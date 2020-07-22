<?php
/**
 * Float box property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
sys::import('modules.base.xarproperties.Dynamic_NumberBox_Property');
/**
 * Class to handle floatbox property
 *
 * @package modules
 * @subpackage Base module
 * @author mikespub <mikespub@xaraya.com>
 */
class Dynamic_FloatBox_Property extends Dynamic_NumberBox_Property
{
    public $id          = 17;
    public $name        = 'floatbox';
    public $desc        = 'Number Box (float)';

    public $xv_size        = 10;
    public $xv_maxlength   = 30;
    public $default     = 0;

    // Precision of number, decimal places.
    // A negative precision works to the left of the decimal point.
    public $xv_precision = NULL;

     // Grouping separator character (eg ',' or ' ')
    public $xv_grouping_sep = NULL;

    // Decimal character (eg '.')
    public $xv_decimal_sep = NULL;

    // Prefix and suffix character to add to the number.
    public $xv_number_prefix = NULL;
    public $xv_number_suffix = NULL;

    // Always show decimals, regardless of precision.
    public $always_show_decimal = false;

    // Trim trailing zeros of decimals.
    public $trim_decimals = NULL;

    //constructor
    function __construct($data)
    {
        parent::__construct($data);
        if (($this->value == '') && $this->xv_allowempty !=1) $this->value = $this->default;
       // if (!is_numeric($this->value) && !empty($this->value)) throw new Exception(xarML('The default value of a #(1) must be numeric',$this->name));

    }
    function _locale2array($data)
    {
        $return = array();
        $sub = array();

        foreach($data as $key => $value) {
            $key = trim($key, '/');
            if (strpos($key, '/') === false) {
                $return[$key] = $value;
            } else {
                $key_arr = explode('/', $key, 2);
                $sub[$key_arr[0]][$key_arr[1]] = $value;
            }
        }

        if (!empty($sub)) {
            foreach($sub as $key2 => $sub2) {
                $return[$key2] = $this->_locale2array($sub2);
            }
        }

        return $return;
    }

    /**
     * Validate the value for this property
     * @return bool true when validated, false when not validated
     */
    function validateValue($value = null)
    {
        //we do not want to validate as an integer (parent) or from any ancestors
        //do the validations here

        if (!isset($value)) $value = $this->value;
        //we need to do the usual parent validations ourselves
         $thename = !empty($this->label)?$this->label:$this->name;
        if (isset($this->xv_notequal)  && $value == $this->xv_notequal) {
            if (!empty($this->xv_notequals_invalid)) {
                $this->invalid = xarML($this->xv_notequal_invalid);
            } else {
            $thevalue = $this->value;
            $thevalue = $this->showOutput(array($thevalue));
                $this->invalid = xarML("The value of '#(2)' is not allowed for '#(1)'", $thename,$thevalue );
            }
            //$this->value = null; - retain for display so user can see what they typed
             $isvalid = false;
        } elseif ($this->xv_allowempty != 1 && $value=='') {

            if (!empty($this->xv_allowempty_invalid)) {
                $this->invalid = xarML($this->xv_allowempty_invalid);
            } else {
                $this->invalid = xarML('#(1) cannot be empty.', $thename);
            }
            $this->value = null;
            $isvalid = false;
        } elseif (isset($this->xv_isunique) && ($this->xv_isunique ==1)) {
            if (!isset($this->objectref))  {
                //probably some faux property - eg Articles title, just return for now until we decide how to handle it
            } else {
                $isunique = $this->checkUnique($this->name,$value);
                if ($isunique === FALSE ) {
                    $this->invalid = xarML('#(1) already exists but must be unique.', $thename);
                }
            }
        }

        if ($value === '' && $this->xv_allowempty ==1) {
            //we allow ''
        } else {
            if (!isset($value) ) {
                if (isset($this->xv_min)) {
                    $this->value = $this->xv_min;
                } elseif (isset($this->xv_max)) {
                    $this->value = $this->xv_max;
                } else {
                    $this->value = 0;
                }
            }

            // Strip out prefix or suffix symbol, e.g. '$' in '$100' or '%' in '0.2%'
            if (!empty($this->number_prefix)) {
                $value = preg_replace('/^' . preg_quote($this->number_prefix, '/') . '/', '', $value);
            }

            if (!empty($this->number_suffix)) {
                $value = preg_replace('/' . preg_quote($this->number_suffix, '/') . '$/', '', $value);
            }

            // Strip out separators, e.g. ',' in '100,000'
            if (!empty($this->xv_grouping_sep)) {
                $value = preg_replace('/' . preg_quote($this->xv_grouping_sep) . '/', '', $value);
            }

            // Convert the decimal separator to a '.'
            if (!empty($this->xv_decimal_sep) && $this->xv_decimal_sep != '.') {
                $value = str_replace($this->xv_decimal_sep, '.', $value);
            }

            // Now we should have a number.
            if (!empty($value) && !is_numeric($value)) {
                $this->invalid = xarML('- value must be numeric');
                // Return the value for correction, even if it is not a valid number.
                $this->value = $value;
                return false;
            }

            // Check the precision, and round up/down as required.
            if (isset($this->xv_precision) && is_numeric($this->xv_precision)) {
                $value = round($value, $this->xv_precision);
            }
            //cast to float
            $this->value = (float)$value;

            if (isset($this->xv_min) && isset($this->xv_max) && ($this->xv_min > $value || $this->xv_max < $value)) {
                $this->invalid = xarML('value - must be between #(1) and #(2)',
                    $this->_format_number($this->xv_min), $this->_format_number($this->xv_max)
                );
                return false;
            } elseif (isset($this->xv_min) && $this->xv_min > $value) {
                $this->invalid = xarML('value - must be no less than #(1)', $this->_format_number($this->xv_min));
                return false;
            } elseif (isset($this->xv_max) && $this->xv_max < $value) {
                $this->invalid = xarML('value - must be no greater than #(1)', $this->_format_number($this->xv_max));
                return false;
            }
            // normallly done in text box ...
            if (!isset($this->xv_max_length) || empty($this->xv_max_length)) $this->xv_max_length= $this->xv_maxlength;
            if (!empty($value) && strlen($value) > $this->xv_max_length) {
               $this->invalid = xarML("'#(1)' #(2) must be less than #(3) characters long", $thename,$this->desc,$this->xv_max_length + 1);
                $isvalid = false;
            } elseif (isset($this->xv_min_length) && strlen($value) < $this->xv_min_length) {
                $this->invalid = xarML("'#(1)'  #(2) must be at least #(3) characters long", $thename,$this->desc,$this->xv_min_length);
                 $isvalid = false;
            } elseif (!empty($this->xv_pattern) && !preg_match($this->xv_pattern, $value)) {
                $this->invalid = xarML("'#(1)' #(2) does not match the required pattern",$thename, $this->desc);
                 $isvalid = false;
            }
        }

        return true;
    }


    /**
     * Show the output for the float property
     * @return mixed info for the template
     */
    function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;
        $value = xarVarPrepForDisplay($this->_format_number($value));
         // Add on any prefix or suffix (e.g. $ or %) for display only
        if (!empty($this->xv_number_prefix) && !empty($value)) $value = $this->xv_number_prefix . $value;
        if (!empty($this->xv_number_suffix) && !empty($value)) $value = $value . $this->xv_number_suffix;
        // TODO: prep the display in the template
        $data['value']= $value;

        $data['template'] = isset($template)?$template:'integerbox';
        return parent::showOutput($data);
    }

    function showInput(Array $data = array())
    {
        extract($data);

        $this->value = $this->_format_number($this->value);
        //Do not equate maxlength with max value for numberbox - Issue xgami-000130
        if (empty($maxlength)) {
            $this->xv_maxlength = $this->xv_size;
        }

        if (empty($name)) $name = 'dd_' . $this->id;
        if (empty($id)) $id = $name;

       // $data['name']     = $name;
       // $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->xv_maxlength;
        $data['size']     = !empty($size) ? $size : $this->xv_size;
        $data['class']     = !empty($class) ? $class : $this->class; //until we get inheritance going for all this
        $data['onfocus']  = isset($onfocus) ? $onfocus : null; // let tpl decide what to do with it
        $data['tplmodule']  = !isset($tplmodule) ? $this->tplmodule : $tplmodule;
        if (empty($template)) $template = '';
        $data['template'] = $template;
        return parent::showInput($data);


    }

    /**
     * Format the numeric value into a string.
     */
    function _format_number($value)
    {
        // Only attempt to format a numeric value.
        // Return if we don't have one.
        if (!is_numeric($value)) return $value;

        // If the precision is 0 or less, and we are not forced to include the decimal,
        // then make the decimal part optional, otherwise pad it out to at least min-length
        // with zeros.
        // Precision can be negative.
        if (isset($this->xv_precision) && isset($this->xv_decimal_sep) && isset($this->xv_grouping_sep)) {
            $value = number_format($value, $this->xv_precision, $this->xv_decimal_sep, $this->xv_grouping_sep);

            // Strip the decimals if required.
            // Using a preg_match seems clumsy, but gets the job done.
            if ($this->xv_precision <= 0 && empty($this->always_show_decimal) && $this->decimal_sep != '') {
                $value = preg_replace('/' . preg_quote($this->decimal_sep) . '.*/', '', $value);
            }
        } elseif (isset($this->number_format)) {
            // TODO: support a more generic and flexible number format string
        }

        if (!empty($this->trim_decimals)) {
            // Trailing zeros after the decimal are to be trimmed.
            $decimal_point = (isset($this->decimal_sep) ? $this->decimal_sep : '.');
            if (strpos($value, $decimal_point) !== false) $value = trim($value, '0' . $decimal_point);
        }

        // Add on any prefix or suffix (e.g. $ or %). DISPLAY ONLY
       /// if (!empty($this->xv_number_prefix)) $value = $this->xv_number_prefix . $value;
      //  if (!empty($this->xv_number_suffix)) $value = $value . $this->xv_number_suffix;

        return $value;
    }
    function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

             $parentvals = parent::getBaseValidationInfo();
             $validations = array(
                                'xv_precision' =>  array('label'=>xarML('Precision'),
                                                  'description'=>xarML('Precision of number - significant decimal places'),
                                                  'propertyname'=>'integerbox',
                                                  'ignore_empty'  =>1,
                                                  'ctype'=>'definition'
                                      ),
                                'xv_number_suffix' =>  array('label'=>xarML('Number suffix'),
                                                  'description'=>xarML('Text shown after the number on display'),
                                                  'propertyname'=>'textbox',
                                                  'ignore_empty'  =>1,
                                                  'ctype'=>'display'
                                      ),
                                'xv_number_prefix' =>  array('label'=>xarML('Number prefix'),
                                                  'description'=>xarML('Text shown before the number on display'),
                                                  'propertyname'=>'textbox',
                                                  'ignore_empty'  =>1,
                                                  'ctype'=>'display'
                                      ),
                                'xv_decimal_sep' =>  array('label'=>xarML('Decimal separator'),
                                                  'description'=>xarML('Character used to denote decimals'),
                                                  'propertyname'=>'textbox',
                                                  'ignore_empty'  =>1,
                                                  'propargs'    =>array('size'=>1,'maxlength'=>1),
                                                  'ctype'=>'display',
                                                   'configinfo'    => xarML('Must be entered to display decimal places')
                                      ),
                                'xv_grouping_sep' =>  array('label'=>xarML('Group separator'),
                                                  'description'=>xarML('Character used to group number position'),
                                                  'propertyname'=>'textbox',
                                                  'ignore_empty'  =>1,
                                                   'propargs'    =>array('size'=>1,'maxlength'=>1),
                                                  'ctype'=>'display'
                                      ),
                                'xv_max_length'    =>  array('label'=>xarML('Maximum length'),
                                      'description'=>xarML('Maximum required length of this field'),
                                      'propertyname'=>'integerbox',
                                      'ignore_empty'  =>1,
                                      'ctype'=>'validation',
                                      'configinfo'    => xarML('Using with decimals may give unexpected results. Use min and max values with precision.')
                                       ),
                                //redefine to allow float values
                                'xv_min' =>  array(  'label'         => xarML('Minimum value'),
                                                            'description'   => xarML('Minimum required value for this field'),
                                                            'propertyname'  => 'floatbox',
                                                            'ignore_empty'  => 1,
                                                             'ctype'         => 'validation'
                                      ),
                                        'xv_max'  =>  array('label'         => xarML('Maximum value'),
                                                            'description'   => xarML('Maximum required value for this field'),
                                                            'propertyname'  =>  'floatbox',
                                                            'ignore_empty'  => 1,
                                                             'ctype'         => 'validation'
                                       ),
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
        $validation = $this->getBaseValidationInfo();
        $baseInfo = array(
            'id'         => 17,
            'name'       => 'floatbox',
            'label'      => 'Number box - float',
            'format'     => '17',
            'validation' =>  serialize($validation),
            'source'         => '',
            'filepath'    => 'modules/base/xarproperties',
            'dependancies'   => '',
            'requiresmodule' => 'base',
            'aliases'        => '',
            'args'           => serialize($args),
        );
        return $baseInfo;
    }
/*
    function showValidation(Array $args = array())
    {
        // allow template override by child classes
        if (!isset($args['template'])) $args['template'] = 'floatbox';

        return parent::showValidation($args);
    }
*/
}

?>