<?php
/**
 * Dynamic Theme Configuration property
 *
 * @package modules
 * @copyright (C) 2011-2012 skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dymamic data module
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Include the base class
 *
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');

/**
 *Handle the validation of the theme var customization
 *
 * @package dynamicdata
 */
class Dynamic_ThemeConfig_Property extends Dynamic_TextBox_Property
{
    public $id      = 2001;
    public $name    = 'themeconfig';
    public $desc    = 'Theme Config';
    public $reqmodules  = 'themes';
    public $proptype    = 1;
    public $xv_themeid = NULL;
    public $varname = NULL;
    public $themename = NULL;
    public $configs = array();

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/themes/xarproperties';
        $this->include_reference = 1;
        $this->template = 'themeconfig';
        $this->tplmodule = 'themes';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = !empty($name) ? $name : 'dd_'.$this->id;
        if (!xarVarFetch($name,'isset',$configuration,NULL,XARVAR_NOT_REQUIRED)) return;
        $themename = $this->themename;
        if (!isset($themename)) $themename = $this->id;
        $data['themename'] = $themename;
        $this->parseValidation($data);
        $vars= $this->configs;
        $newvalue = NULL;
        foreach ($vars as $var =>$info) {
            $args['type'] = $info['config']['type'];
            //make sure we check the correct property name as per our configuration property
            $args['name']= xarVarPrepForDisplay($themename.'_'.$var);
            //make sure we pass the validation for properties that need it like dropdowns, for options
            $args['validation'] = $info['config']['propargs'];
            $newname = $args['name'];
            $args['label'] = $info['config']['label'];
            $prop = Dynamic_Property_Master::getProperty($args);
            $isvalid = $prop->checkInput($newname);
            if ($isvalid === FALSE) {
                $invalidmsg = $prop->invalid;
                $this->invalid[$var] = $invalidmsg;
            }
            //get the value via getValue method for properties which need this to set actual value eg array
            $newvalue[$var] = $prop->getValue();
         }
        $this->value = $newvalue;
        if (is_array($this->invalid) && count($this->invalid)>0) {
            return false;
        }else {
            return true;
        }
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        if (!empty($this->objectref) && !empty($this->objectref->properties['xv_themeid'])) {
            $this->themeid = $this->objectref->properties['xv_themeid']->value;
            $data['themeid'] = $this->themeid;
        }
        // Get the configuration of this theme and parse it
        $this->parseValidation($data);
        $args = $this->configs;
        $this->name = $this->themename;
        $this->id = $this->themename;
        $varlist = array();
        $varlist['varcattypes'] = array();
        ksort($args);
        foreach ($args as $var =>$vardata) {
          //  $vardata['config']['validation'] = serialize( $vardata['config']['propargs'] );
           $varlist['varcattypes'][$vardata['varcat']][$var] = $vardata;
        }

        $data = $varlist;
        $data['name'] = $this->name;
        $data['id'] = $this->id;
        $data['varcount'] = count($args);
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!empty($this->objectref) && !empty($this->objectref->properties['xv_themeid'])) {
            $this->themeid = $this->objectref->properties['xv_themeid']->value;
            $data['themeid'] = $this->themeid;
        }

        // Get the configuration of this theme and parse it
        $this->parseValidation($data);

        $data['configs'] = $this->validation;
        return parent::showOutput($data);
    }

    public function parseValidation($validation = '')
    {
        if (is_array($validation)) {
            $fields = $validation;
        } elseif (empty($validation)) {
            $fields = array();
        // try normal serialized configuration
        } else {
            try {
                $fields = unserialize($validation);
            } catch (Exception $e) {
                return true;
            }
        }
        //get our theme configurations
        $themeproperties =  $this->getThemeVarConfiguration();

        foreach ($themeproperties  as $vname=>$info) {
            if (isset($info['config']['propargs'])) {
                foreach ($info['config']['propargs'] as $name =>$val ) {
                    $check = substr($name,3);
                    if (isset($fields[$check])) {
                        //allow override by passed in validation
                        $check = isset($fields[$check]) ? $fields[$check] : $val;
                    } else {
                        $check = null;
                    }

                    $themeproperties[$vname]['config']['propargs']['name'] = $check;
                    $this->configs[$vname] = $themeproperties[$vname];
                }
            }
        }
    }

    public function getThemeVarConfiguration()
    {
        $themename = $this->themename;
        if (!isset($themename)) $themename = $this->id;
        $varname = $this->varname;
        if (isset($varname)) {
            $varsinfo[] = xarThemeGetConfig(array('themename'=>$themename,'varname'=>$varname));
        } else {
             $varsinfo = xarThemeGetConfig(array('themename'=>$themename));
        }
        $this->themename = $themename;
        $varlist = array();
        foreach($varsinfo as $var =>$varinfo) {
            $args['varname']    = $var;
            $args['varid']      = $varinfo['id'];
            $args['themename']  = $themename;
            $args['themeid']    = isset($this->xv_themeid)? $this->xv_themeid: xarThemeGetIDFromName($themename);
            $args['prime']      = $varinfo['prime'];
            $args['description'] = $varinfo['description'];
            $args['config']     = $varinfo['config'];
            $propargs           = $varinfo['config']['propargs'];
            $args['label']      = $varinfo['config']['label'];
            $args['default']    = isset($varinfo['config']['default'])?$varinfo['config']['default']:$varinfo['value'];
            $args['status']     = isset($varinfo['config']['status'])? $varinfo['config']['status']:1;
            $args['proptype']   = $varinfo['config']['type'];
            $args['type']       = $varinfo['config']['type'];
            $args['value']      = $varinfo['value'];
            $args['varcat']     = $varinfo['config']['varcat'];
            $this->themeid = $args['themeid'];
            $varlist[$var] = $args;
        }
        return $varlist;
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
            $themelist = xarModAPIfunc('themes','user','dropdownlist');
            $validations= array(   'xv_themeid'    =>  array('label'=>xarML('Theme ID'),
                                                          'description'=>xarML('The theme ID'),
                                                          'propertyname'=>'dropdown',
                                                          'ignore_empty'  =>1,
                                                          'propargs'=>array('options'=>$themelist),
                                                          'ctype'=>'validation'
                                                          ),

                                    );
             $validationarray= array_merge($validations,$parentvals);
        }
        return $validationarray;

    }
    /**
     * Get the base information for this property.
     *
     * @return array Base information for this property
     **/
    function getBasePropertyInfo()
    {
        $args = array();
        $validation = parent::getBaseValidationInfo();
        $baseInfo = array(
                          'id'         => 2001,
                          'name'       => 'themeconfig',
                          'label'      => 'Theme configuration',
                          'format'     => '2001',
                          'validation' => serialize($validation),
                          'source'     => $this->source,
                          'dependancies' => '',
                          'filepath'    => 'modules/themes/xarproperties',
                          'requiresmodule' => 'themes',
                          'aliases' => '',
                          'args'       => serialize( $args ),
                          // ...
                         );
        return $baseInfo;
    }
}

?>