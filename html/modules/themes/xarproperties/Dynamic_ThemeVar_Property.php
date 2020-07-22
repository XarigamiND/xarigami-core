<?php
/**
 * Dynamic Array Property
 * @package modules
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2012 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Include the base class
 */
sys::import('modules.dynamicdata.class.properties');
/**
 * Handle the theme var configurations
 */
class Dynamic_ThemeVar_Property extends Dynamic_Property
{
    public $id         = 2002;
    public $name       = 'themevar';
    public $desc       = 'Theme Variable';
    public $reqmodules = array('themes');

    public $xv_themeid;       // The regid of the theme this property belongs to
    public $xv_varid = NULL;
    public $xv_varname = '';
    public $xv_description= '';
    public $xv_label = '';
    public $xv_value = '';
    public $xv_prime = 0;
    public $xv_allowempty = 1;
    public $xv_varcat = NULL;
    public $xv_propargs = array();
    public $xv_vartype = 2; //textbox
    public $xv_status = 1; //active
    public $themename = '';

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/themes/xarproperties';
        $this->tplmodule  = 'themes';
        $this->template   = 'themevar';
        // Make sure we get an object reference so we can get the theme ID value
        $this->include_reference = 1;

    }

    public function checkInput($name = '', $value = null)
    {
        $name = !empty($name) ? $name : 'dd_'.$this->id;
        if (!xarVarFetch($name,'isset',$configuration,NULL,XARVAR_NOT_REQUIRED)) return;
        if (!isset($configuration)) return true;
        //arrange the values in normal form for a theme var
        if (isset($configuration['xv_varconfig']) && is_array($configuration['xv_varconfig'])) $propargs = $configuration['xv_varconfig'];
        $args = array();
        //only keep config properties with some value
        if (is_array($propargs)) {
            foreach ($propargs as $prop => $val) {
                if (isset($val) && !empty($val)) {
                    $args[$prop] = $val;
                }
            }
        }
        //standard config values
        $config['propargs'] = $args;
        $config['format'] = $configuration['xv_proptype'];
        $config['label'] = $configuration['xv_label'];
        $config['default'] = $configuration['xv_default'];
        $config['status'] = $configuration['xv_status'];
        $config['varcat'] = $configuration['xv_varcat'];
        $proptypes = xarMod::apiFunc('dynamicdata','user','getproptypes');
         //update irregardless if set or not
        foreach($proptypes as $proptypeid=>$propinfo) {
            if ($propinfo['format'] ==  $configuration['xv_proptype']) {
                $config['name'] = $propinfo['name'];
            }
        }
        $propinfo = Dynamic_Property_Master::getProperty($config); //type is the name
        $config['propertyname'] = $config['name'];
        $var = array();
        //standard theme var values
        $var['id'] = $configuration['xv_varid'];
        $var['value'] = $configuration['xv_value']; //we only set this on input of the theme var
        $var['varname'] = $configuration['xv_varname'];
        $var['themeid'] = $configuration['xv_themeid'];
        $var['prime'] = isset($configuration['xv_prime'])?$configuration['xv_prime']:0;
        $var['description'] = $configuration['xv_description'];
        $var['config'] =$config;
        unset($config['varconfig']); //we don't need it now- just a name to prevent confusion with themevar config value
        $this->value = $var;

        return true;
    }

  /**
    * Format of variable from the db
    * id','name','value','prime','description','config'
    * 'config  - propargs - aray of standard property definitions for the actual property
    *          - default - default value for prime
    *          - type - a numeric property type
    *          - status  - numeric property status
    *          - label - label for the property
    *          - value
    *          - varcat - category type of the variable
    */

    public function showOutput(Array $data = array())
    {
        // set theme regid the from object reference (= theme_configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['regid'])) {
            $this->themeid = $this->objectref->properties['regid']->value;
            $data['themeid'] = $this->themeid;
        }
        // Get the configuration of this theme and parse it
        $this->parseValidation($this->value);
        $data['configs'] = $this->validation;

        // Get the configuration of this theme and parse it
        return parent::showOutput($data);
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        if (isset($themename)) $this->themename= $themename;
        if (!isset($varname) && isset($name)) $varname = $name;
        if (isset($varname)) $this->xv_varname = $varname;
        if (isset($valid)) {
           $this->value = $value;
        }
        if (!isset($themename)) return;
          // set theme regid the from object reference (= theme_configuration) if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['themeid'])) {
            $this->themeid = $this->objectref->properties['themeid']->value;
            $data['themeid'] = $this->themeid;
        }
        /*
        $props = $this->getThemeVarConfiguration();
        foreach ($props as $propname => $propvalue) {
            $data['propname'] = isset($data[$propname])?$data[$propname]: $propvalue;
        }
         */
        $data['themeid'] = xarThemeGetIDFromName($themename);
        $this->xv_themeid = $data['themeid'];

        $this->parseValidation($data);

        $args = isset($this->xv_varconfig) ? $this->xv_varconfig : array();
        //get the property type for this specific variable
        $mythemevar =Dynamic_Property_Master::getProperty($args);
        // Get the configuration of this specific variable
         $data['showinput'] = $mythemevar->showInput($args);
        $data['showinput'] = $mythemevar->showInput($args);

        // Get the configuration of this theme and parse it
        //$this->parseValidation($this->validation);

        $data['configs'] = $this->validation;
        $data['varname'] = $this->xv_varname;
        return parent::showInput($data);
    }

   public function parseValidation($validation = '')
    {
        if (is_array($validation )) {
            $fields = $validation ;
        } elseif (empty($validation )) {
            $fields = array();
        // try normal serialized configuration
        } else {
            try {
                $fields = unserialize($validation );
            } catch (Exception $e) {
                return true;
            }
        }

        //get our theme configurations
        $valprops = $this->getValidationProperties();
        //what about the instantiation for the config property
        $themeprops= $this->getThemeVarConfiguration();
        foreach ($valprops as $name=>$detail) {
            $check = substr($name,3);
            if (isset($themeprops[$check])) {
                //allow override by passed in validation
                $this->$name = isset($fields[$check]) ? $fields[$check] :$themeprops[$check];
            } else {
                $this->$name = null;
            }

        }
    }

    public function getThemeVarConfiguration()
    {

    $themename = $this->themename;
    $varname = isset($this->varname)?$this->varname: $this->xv_varname;
    $varinfo = xarThemeGetConfig(array('themename'=>$themename,'varname'=>$varname));

    $args['varname']    = $varname;
    if (isset($varinfo['id'])) {
        $args['varid']      = $varinfo['id'];
    } else {
        return;
        //we probably are using this property out of context
    }
    $args['themename']  = $themename;
    $args['themeid']    = isset($this->xv_themeid)? $this->xv_themeid: xarThemeGetIDFromName($themename);
    $args['prime']      = $varinfo['prime'];
    $args['description'] = $varinfo['description'];
    $args['config']     = $varinfo['config'];
    $propargs           = $varinfo['config']['propargs'];
    $args['varconfig']  = array_merge($args['config'],$propargs);
    $args['label']      = $varinfo['config']['label'];
    $args['default']    = isset($varinfo['config']['default'])?$varinfo['config']['default']:$varinfo['value'];
    $args['status']     = $varinfo['config']['status'];
    $args['proptype']   = $varinfo['config']['type'];
    $args['type']       = $varinfo['config']['type'];
    $args['value']      = $varinfo['value'];
    $args['varcat']     = $varinfo['config']['varcat'];

    $varconfiguration = $args;

    return $varconfiguration;
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
                $themelist = array();
                //this dropdownlist has some security check in the chain of events
                //only call when system is stable - ie not in install or upgrade
                if (sys::isStable()) {
                    $themelist = xarModAPIfunc('themes','user','dropdownlist');
                }
                //default category list
                //can be others
                $catlist = array('color_and_images'      => xarML('Color and Images'),
                                'text'                  => xarML('Text and Fonts'),
                                'layout_and_position'   => xarML('Layout and Position'),
                                'dimensions_and_padding'=> xarML('Dimensions and Padding'),
                                'background_and_borders'=> xarML('Background and Borders'),
                                'tables'                => xarML('Tables'),
                                'forms'                 => xarML('Forms'),
                                'miscellaneous'         => xarML('Miscellaneous'));


                $validations = array(
                                        'xv_varid'    =>  array('label'=>xarML('varid'),
                                                          'description'=>xarML('The theme ID'),
                                                          'propertyname'=>'itemid',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                          ),
                                        'xv_themeid'    =>  array('label'=>xarML('Theme ID'),
                                                          'description'=>xarML('The theme ID'),
                                                          'propertyname'=>'dropdown',
                                                          'ignore_empty'  =>1,
                                                          'propargs'=>array('options'=>$themelist),
                                                          'ctype'=>'definition'
                                                          ),

                                          'xv_varname'      =>  array('label'=>xarML('Name'),
                                                          'description'=>xarML('The name of the variable'),
                                                          'propertyname'=>'textbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition',
                                                           'propagrs'=> array('maxlength'=>64),
                                                           'configinfo'    => xarML('[No hypens or spaces]')
                                                          ),
                                          'xv_description'  =>  array('label'=>xarML('Description'),
                                                          'description'=>xarML('Description of this variable function'),
                                                          'propertyname'=>'textarea_small',
                                                           'ignore_empty'  =>1,
                                                           'propargs' => array('maxlength'=>254),
                                                           'ctype'=>'definition',
                                                          ),
                                            'xv_label'  =>  array('label'=>xarML('Label'),
                                                          'description'=>xarML('Display label'),
                                                          'propertyname'=>'textbox',
                                                           'ignore_empty'  =>1,
                                                            'propagrs'=> array('maxlength'=>64),
                                                           'ctype'=>'definition',
                                                          ),
                                            'xv_default'  =>  array('label'=>xarML('Default value'),
                                                          'description'=>xarML('Can be empty, used if no value provided.'),
                                                          'propertyname'=>'textbox',
                                                           'ignore_empty'  =>1,
                                                            'propagrs'=> array(),
                                                           'ctype'=>'definition',
                                                          ),
                                            'xv_value'  =>  array('label'=>xarML('Current value'),
                                                          'description'=>xarML(''),
                                                          'propertyname'=>'textbox',
                                                           'ignore_empty'  =>1,
                                                            'propagrs'=> array(),
                                                           'ctype'=>'definition',
                                                          ),
                                          'xv_proptype'      =>  array('label'=>xarML('Variable type'),
                                                          'description'=>xarML('The property type of the variable'),
                                                          'propertyname'=>'fieldtype',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition',
                                                          ),

                                          'xv_status'      =>  array('label'=>xarML('Display status'),
                                                          'description'=>xarML('Active or disabled'),
                                                          'propertyname'=>'fieldstatus',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition',
                                                          ),

                                          'xv_varcat'     =>  array('label'=>xarML('VariableCategory'),
                                                          'description'=>xarML('Default value of the variable'),
                                                          'propertyname'=>'combo',
                                                           'ignore_empty'  =>1,
                                                             'propargs'=>array('options'=>$catlist),
                                                           'ctype'=>'definition',
                                                          ),
                                            'xv_prime'     =>  array('label'=>xarML('System variable?'),
                                                          'description'=>xarML('System variable or user variable'),
                                                          'propertyname'=>'checkbox',
                                                           'ignore_empty'  =>1,
                                                           'propargs' =>array('disabled'=>1), //disabled on input
                                                           'ctype'=>'definition',
                                                          ),
                                            'xv_varconfig'     =>  array('label'=>xarML('Configuration'),
                                                          'description'=>xarML('Configuration'),
                                                          'propertyname'=>'configuration',
                                                           'propargs'=>array('allowempty'=>1),
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition',
                                                          )

                                    );
             //$validationarray= array_merge($validations,$parentvals);
             $validationarray = $validations;
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
                          'id'         => 2002,
                          'name'       => 'themevar',
                          'label'      => 'Theme variable',
                          'format'     => '2001',
                          'validation' => serialize($validations),
                          'source'     => $this->source,
                          'dependancies' => '',
                          'filepath'    => 'modules/themes/xarproperties',
                          'requiresmodule' => 'themes',
                          'aliases'    => '',
                          'args'       => serialize($args),
                          // ...
                         );
       return $baseInfo;
    }
}
?>