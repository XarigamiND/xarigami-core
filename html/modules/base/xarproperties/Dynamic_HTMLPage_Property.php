<?php
/**
 * HTML Page property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Class to handle dynamic html page property
 *
 * @package dynamicdata
 */
class Dynamic_HTMLPage_Property extends Dynamic_Select_Property
{
    public $id         = 13;
    public $name       = 'webpage';
    public $desc       = 'HTML Page';

    public $xv_basedir  = '';
    public $xv_file_ext = '((xml)|(html))?';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'webpage';

        // specify base directory in validation field
        if (empty($this->xv_basedir) && !empty($this->validation)) {
            // Hack for passing this thing into transform hooks
            // validation may start with 'transform:' and we
            // obviously dont want that in basedir
            if(substr($this->validation,0,10) == 'transform:') {
                $basedir = substr($this->validation,10,strlen($this->validation)-10);
            } else {
                $basedir = $this->validation;
            }
            $this->xv_basedir = $basedir;
        }
    }

    function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;
        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->xv_basedir;
        $filetype = $this->xv_file_ext;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $this->value = $value;
            return true;
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

    function showInput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->getOptions();
        }
        $data['value']    = $value;
        $data['template'] = isset($template)?$template:$this->template;
        return parent::showInput($data);

    }

    function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->xv_basedir;
        $filetype = $this->xv_file_ext;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $srcpath = join('', @file($basedir.'/'.$value));

        } else {
            $srcpath='';
        }
        $data['value']=$value;
        $data['basedir']=$basedir;
        $data['filetype']=$filetype;
        $data['srcpath']=$srcpath;

        $data['template'] = isset($template)?$template:$this->template;
        return parent::showOutput($data);

    }
    public function getOptions()
    {
         $options = parent::getOptions();
        if ((!isset($options) || count($options) == 0) && !empty($this->xv_basedir)) {
            $files = xarMod::apiFunc('dynamicdata','admin','browse',
                                   array('basedir' => $this->xv_basedir,
                                         'filetype' => $this->xv_file_ext));
            if (!isset($files)) {
                $files = array();
            }
            natsort($files);
//            array_unshift($files,'');

            foreach ($files as $file) {
                $options[] = array('id' => $file,
                                   'name' => $file);
            }
            unset($files);
        }

        return $options;
    }
 /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

            $parentvals = parent::getBaseValidationInfo();
            $validations = array('xv_file_ext'       =>  array('label'=>xarML('File extensions'),
                                                                'description'=>xarML('A list of allowable file extensions separated by commas'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'definition',
                                                           ),
                                    'xv_basedir'       =>  array(  'label'=>xarML('Base directory'),
                                                                'description'=>xarML('A file, position relative to index.php, containing select options for this field.'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'                                                      ),

                                );
            $validationarray = array_merge($parentvals,$validations);

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
                              'id'         => 13,
                              'name'       => 'webpage',
                              'label'      => 'HTML page',
                              'format'     => '13',
                              'validation' => serialize($validations),
                              'filepath'    => 'modules/base/xarproperties',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}

?>
