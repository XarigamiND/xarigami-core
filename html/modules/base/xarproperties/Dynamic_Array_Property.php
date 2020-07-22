<?php
/**
 * Dynamic Array Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

if (!class_exists('Dynamic_Property_Master') || !class_exists('Dynamic_Property')) sys::import('modules.dynamicdata.class.properties');
/**
 * Property to show an array
 *
 * @package modules
 */
class Dynamic_Array_Property extends Dynamic_Property
{
    public $id         = 999;
    public $name       = 'array';

    public $fields               = array();
    public $size                 = 20;
    public $xv_columns           = 1;
    public $xv_columns_count     = 1;
    public $xv_rows              = 2;
    public $xv_max_rows          = 10;
    public $xv_key_label         = 'Key';
    public $xv_value_label       = 'Name';
    public $xv_suffix_label      = 'Row';
    public $xv_addremove         = 0;
    public $xv_associative_array = 1;
    public $xv_fixed_keys        = 0;
    public $xv_prop_type        = 2; //textbox
    //public $xv_prop_type        = 'textbox';
    public $xv_prop_config = '';
    function __construct($args)
    {
        extract($args);
        parent::__construct($args);
        $this->filepath  = 'modules/base/xarproperties';
        $this->tplmodule = 'base';
        $this->template = 'array';

    }

    function checkInput($name='', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        //$name = 'dd_'.$this->id ;;
        // store the fieldname for validations that need it (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value) || empty($value)) {


            if (!xarVarFetch($name . '_key', 'array', $keys, array(), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch($name . '_value',   'array', $values, array(), XARVAR_NOT_REQUIRED)) return;

            //Check for an associative_array.
            if (!xarVarFetch($name . '_associative_array',   'int', $associative_array, null, XARVAR_NOT_REQUIRED)) return;
            //Set value to the initialization_associative_array
            $this->xv_associative_array = $associative_array;

            // check if we have a specific property for the values
            if (!xarVarFetch($name . '_has_property', 'isset', $has_property, null, XARVAR_NOT_REQUIRED)) return;
            if (!empty($has_property)) {
                $property = $this->getValueProperty();
            }
            if (!empty($property)) {
                $value = array();
                foreach ($keys as $idx => $key) {
                    if (empty($key)) continue;
                    $fieldname = $name . '_value_' . $idx;
                    $isvalid = $property->checkInput($fieldname);
                    if ($isvalid) {
                        $value[$key] = $property->getDisplayValue();
                    } else {
                        $this->invalid .= $key . ': ' . $property->invalid;
                    }
                }
            } else {
                $hasvalues = FALSE;
                while (count($keys)) {
                    try {
                        $thiskey = array_shift($keys);
                        $thisvalue = array_shift($values);
                        if (empty($thiskey) && empty($thisvalue)) continue;
                        if ($this->xv_associative_array == 1 && empty($thiskey)) continue;
                        if (is_array($thisvalue) && count($thisvalue) == 1) {
                            $value[$thiskey] = current($thisvalue);
                        } else {
                            $value[$thiskey] = $thisvalue;
                        }
                        $hasvalues = TRUE;
                    } catch (Exception $e) {}
                }
                if (!$hasvalues) $value = array();
            }
        }

        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return FALSE;
        if (!is_array($value)) {
            $this->value = NULL;
            return FALSE;
        }

        $this->setValue($value);

        return TRUE;
    }

    function setValue($value=null)
    {
        if (!empty($value) && !is_array($value)) {
            $this->value = $value;
        } else {
            if (empty($value)) $value = array();
            $this->value = serialize($value);
        }


    }
    /*
     * Get value used in display
     */
    public function getDisplayValue()
    {
        try {
            $value = @unserialize($this->value);
        } catch(Exception $e) {
            $value = null;
        }

        return $value;
    }
    public function getValue()
    {   //jojo - this will create problems in object creation
        //return $this->getDisplayValue();
        return $this->value;
    }

    public function showInput(Array $data = array())
    {
        extract($data);

        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        if (!is_array($value)) {
            try {
                $value = @unserialize($value);

            } catch (Exception $e) {
                $value = array();
            }
        }
        // Allow overriding of the field keys from the template
        if (isset($data['fields'])) $this->fields = $data['fields'];
        if (count($this->fields) > 0) {
            $fieldlist = $this->fields;
        } elseif (is_array($value)) {
            $fieldlist = array_keys($value);
        } else{
              $fieldlist = array();
        }

        // check if we have a specific property for the values
        if (!isset($data['columntype'])) $data['columntype'] = $this->xv_prop_type;
        if (!isset($data['valueconfig'])) $data['valueconfig'] = $this->xv_prop_config;
        $data['property'] = $this->getValueProperty($data['columntype'], $data['valueconfig']);

        // use a different default template when dealing with properties
        if (empty($data['template']) && !empty($data['property'])) {
            $data['template'] = 'arrayprops';
        }

        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } elseif (is_array($value[$field])) {
                foreach($value[$field] as $k => $v){
                    $data['value'][$field][$k] = xarVarPrepForDisplay($v);
                }
            } else {
                // CHECKME: skip this for array of properties ?
                if (!empty($data['template']) && $data['template'] == 'arrayprops') {
                    $data['value'][$field] = $value[$field];
                } else {
                    $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
                }
            }
        }

        if (!isset($data['rows'])) $data['rows'] = $this->xv_rows;
        if (!isset($data['size'])) $data['size'] = $this->size;
        if (!isset($data['columns'])) $data['columns'] = $this->xv_columns;
        $data['keylabel'] = !isset($keylabel)?$this->xv_key_label: $keylabel;
        $data['valuelabel'] = !isset($valuelabel)?$this->xv_value_label: $valuelabel;
        $data['valuelabel'] = explode(';', $data['valuelabel']);

        if (!isset($data['proptype'])) $data['proptype'] = $this->xv_prop_type;
        if (!isset($data['allowinput'])) $data['allowinput'] = $this->xv_addremove;
        if (!isset($data['associative_array'])) $data['associative_array'] = isset($this->xv_associative_array)?$this->xv_associative_array:0;
        if (!isset($data['fixedkeys'])) $data['fixedkeys'] = $this->xv_fixed_keys;
        $data['numberofrows'] = count($data['value']);
        $data['suffixlabel'] = !isset($suffixlabel)?$this->xv_suffix_label: $suffixlabel;
        $data['maxrows'] = isset($maxrows) ?$maxrows: $this->xv_max_rows;
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['columns'])) $data['columns'] = $this->xv_columns;

        $value = isset($data['value']) ? $data['value'] : $this->getDisplayValue();
        $data['associative_array'] = isset($associative_array) ? $associative_array : $this->xv_associative_array;

        if (!is_array($value)) {
        //this is added to show the value with new line when storage is non-associative
            try {
                    $check = @unserialize($value);
                } catch (Exception $e) {
                    //do nothing
                }
            $serialized =  ($check === FALSE) && ($value != serialize(FALSE)) ? FALSE : TRUE;
            if ($serialized) {
                 $data['value'] = unserialize($value);
            } else {
                $data['value'] = NULL;
                //jojo this should not be happening
               // throw new Exception("Did not find a correct array value");
                // $data['value'] = $value;
            }
        } else {
            //if (empty($value)) $value = NULL;

            if (count($this->fields) > 0) {
                $fieldlist = $this->fields;
            } else {
                $fieldlist = array_keys($value);
            }

            $data['value'] = array();

            foreach ($fieldlist as $field) {
                if (!isset($value[$field])) {
                    $data['value'][$field] = '';
                } else {
                    $data['value'][$field] = $value[$field];
                }
            }
        }
        // check if we have a specific property for the values
        if (!isset($data['valuetype'])) $data['valuetype'] = $this->xv_prop_type;
        if (!isset($data['valueconfig'])) $data['valueconfig'] = $this->xv_prop_config;
        $data['property'] = $this->getValueProperty($data['valuetype'], $data['valueconfig']);

        // use a different default template when dealing with properties
        if (empty($data['template']) && !empty($data['property'])) {
            $data['template'] = 'array_of_props';
        }
        return parent::showOutput($data);
    }

    function &getValueProperty($valuetype = '', $valueconfig = '')
    {
        if (empty($valuetype)) {
            $valuetype = $this->xv_prop_type;
        } else {
            $this->xv_prop_type = $valuetype;
        }
        if (empty($valueconfig)) {
            $valueconfig = $this->xv_prop_config;
        } else {
            $this->xv_prop_config = $valueconfig;
        }
        if (empty($this->xv_prop_type)) {
            $property = null;
        } elseif ($this->xv_prop_type == '2' && empty($this->xv_prop_config)) {
            $property = null;
        } else {
            $property = Dynamic_Property_Master::getProperty(array('type' => $this->xv_prop_type));
            if (!empty($this->xv_prop_config)) {
                $property->parseValidation($this->xv_prop_config);
            }
        }
        return $property;
    }

    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
             $parentvalidation = parent::getBaseValidationInfo();

            $adddleteoptions = array(0 => xarML('No addition/deletion'),
                                     1 => xarML('Add only'),
                                     2 => xarML('Add and delete rows')
                        );
            $validations = array('xv_columns'    =>     array('label'=>xarML('Columns'),
                                                                    'description'=>xarML('Number of columns'),
                                                                    'propertyname'=>'integerbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition'
                                                                  ),

                                    'xv_rows'       =>      array('label'=>xarML('Rows'),
                                                                    'description'=>xarML('Number of rows'),
                                                                    'propertyname'=>'integerbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition'
                                                                     ),
                                    'xv_max_rows'       =>      array('label'=>xarML('Maximum rows'),
                                                                    'description'=>xarML('Maximum number of rows'),
                                                                    'propertyname'=>'integerbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition'
                                                                     ),
                                    'xv_prop_type'    =>     array('label'=>xarML('Row types'),
                                                                    'description'=>xarML('Row item types'),
                                                                    'propertyname'=>'fieldtype',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition',
                                                                     ),
                                    'xv_key_label'    =>     array('label'=>xarML('Key label'),
                                                                    'description'=>xarML('Label for the Key column'),
                                                                    'propertyname'=>'textbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition',
                                                                     ),
                                    'xv_value_label'    =>     array('label'=>xarML('Value label'),
                                                                    'description'=>xarML('Label for the value column, or multiple column labels separated by semicolons(;)'),
                                                                    'propertyname'=>'textbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition',
                                                                    'configinfo' => xarML('Separate multiple column labels by semicolons (;)')
                                                                     ),
                                    'xv_suffix_label'    =>     array('label'=>xarML('Row label'),
                                                                    'description'=>xarML('Label for the row name'),
                                                                    'propertyname'=>'textbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition',
                                                                     ),
                                    'xv_associative_array'  =>  array('label'=>xarML('Associative array?'),
                                                                    'description'   =>xarML('Array should have index and value options'),
                                                                    'propertyname'  =>'checkbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'         =>'definition',
                                                                     ),
                                    'xv_fixed_keys'  =>  array('label'=>xarML('Prevent key editing?'),
                                                                    'description'   =>xarML('Keys cannot be edited'),
                                                                    'propertyname'  =>'checkbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'         =>'definition',
                                                                     ),

                                    'xv_addremove'   =>     array('label'=>xarML('Item editing'),
                                                                    'description'=>xarML('Access to add or delete items'),
                                                                    'propertyname'=>'dropdown',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'definition',
                                                                    'propargs'  =>array('options'=> $adddleteoptions),
                                                                     )
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
                           'id'         => 999,
                           'name'       => 'array',
                           'label'      => 'Array',
                           'format'     => '999',
                           'validation' => serialize($validations),
                           'source'     => $this->source,
                           'dependancies' => '',
                           'filepath'    => 'modules/base/xarproperties',
                           'requiresmodule' => 'base',
                           'aliases'    => '',
                           'args'       => serialize($args),
                           // ...
                          );
        return $baseInfo;
     }
}

?>
