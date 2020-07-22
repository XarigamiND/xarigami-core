<?php
/**
 * Dynamic Textbox Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/* Include parent class */
sys::import('modules.dynamicdata.class.properties');

/**
 * handle the textbox property
 *
 * @package dynamicdata
 */
class Dynamic_TextBox_Property extends Dynamic_Property
{
    public $id          = 2;
    public $name        = 'textbox';
    public $desc        = 'Text Box';
    public $layout      = 'default';
    public $tplmodule   = 'base';
    public $module      = 'base';

    public $xv_size         = 50;   //Default number of characters visible in the input box
    public $xv_maxlength    = 254;  //default maximum length of string when no max_length
    public $xv_min_length   = null;  //minimum length of string
    public $xv_max_length   = null;  //maximum length of string
    public $xv_pattern      = null;
    public $xv_layout       = 'default';
    public $xv_placeholder  = null;
    public $xv_help         = '';
    public $xv_autocomplete = false; //turn off autocomplete
    public $xv_classname        = null;
    public $style           = null;
    public $xv_disabled     = FALSE;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule    = 'base';
        $this->template     = 'textbox';
        $this->filepath     = 'modules/base/xarproperties';
    }

    // checkInput($name='', $value = null) in parent

    /**
     * Validate the value entered
     * @return bool true on successfull validation
     */
    function validateValue($value = null)
    {

        if (!parent::validateValue($value)) return false;
        if (!isset($value)) {
            $value = $this->value; //default
        } elseif (is_array($value)) {
            $value = serialize($value);
        }
        $isvalid = true;
        if (isset($this->label) && !empty($this->label)) {
            $namelabel = $this->label;
        } else {
            $namelabel = $this->name;
        }

        if (!isset($this->xv_max_length)||empty($xv_max_length)|| is_null($xv_max_length)) $this->xv_max_length= $this->xv_maxlength;

        if (!empty($value) && strlen($value) > $this->xv_max_length) {
            $this->invalid = xarML("Invalid '#(1)' #(2) must be less than #(3) characters long", $namelabel,$this->desc,$this->xv_max_length + 1);
            //$this->value = null;
            $isvalid = false;
        } elseif (isset($this->xv_min_length) && strlen($value) < $this->xv_min_length) {
            $this->invalid = xarML("Invalid '#(1)'  #(2) must be at least #(3) characters long", $namelabel,$this->desc,$this->xv_min_length);
          //  $this->value = null;
             $isvalid = false;
        } elseif (!empty($this->xv_pattern) && !preg_match($this->xv_pattern, $value)) {
            $this->invalid = xarML("Invalid '#(1)' #(2) does not match the required pattern", $namelabel, $this->desc);
           // $this->value = null;
             $isvalid = false;
        } else {

            $this->value = $value;
             $isvalid = true;
        }
        return $isvalid;
    }

    /**
     * Show the input form
     */
    function showInput(Array $data = array())
    {
        extract($data);

        if (empty($maxlength) && isset($max_length)) {
            $this->xv_maxlength = $this->xv_max_length;
            if ($this->xv_size > $this->xv_maxlength) {
                $this->xv_size = $this->xv_maxlength;
            }
        }
        //have retained some older names vars for template compatibility
        $data['value']      = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['maxlength']  = !empty($maxlength) ? $maxlength : $this->xv_maxlength;
        $data['size']       = !empty($size) ? $size : $this->xv_size;
        $data['class']      = !empty($class) ? $class : $this->xv_classname;
        $data['autocomplete'] = isset($autocomplete) ? $autocomplete : $this->xv_autocomplete;
        $data['placeholder'] = isset($placeholder) ? $placeholder : $this->xv_placeholder;
        $data['pattern']    = isset($pattern) ? $pattern: $this->xv_pattern;
        $data['tplmodule']  = !isset($tplmodule) ? $this->tplmodule : $tplmodule;
         $data['help']  = isset($help) ? $help : $this->xv_help;

       if (empty($template)) $template = $this->template;
        $data['template'] = $template;

        return parent::showInput($data);
    }
    function showOutput(Array $data = array())
    {
        extract($data);
        if (isset($value)) {
            $value=xarVarPrepHTMLDisplay($value);
        } else {
            $value=xarVarPrepHTMLDisplay($this->value);
        }
        $data['value'] = $value;

        if (empty($template)) {
            $template = $this->template;
        }
        $data['template'] = $template;

        return parent::showOutput($data);
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
            $validations= array(   'xv_min_length'    =>  array('label'=>xarML('Minimum length'),
                                                          'description'=>xarML('Minimum required length of this field'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'validation'
                                                          ),
                                        'xv_max_length'    =>  array('label'=>xarML('Maximum length'),
                                                          'description'=>xarML('Maximum required length of this field'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'validation'
                                                           ),
                                        'xv_size'          =>  array('label'=>xarML('Maximum input display'),
                                                          'description'=>xarML('Maximum number of characters visible in the input box'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'display',
                                                          ),
                                        'xv_pattern'       =>  array('label'=>xarML('Pattern for input'),
                                                          'description'=>xarML('A PCRE regex pattern that the field contents must match'),
                                                          'propertyname'=>'textbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'validation',
                                                            'configinfo'    => xarML('e.g. /^[A-Z]+\d*$/')
                                                          ),
                                        'xv_help' => array('label'=>xarML('Pattern instructions'),
                                                            'description'=>xarML('Instruction to the user for pattern input'),
                                                            'propertyname'=>'textbox',
                                                            'propargs' => array('size'=>60),
                                                            'ignore_empty'=>1,
                                                            'ctype'=>'validation'
                                                            ),
                                        'xv_placeholder'   =>  array('label'=>xarML('Placeholder text'),
                                                          'description'=>xarML('Text displayed in input field to help user'),
                                                          'propertyname'=>'textbox',
                                                           'ignore_empty'  =>1,

                                                           'ctype'=>'display'
                                                          ),
                                        'xv_autocomplete'       =>  array('label'=>xarML('Autocomplete off?'),
                                                          'description'=>xarML('Turn off autocomplete'),
                                                          'propertyname'=>'checkbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'display'
                                                          ),

                                        'xv_isunique'      =>  array('label'=>xarML('Unique?'),
                                                          'description'=>xarML('Must be a unique value'),
                                                          'propertyname'=>'checkbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'validation',
                                                           'configinfo'    => xarML('[Object and hooked DD properties only]')
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
     * @return base information for this property
    **/
    function getBasePropertyInfo()
    {
        $args = array();

        $validation = $this->getBaseValidationInfo();

        $baseInfo = array(
            'id'            => 2,
            'name'          => 'textbox',
            'label'         => 'Text box',
            'format'        => '2',
            'validation'    =>  serialize($validation),
            'source'        => '',
            'dependancies'  => '',
            'filepath'    => 'modules/base/xarproperties',
            'requiresmodule'=> 'base',
            'aliases'       => '',
            'args'          => serialize($args),
        );
        return $baseInfo;
    }

}

?>
