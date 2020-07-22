<?php
/**
 * Numberlist property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * @author mikespub <mikespub@xaraya.com>
*/
/* linoj: validation can also be max:min for descending list */

sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * handle the numberlist property
 *
 * @package dynamicdata
 */
class Dynamic_NumberList_Property extends Dynamic_Select_Property
{
    public $id         = 16;
    public $name       = 'integerlist';
    public $desc       = 'Number List';
    public $xv_min = null;
    public $xv_max = null;
    public $order = 'asc';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->xv_firstline = xarML('Please select');

    }

  /**
     * Validate the input value to be of numeric type
     * @return bool true if value is numeric
     */
    public function validateValue($value = null)
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
            $value = (int)$value;
            if (isset($this->xv_min) && isset($this->xv_max) && ($this->xv_min > $value || $this->xv_max < $value)) {
                $this->invalid = xarML('integer : allowed range is between #(1) and #(2)',$this->xv_min,$this->xv_max);
                $this->value = null;
                return false;
            } elseif (isset($this->xv_min) && $this->xv_min > $value) {
                $this->invalid = xarML('integer : must be #(1) or more',$this->xv_min);
                $this->value = null;
                return false;
            } elseif (isset($this->xv_max) && $this->xv_max < $value) {
                $this->invalid = xarML('integer : must be #(1) or less',$this->xv_max);
                $this->value = null;
                return false;
            }
            $this->value = $value;
        } else {
            $this->invalid = xarML('integer: #(1)', $this->name);
            $this->value = null;
            return false;
        }
        return true;
    }

    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

            $parentinfo = parent::getBaseValidationInfo();

            $validations = array(
                                'xv_min' =>  array('label'=>xarML('Minimum value'),
                                      'description'=>xarML('Minimum required value for this field'),
                                      'propertyname'=>'integerbox',
                                       'ignore_empty'  =>1
                                      ),
                                'xv_max'  =>  array('label'=>xarML('Maximum value'),
                                      'description'=>xarML('Maximum required value for this field'),
                                       'propertyname'=>'integerbox',
                                        'ignore_empty'  =>1
                                       ),
                                );

            $validationarray = array_merge($parentinfo,$validations);
        }
        return $validationarray;
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     public function getBasePropertyInfo()
     {
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 16,
                            'name'       => 'integerlist',
                            'label'      => 'Number list',
                            'format'     => '16',
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
