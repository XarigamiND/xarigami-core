<?php
/**
 * Dynamic Textupload Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
sys::import('modules.base.xarproperties.Dynamic_TextArea_Property');

/**
 * Handle text upload property
 *
 * @package dynamicdata
 *
 */
class Dynamic_TextUpload_Property extends Dynamic_TextArea_Property
{
    public $id         = 38;
    public $name       = 'textupload';
    public $desc       = 'Text Upload ';
    public $xv_classname = ''; // via GUI in property
    public $class = ''; // passed in from template
    public $defaultclass = ''; // some default class
    public $xv_rows = 2;
    public $xv_cols = 35;
    public $xv_maxlength = null;
    public $xv_method = null;
    public $xv_display_size = '';
    public $methods = array('trusted'  => false,
                            'external' => false,
                            'upload'   => false,
                            'stored'   => false);
    public $xv_basedir = null;
    public $xv_importdir = null;
    public $xv_max_file_size    = 1000000;
    public $UploadsModule_isHooked = false;
    public $xv_file_ext = 'txt';
    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    public $upload = true;

    function __construct($args)
    {
        parent::__construct($args);

        // Note : {user} will be replaced by the current user uploading the file - e.g. var/uploads/{user} -&gt; var/uploads/myusername_123
        $uname = xarUserGetVar('uname');
        $uid = xarUserGetVar('uid');
        $udir = $uname . '_' . $uid;
        if (!empty($this->xv_basedir) && preg_match('/\{user\}/',$this->xv_basedir)) {
           $this->xv_basedir = preg_replace('/\{user\}/',$udir,$this->xv_basedir);
        }
        if (!empty($this->importdir) && preg_match('/\{user\}/',$this->xv_importdir)) {
            // Note: we add the userid just to make sure it's unique e.g. when filtering
            // out unwanted characters through xarVarPrepForOS, or if the database makes
            // a difference between upper-case and lower-case and the OS doesn't...
            $this->xv_importdir = preg_replace('/\{user\}/',$udir,$this->xv_importdir);
        }
    }

    function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;
        // the variable corresponding to the file upload field is no longer set in PHP 4.2.1+
        // but we're using a textarea field to keep track of any previously uploaded file here
        if (!isset($value)) {
            $value = $this->value;
        }
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }
        // retrieve new value for preview + new/modify combinations
        if (xarCoreCache::isCached('DynamicData.TextUpload',$name)) {
            $this->value = xarCoreCache::getCached('DynamicData.TextUpload',$name);
            return true;
        }
        // if the uploads module is hooked (to be verified and set by the calling module)
        // any uploaded files will be referenced in the text as #...:NN# for transform hooks
        if (xarCoreCache::getCached('Hooks.uploads','ishooked')) {
            // set override for the upload/import paths if necessary
            if (!empty($this->xv_basedir) || !empty($this->xv_importdir)) {
                $override = array();
                if (!empty($this->xv_basedir)) {
                    $override['upload'] = array('path' => $this->xv_basedir);
                }
                if (!empty($this->xv_importdir)) {
                    $override['import'] = array('path' => $this->xv_importdir);
                }
            } else {
                $override = null;
            }
            $return = xarMod::apiFunc('uploads','admin','validatevalue',
                                    array('id' => $name, // not $this->id
                                          'value' => null, // we don't keep track of values here
                                          // pass the module id, item type and item id (if available) for associations
                                      // Note: for text upload, the file association is not maintained after editing
                                          'moduleid' => $this->_moduleid,
                                          'itemtype' => $this->_itemtype,
                                          'itemid'   => !empty($this->_itemid) ? $this->_itemid : null,
                                          'multiple' => FALSE, // not relevant here
                                          'methods' => $this->methods,
                                          'override' => $override,
                                          'format' => 'textupload',
                                          'maxsize' => $this->xv_max_file_size));
            if (!isset($return) || !is_array($return) || count($return) < 2) {
                $this->value = null;
                return false;
            }
            if (empty($return[0])) {
                $this->value = null;
                $this->invalid = xarML('value');
                return false;
            }
            // show magic link #...:NN# to file in text (cfr. transform hook in uploads module)
            $magiclinks = '';
            if (!empty($return[1])) {
                $magiclinks = xarMod::apiFunc('uploads','user','showoutput',
                                            array('value' => $return[1],
                                                  'format' => 'textupload',
                                                  'style' => 'icon'));
                // strip template comments if necessary
                $magiclinks = preg_replace('/<\!--.*?-->/','',$magiclinks);
                $magiclinks = trim($magiclinks);
            }
            if (!empty($value) && !empty($magiclinks)) {
                $value .= ' ' . $magiclinks;
            } elseif (!empty($magiclinks)) {
                $value = $magiclinks;
            }
            $this->value = $value;
            // save new value for preview + new/modify combinations
            xarCoreCache::setCached('DynamicData.TextUpload',$name,$value);
            return true;
        }
        //no process this upload specifically for the textupload property
        //we have already validated
        $upname = $name .'_upload';
        if (!empty($_FILES) && !empty($_FILES[$upname]) && !empty($_FILES[$upname]['tmp_name'])
            // is_uploaded_file() : PHP 4 >= 4.0.3
            && is_uploaded_file($_FILES[$upname]['tmp_name']) && $_FILES[$upname]['size'] > 0) {
            if ($_FILES[$upname]['size']  > $this->xv_max_file_size) {
                $this->invalid = xarML('file. Size is greater than maximum of #(1)',$this->xv_max_file_size);
                return false;
            }
            $tmpdir = sys::varpath();
            $tmpdir .= '/cache/templates';
            $tmpfile = tempnam($tmpdir, 'dd');
            $filetype = $this->xv_file_ext;
            $fileName = xarVarPrepForOS(basename(strval($_FILES[$upname]['name'])));
            $this->setExtensions($filetype);
            if (!$this->validateExtension($_FILES[$upname]['name'])) {
                    $this->invalid = xarML('file. File type must be one of:  #(1)',$filetype);
                    return false;
            }
            if (move_uploaded_file($_FILES[$upname]['tmp_name'], $tmpfile) && file_exists($tmpfile)) {
                $this->value = join('', file($tmpfile));
                unlink($tmpfile);
            }
            // save new value for preview + new/modify combinations
            xarCoreCache::setCached('DynamicData.TextUpload',$name,$this->value);
        // retrieve new value for preview + new/modify combinations
        } elseif (xarCoreCache::isCached('DynamicData.TextUpload',$name)) {
            $this->value = xarCoreCache::getCached('DynamicData.TextUpload',$name);
        } elseif (!empty($value)) {
            $this->value = $value;
        } else {
            $this->value = '';
        }

        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
         if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        $data['upid']     = !empty($id) ? $id.'_upload' : '';
        $data['upname']   = $name.'_upload';
        $data['size']     = !empty($size) ? $size : $this->xv_display_size;
        $data['basedir'] = isset($basedir) ? $basedir : $this->xv_basedir;
        $data['maxsize'] = isset($maxsize)?$maxsize:$this->xv_max_file_size;
        $data['extensions'] = isset($data['extensions'])?$data['extensions']:$this->xv_file_ext;
        if (isset($data['extensions']))   $this->setExtensions($data['extensions']);
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['fileName']   = isset($data['fileName'])?$data['fileName']:$data['value'];
        $data['template'] = isset($template)?$template:'textupload';
        return parent::showInput($data);
    }

    function showOutput(Array $data = array())
    {
        extract($data);

         if(!empty($basedir)) $this->xv_basedir = $basedir;
        if (!isset($data['value']) ||  empty($data['value'])) $data['value'] = $this->value;
        //don't pass through basedir for uploaded file as preparation of final value is done by fileupload property

        return parent::showOutput($data);
    }
     /**
     * Validate the given filename against the list/regex of allowed file extensions
     */
    public function validateExtension($filename = '')
    {
        $pos = strrpos($filename, '.');
        if ($pos !== false) {
            $extension = substr($filename, $pos + 1);
        } else {
            // in case we already got the extension from $dir->getExtension()
            $extension = $filename;
        }

        if (!empty($this->file_ext_list) &&
            !in_array($extension, $this->file_ext_list)) {
            return false;
        }
        if (!empty($this->file_ext_regex) &&
            !preg_match('/^' . $this->file_ext_regex . '$/', $extension)) {
            return false;
        }
        return true;
    }
    /**
     * Set the list/regex of allowed file extensions, depending on the syntax used (cfr. image, webpage, ...)
     */
    public function setExtensions($file_ext = null)
    {
        if (isset($file_ext)) {
            $this->xv_file_ext = $file_ext;
        }
        $this->file_ext_list = null;
        $this->file_ext_regex = null;
        if (!empty($this->xv_file_ext)) {
            // example: array('gif', 'jpg', 'jpeg', ...)
            if (is_array($this->xv_file_ext)) {
                $this->file_ext_list = $this->xv_file_ext;

            // example: gif,jpg,jpeg,png,bmp,txt,htm,html
            } elseif (strpos($this->xv_file_ext, ',') !== false) {
                $this->file_ext_list = explode(',', $this->xv_file_ext);

            // example: gif|jpe?g|png|bmp|txt|html?
            } else {
                $this->file_ext_regex = $this->xv_file_ext;
            }
        }
    }
    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

            $methodarray = array('trusted'=>'trusted',
                                 'external'=>'external',
                                 'upload'=> 'upload',
                                 'stored' => 'stored'
                                );

            $parentvals = parent::getBaseValidationInfo();
            $validationarray = array('xv_file_ext'       =>  array('label'=>xarML('File extensions'),
                                                                'description'=>xarML('A list of allowable file extensions separated by commas'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'validation'),
                                    'xv_basedir'       =>  array(  'label'=>xarML('Base directory'),
                                                                'description'=>xarML('A directory relative to index.php, containing select options for this field.'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>0,
                                                                'ctype'=>'definition'),
                                    'xv_method'       =>  array(  'label'=>xarML('Allowed methods'),
                                                                'description'=>xarML('Allowed methods for uploading of text'),
                                                                'propertyname'=>'checkboxlist',
                                                                'propargs'  => array('options' => $methodarray,
                                                                                    ),
                                                                'configinfo'=>xarML('Applicable when Uploads hooked'),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'),
                                    'xv_max_file_size'   =>  array('label'=>xarML('Maximum file size'),
                                                                'description'=>xarML('Maximum file size for uploaded file'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>15),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('bytes'),
                                                                'ctype' =>'validation')
                                    );
             $validationarray = array_merge($parentvals,$validationarray);
        }
         return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args = array();
        $validations = $this->getBaseValidationInfo();

         $baseInfo = array(
                           'id'         => 38,
                           'name'       => 'textupload',
                           'label'      => 'Text upload',
                           'format'     => '38',
                           'validation' => serialize($validations),
                            'source'     => '',
                            'dependancies' => '',
                            'filepath'    => 'modules/base/xarproperties',
                            'requiresmodule' => 'base',
                            'aliases' => '',
                            'args' => serialize( $args ),
                            'args'         => '',
                            // ...
                           );
        return $baseInfo;
    }
}
?>
