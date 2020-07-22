<?php
/**
 * Number Box Property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
*/
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');

/**
 * handle a numberbox property
 *
 * @package dynamicdata
 */
class Dynamic_NumberBox_Property extends Dynamic_TextBox_Property
{
    public $id      = 15;
    public $name    = 'integerbox';
    public $module  = 'base';
    public $desc    = 'Number Box';
    public $xv_size    = 10;       //display length of input box
    public $xv_min     = null;  //minimum value of number
    public $xv_max     = null;  //maximum value of number
    public $xv_maxlength = 30;     //default maximum size of input characters

    function __construct($args)
    {
      parent::__construct($args);
     // if (!is_numeric($this->value) && !empty($this->value)) throw new Exception(xarML('The default value of a #(1) must be numeric',$this->name));
    }

    /**
     * Validate the input value to be of numeric type
     * @return bool true if value is numeric
     */
    function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;
        if ($this->xv_min == "") $this->xv_min = null;
        if ($this->xv_max == "") $this->xv_max = null;

        //check to see if we use a default
        if (!isset($value) ) {
            if (isset($default)) {
                $this->value = $default;
            } elseif (isset($this->default)) {
                $this->value = $this->default;
            }else {
                $this->value = 0;
                return true;
            }
        //now check to see if it passes validation
       } elseif (isset($value)) {
             $label= isset($this->label)?$this->label:$this->name;
             //empty($value) is handled by parent class and allowed empty sometimes
             //but we need to ensure it passes here if the value is empty
            if ($value == '' && $this->xv_allowempty ==1) {
                //we can continue
            } else {
                if (!preg_match('/^(?:[1-9][0-9]*|0)$/',$value))  {
                    $this->invalid = xarML("'#(1)' has invalid characters and must be an integer",$label);
                    $this->value = null;
                    return false;
                } else {
                    $value = (int)$value;
                }
                if (isset($this->xv_min) && isset($this->xv_max) && ($this->xv_min > $value || $this->xv_max < $value)) {
                    $this->invalid = xarML("'#(1)' : allowed range is between #(2) and #(3)",$label, $this->xv_min,$this->xv_max);
                    //$this->value = null;
                    return false;
                } elseif (isset($this->xv_min) && $this->xv_min > $value) {
                    $this->invalid = xarML("#(1) : must be #(2) or more",$label, $this->xv_min);
                  //  $this->value = null;
                    return false;
                } elseif (isset($this->xv_max) && $this->xv_max < $value) {
                    $this->invalid = xarML("#(1) : must be #(2) or less",$label, $this->xv_max);
                 //   $this->value = null;
                    return false;
                }
            }
            $this->value = $value;
        } else {
            $this->invalid = xarML('number: #(1)', $this->name);
            $this->value = null;
            return false;
        }
        return true;
    }


    // default showOutput() from Dynamic_TextBox_Property
    /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited from a parent class
     */
    function getBaseValidationInfo()
    {
         static $validationarray = array();
        if (empty($validationarray)) {
            $parentvals = parent::getBaseValidationInfo();

             $validations = array(
                                       'xv_min' =>  array(  'label'         => xarML('Minimum value'),
                                                            'description'   => xarML('Minimum required value for this field'),
                                                            'propertyname'  => 'integerbox',
                                                            'ignore_empty'  => 1,
                                                             'ctype'         => 'validation'
                                      ),
                                        'xv_max'  =>  array('label'         => xarML('Maximum value'),
                                                            'description'   => xarML('Maximum required value for this field'),
                                                            'propertyname'  =>  'integerbox',
                                                            'ignore_empty'  => 1,
                                                             'ctype'         => 'validation'
                                       ),

                                );
            $validationarray= array_merge($validations,$parentvals);
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
                          'id'         => 15,
                          'name'       => 'integerbox',
                          'label'      => 'Number box',
                          'format'     => '15',
                          'validation' => serialize($validation),
                          'filepath'    => 'modules/base/xarproperties',
                          'source'     => '',
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
