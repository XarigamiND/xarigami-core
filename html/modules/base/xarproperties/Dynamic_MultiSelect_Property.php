<?php
/**
 * Multiselect Property
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
 * handle the multiselect property
 * @author mikespub <mikespub@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_MultiSelect_Property extends Dynamic_Select_Property
{
    public $id         = 39;
    public $name       = 'multiselect';
    public $desc       = 'Multiselect';

    public $xv_single = false;
    public $xv_displaydelimiter = ',';
    public $xv_size = null;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template =  'multiselect';
        //$this->xv_displaydelimiter = ',';
    }

    public function validateValue($value = null)
    {

        $value = $this->getSerializedValue($value);
        $validlist = array();
        $options = $this->getOptions();
        foreach ($options as $option) {
            array_push($validlist,$option['id']);
        }
        foreach ($value as $val) {
            if (!in_array($val,$validlist)) {
                $this->invalid = xarML('selection: #(1)', $this->name);
                $this->value = null;
                return false;
            }
        }
        $this->value = serialize($value);
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['single'])) $this->xv_single = $data['single'];
        if (isset($data['allowempty'])) $this->xv_allowempty = $data['allowempty'];
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = $this->getSerializedValue($data['value']);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;

        $data['value'] = $this->getSerializedValue($data['value']);
        if (!isset($data['options'])) $data['options'] = $this->getOptions();
        if (!isset($data['template'])) $data['template']= $this->template;
        return parent::showOutput($data);
    }

    public function getValue()
    {
        //return $this->getSerializedValue($this->value);
        return $this->value;
    }

    public function getItemValue($itemid)
    {

        return $this->getSerializedValue($this->_items[$itemid][$this->name]);

    }
    public function getSerializedValue($value)
    {
        if (empty($value)) {
            return array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        // return array
        return $value;
    }
    public function getBaseValidationInfo()
    {
        static $validationarray = array();

        if (empty($validationarray)) {
            $parentvalidations = parent::getBaseValidationInfo();

            $validations = array('xv_single'    =>  array('label'=>xarML('Single'),
                                                                    'description'=>xarML('Single select'),
                                                                    'propertyname'=>'checkbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition'
                                                                     ),
                            'xv_displaydelimiter'   =>  array('label'=>xarML('Delimiter'),
                                                                    'description'=>xarML('Character to use for separation of options in display'),
                                                                    'propertyname'=>'textbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'display'
                                                                     )
                                    );
            $validationarray = array_merge($parentvalidations,$validations);
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
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 39,
                            'name'       => 'multiselect',
                            'label'      => 'Multi select',
                            'format'     => '39',
                            'validation' => serialize($validations),
                            'source'     => '',
                            'dependancies' => '',
                            'filepath'    => 'modules/base/xarproperties',
                            'requiresmodule' => 'base',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }


}
?>