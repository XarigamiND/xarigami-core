<?php
/**
 * Dynamic State List Property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * handle the StateList property
 * @package dynamicdata
 */
class Dynamic_StateList_Property extends Dynamic_Select_Property
{
    public $id         = 43;
    public $name       = 'statelisting';

    public $defaultcountries = 'au,us,ca';

    function __construct($args)
    {

        parent::__construct($args);
        $this->template  = 'statelist';
        $this->tplmodule = 'base';
        $this->firstline = xarML('Please select');
        if (!isset($this->xv_countries) || empty($this->xv_countries)) $this->xv_countries = $this->defaultcountries;
        $this->options = $this->getOptions();
    }

   /**
    * State list options
    * Updated inline with changes in Country List property
    */

   public function getOptions()
   {
        $options = array();
        $countrylist= isset($this->xv_countries)&&!empty($this->xv_countries)?$this->xv_countries:'';
        //need alias for gb - uk
        //if we have greece we'll need it for that too but not atm
        //the rest are standard (so far)
        $match = preg_match('/uk/',$countrylist,$matches);
        if ($matches) {
            $countrylist= str_replace('uk','gb',$countrylist);
        }
        $countries = explode(',',$countrylist);
        foreach ($countries as $country) {
            try {
                sys::import('modules.base.xardata.countrystates.' . $country);
                $temp = 'getOptions_'.$country;

                if(function_exists($temp)) {
                    $options = array_merge($options,$temp());
                } else {
                    //that function doesn't exist, let's leave message and pass gracefully to next
                     xarLogMessage("PROPERTIES: Statelist - called $country but no options list for that country");
                    break;
                }
            } catch (Exception $e) {
                xarLogMessage('PROPERTIES: Statelist - problem importing state options');
            }
        }
        $options[] = array('id' =>'Other', 'name' =>'Other');
        return $options;
    }
    /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

            $parentvals = parent::getBaseValidationInfo();
            $validationarray = array('xv_countries'    =>  array('label'=>xarML('Countries'),
                                                            'description'=>xarML('Comma separated list of countries for state/province display.'),
                                                            'propertyname'=>'textbox',
                                                            'ignore_empty'  =>1,
                                                            'ctype'=>'definition',
                                                            'configinfo'=> xarML('Comma separated list eg: au,us,ca')
                                                          ),

                                );
            $validationarray = array_merge($parentvals,$validationarray);
        }
        return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @return array Base information for this property
     **/
     public function getBasePropertyInfo()
     {
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 43,
                              'name'       => 'statelisting',
                              'label'      => 'Dropdown list - States',
                              'format'     => '43',
                              'validation' => serialize($validations),
                              'source'     => '',
                              'filepath'    => 'modules/base/xarproperties',
                              'dependancies' => '',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }
}
?>
