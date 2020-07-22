<?php
/**
 * BL Template property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

sys::import('modules.base.xarproperties.Dynamic_FileList_Property');

/**
 * @package modules
 * @subpackage Base module
 * Class to handle dynamic blocklayout template property
 */
class Dynamic_BLT_Property extends Dynamic_Filelist_Property
{
    public $id         = 666;
    public $name       = 'bltemplate';
    public $desc       = 'Block Layout Template';

    public $xv_file_ext     = 'xd,xt';
    public $xv_blttype      = 'theme'; // 'module'
    public $xv_longname     = true;
    public $xv_bltmodule    = '';    //  'base'
    public $xv_bltsubdir    = '';
    public $xv_bltsubdata   = '';

    function __construct($args)
    {
        parent::__construct($args);
        $this->template  = 'bltemplate';

        if (empty($this->xv_blttype)) {
            $this->xv_blttype = 'theme';
        }
        // set basedir
        switch ($this->xv_blttype) {
            case 'module' :
                $curtheme = xarTplGetThemeDir();
                if (empty($this->xv_bltmodule)) {
                    // default base
                    $this->xv_bltmodule = 'base';
                }
                $this->xv_basedir = $curtheme . '/modules/' . $this->xv_bltmodule . '/includes';
                break;
            case 'theme' :
                $curtheme = xarTplGetThemeDir();
                $this->xv_basedir = $curtheme . '/includes';
                break;
            case 'system' :
                $this->xv_basedir = '';
                break;
        }

        if (!empty($this->xv_bltsubdir)) {
            $this->xv_basedir = $this->xv_basedir . '/' . $this->xv_bltsubdir;
        }

    }

    function validateValue($value = null)
    {
       if (!parent::validateValue($value)) return false;

        if (!isset($value)) {
            $value = $this->value;
        }
         if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }
        $basedir = $this->xv_basedir;
        $filetype = $this->xv_file_ext;

        $prop = Dynamic_Property_Master::getProperty(array('type'=>'filelist'));
        $prop->xv_longname = true;

        $prop->xv_allowempty =  $this->xv_allowempty;
        $prop->xv_basedir = $this->xv_basedir;
        $prop->xv_file_ext = $this->xv_file_ext;
        $prop->validateValue($value);
        $this->value = $prop->value;

        return true;

    }

    function showInput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
         $data['basedir'] = isset($basedir) ? $basedir : $this->xv_basedir;
         $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
            $data['value'] = basename($data['value']);
              $this->value =  $data['value'];

        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['name']    = $name;
        $data['id']      = $id;
        $data['options'] = $this->options;
        $data['blttype'] = !isset($this->blttype)?'theme':$this->bltype;
        $data['bltmodule'] = isset($this->bltmodule)?$this->bltmodule:'';
        $data['bltsubdir'] = isset($this->bltsubdir)? $this->bltsubdir:'';
        $data['bltsubdata'] =  isset($this->bltsubdata)? $this->bltsubdata:'';
        $data['bltbasedir'] = $this->xv_basedir;
         $data['longname'] = $this->xv_longname;
        if (!isset($template)) $data['template'] = $this->template;
         return parent::showInput($data);

    }

    function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->xv_basedir;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {

        } else {
        //    return xarVarPrepForDisplay($value);
            if ($this->xv_allowempty) {
                $value = '';
            }
            //else {
              //  $value='FILE-NOT-EXISTS';
            //}
            //return '';
        }

        // prepare value for output
        if (!empty($value)) {
            $value = basename( $value, '.xt' );
            if (!empty($this->xv_bltsubdir)) {
                $value = $this->xv_bltsubdir . '/' . $value;
            }
        }
        $data['bltfile']=$value;
        $data['blttype']=$this->xv_blttype;
        $data['bltmodule']=$this->xv_bltmodule;
        $data['bltsubdata']=$this->xv_bltsubdata;
        $data['bltbasedir'] = $this->xv_basedir;
        if (!isset($template)) $template = $this->template;

        return xarTplProperty('base', 'bltemplate', 'showoutput', $data);

    }



  /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    function getBaseValidationInfo()
    {
        static $validationarray = array();

        if (empty($validationarray)) {
            $parentvalidations = parent::getBaseValidationInfo();
            $tploptions = array(
                    array('id'=>'module','name'=>xarML('Module')),
                    array('id'=>'theme', 'name'=>xarML('Theme')),
                    array('id'=>'syste,', 'name'=>xarML('System')),
                );

            $validations = array('xv_blttype' =>  array(  'label'=>xarML('Template type'),
                                                                'description'=>xarML('Template type'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => array('options'=>$tploptions),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                     'xv_bltmodule' =>  array(  'label'=>xarML('Module'),
                                                                'description'=>xarML('Module name if module template type'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array('size'=>30),
                                                                'ignore_empty'  =>1,
                                                                 'configinfo'    => xarML('Module templates only'),
                                                                'ctype'=>'definition'
                                                          ),
                                     'xv_bltsubdir' =>  array(  'label'=>xarML('Sub-directory'),
                                                                'description'=>xarML('Subdirectory for template'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                     'xv_bltsubdata' =>  array(  'label'=>xarML('Subdata array'),
                                                                'description'=>xarML('Array of data to be passed to the subtemplate'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          )

                                    );
            $validationarray = array_merge($parentvalidations,$validations);
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
         $validation = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 666,
                              'name'       => 'bltemplate',
                              'label'      => 'BL template',
                              'format'     => '666',
                              'validation' => serialize($validation),
                              'source'         => '',
                              'dependancies'   => '',
                              'filepath'    => 'modules/base/xarproperties',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}
?>
