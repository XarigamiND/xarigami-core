<?php
/**
 * File list property
 *
 * @package modules
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

sys::import('modules.base.xarproperties.Dynamic_Select_Property');
sys::import('xarigami.structures.relativedirectoryiterator');

/**
 * Handle the filelist property
 *
 * @package dynamicdata
 */
class Dynamic_FileList_Property extends Dynamic_Select_Property
{
    public $id         = 1200;
    public $name       = 'filelist';
    public $desc       = 'File List';

    public $xv_file_ext = '';
    public $xv_basedir = 'var/uploads';
    public $xv_longname = false;
    public $xv_matches = '';
    public $xv_baseurl= '';
    public $xv_size        = 1; //number of rows to show

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath = 'modules/base/xarproperties';
        $this->template ='filelist';

        // keep things relative here if possible (cfr. basedir vs. baseurl issue for images et al.)
        if (empty($this->xv_basedir)) $this->xv_basedir = sys::varpath();
        if (empty($this->xv_baseurl)) $this->xv_baseurl = $this->xv_basedir;
        else {
            // support common xar  calls
            if ((strpos($this->xv_basedir,'sys') === 0) || (strpos($this->xv_basedir,'xar') === 0)) {
                eval('$temp='.$this->xv_basedir.";");
                $this->xv_basedir = $temp;
            }
        }
        $this->setExtensions();
    }

     public function showInput(Array $data = array())
    {
        extract($data);
        if (isset($file_ext) && !isset($extensions)) $extensions = $file_ext; //allow passing in of alternative

        if (isset($basedir)) $this->xv_basedir = $basedir;
        //support legacy
        if (!isset($basedir) && isset($baseurl)) $this->xv_basedir = $baseurl;
        $paths =  $this->getBaseDirInfo();
        $newbasepath = isset($paths['basepath']) ? $paths['basepath'] : '';
        $data['size'] = isset($size) && !empty($size)? $size: $this->xv_size;//let tpl decide
        if (isset($matches)) $this->validation_matches = $matches;
        if (isset($extensions)) $this->setExtensions($extensions);
        if (isset($longname)) $this->xv_longname = $longname;
        $data['longname'] = isset($longname)?$longname:$this->xv_longname;
        $data['extensions'] = isset($extensions)?$extensions:$this->xv_file_ext;
        //if (isset($firstline))  $this->xv_firstline = $firstline;
        $data['template'] = isset($template)?$template:$this->template;
        return parent::showInput($data);
    }

    public function validateValue($value = null)
    {

        if (!parent::validateValue($value)) return false;
        $paths =  $this->getBaseDirInfo();
        $basedir= isset($paths['basedir']) ? $paths['basedir'] : '';
        $basepath = isset($paths['basepath']) ? $paths['basepath'] : '';
        $filepath = str_replace('//','/',$basepath.'/'.$basedir.'/'.$value);
        if (!empty($value) &&  preg_match('/^[a-zA-Z0-9_\/.\-\040]+$/', $value) &&  $this->validateExtension($value) &&  file_exists($filepath) &&  is_file($filepath)) {
                return true;
        } elseif (empty($value)) {
            return true;
        }
        $namelabel = isset($this->label)?$this->label:$this->name;
        $this->invalid = xarML('Invalid selection: #(1) for #(2)', $value, $namelabel);
        $this->value = null;
        return false;
    }

    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }

        if (empty($this->xv_basedir)) return array();
        $paths =  $this->getBaseDirInfo();
        $basepath = isset($paths['basepath']) ? $paths['basepath'] : '';
         $basedir= isset($paths['basedir']) ? $paths['basedir'] : '';
        // this works with relative directories
        $filepath = str_replace('//','/',$basepath.'/'.$basedir);
        $dir = new RelativeDirectoryIterator( $filepath);
        if ($dir == false) return array();

        for($dir->rewind();$dir->valid();$dir->next()) {
            if($dir->isDir()) continue; // no dirs

            if(!$this->validateExtension($dir->getExtension())) continue;
            if($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
            $name = $dir->getFileName();
            $id = $name;
            if (!$this->xv_longname) $name = substr($name, 0, strlen($name) - strlen($dir->getExtension()) - 1);
            if(!empty($this->xv_matches) && (strpos($this->xv_matches,$name) === false)) continue;
            $options[] = array('id' => $id, 'name' => $name);
        }

        // Save options only when we're dealing with an object list
        if (!empty($this->_items)) {
            $this->options = $options;
        }

        return $options;
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
/* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    function getBaseValidationInfo()
    {
        static $validationarray = array();

        if (empty($validationarray)) {
            $parentvalidation = parent::getBaseValidationInfo();

            $validations = array('xv_file_ext' =>  array(  'label'=>xarML('File extentions'),
                                                                'description'=>xarML('List of file extensions separated by commas'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),

                                    'xv_basedir'   =>  array(  'label'=>xarML('Base directory'),
                                                                'description'=>xarML('Base directory for file browsing.'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=> 'definition'
                                                          ),
                                    'xv_longname'   =>  array(  'label'=>xarML('Display file extension'),
                                                                'description'=>xarML('Display file name plus file extension in the drop down selection list.'),
                                                                'propertyname'=>'checkbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=> 'definition'
                                                          ),
                                );
            $validationarray = array_merge($parentvalidation,$validations);
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
                              'id'         => 1200,
                              'name'       => 'filelist',
                              'label'      => 'File list',
                              'format'     => '1200',
                              'validation' => serialize($validations),
                              'source'         => '',
                              'filepath'    => 'modules/base/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

    public function getBaseDirInfo()
    {

        if (isset($this->xv_basedir))  {
           $prop_path = $this->xv_basedir;//often the bit above webroot but may be absolute
            // If the base dir supplied contains {var} then expand that
            // We discard anything that comes before '{var}' and anything up to the next '/' after it.
            // We expect {var} to be used like this: {var}/custom_path
            if (preg_match('/{var}/', $prop_path)) {
              $prop_path = preg_replace('#{var}[^/]*/#', sys::varpath() . '/', $prop_path);
              $this->xv_basedir = $prop_path ;

            }
        } else {
             // No base directory supplied, so default to '{var}/uploads', with no basepath.
            $this->xv_basepath = '';
            $this->xv_basedir = 'var/uploads';
        }

        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/default/images
        if (!empty($this->xv_basedir) && preg_match('/\{theme\}/',$this->xv_basedir)) {
            $curtheme = xarTplGetThemeDir();
            $this->xv_basedir = preg_replace('/\{theme\}/',$curtheme,$this->xv_basedir);
        }

        $uname = xarUserGetVar('uname');
        $uid = xarUserGetVar('uid');
        $udir = $uname . '_' . $uid;

        if (!empty($this->xv_basedir) && preg_match('/\{user\}/',$this->xv_basedir)) {
            $this->xv_basedir = preg_replace('/\{user\}/',$udir,$this->xv_basedir);
        }

        // This one for uploads-hooked operation only.
        if (!empty($this->xv_importdir) && preg_match('/\{user\}/',$this->xv_importdir)) {
            $this->xv_importdir = preg_replace('/\{user\}/',$udir,$this->xv_importdir);
        }
        $paths = sys::getBaseDirs($this->xv_basedir) ;

        return $paths;

    }

}
?>
