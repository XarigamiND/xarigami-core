<?php
/**
 * Checkbox Property
 *
 * @package Xaraya modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

sys::import('modules.dynamicdata.class.properties');

/**
 * Class to handle check box property
 * If checkbox has 'checked = checked' it displays as checked
 * Value is the value submitted if the checkbox is checked
 * @package dynamicdata
 */
class Dynamic_Checkbox_Property extends Dynamic_Property
{
    public $id          = 14;
    public $name        = 'checkbox';
    public $desc        = 'Checkbox';
    public $reqmodules  =  'base';
    public $xv_hasvalue    = NULL;
    public $xv_hasnovalue  = NULL;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'checkbox';
        $this->filepath  = 'modules/base/xarproperties';
        $this->xv_hasvalue = xarML('Yes');
        $this->xv_hasnovalue = xarML('No');
    }
    // check validation for allowed values
    function parseValidation($validation='')
    {
        parent::parseValidation($validation);
        //backward compatibility
        if (isset($this->xv_other) && is_string($this->xv_other)) {
            switch (strtolower($this->xv_other) ){
                case 'true':
                case '1':
                    $this->xv_other = TRUE;
                break;
                case 'false':
                case '0':
                    $this->xv_other = FALSE;
                break;
            }
        }
        //check for other values
    }

    function checkInput($name='', $value = NULL)
    {
       $name = empty($name) ? 'dd_'.$this->id : $name;
       if (!isset($value)) {
          $isvalid = TRUE;
            list($found,$value) = $this->fetchValue($name);
        }

        return $this->validateValue($value);
    }

    function validateValue($value = NULL)
    {
        if (!parent::validateValue($value)) return FALSE;
       // we want to try and retain the value not set it to something else! Our forms should be able to cope
       if (!empty($value)) {
            $this->value = $value;
            $this->checked = TRUE;
        } else {
            $this->value = 0;
            $this->checked = FALSE;
        }
        $name = $this->name;
        $label=$this->label;
        //check for alternate values for TRUE /FALSE
        $this->parseValidation($this->validation);

        //backward compatibility v 1.3.5 and prior
        $other = isset($this->xv_other)?$this->xv_other : NULL;
        $requiredstate = $other;
        if ($requiredstate === FALSE || $requiredstate === TRUE) {
            if (($requiredstate == FALSE) && ($this->checked == TRUE)) {
                $this->invalid = xarML("#(1) : this checkbox must be unchecked to continue",$label);
                return FALSE;
            } elseif (( $requiredstate ==TRUE) && ($this->checked != TRUE)) {
                $this->invalid = xarML("#(1) : this checkbox must be checked to continue",$label);
                return FALSE;
            }
        }

        return TRUE;
    }

    function showInput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }

         if (!isset($checked)) {
            //   $checked= isset($value) && !empty($value) ? TRUE :FALSE;
            
            //$this->checked = isset($checked)?$checked: FALSE;
            // Lakys: where is that member var declared and used below? Only use $checked. The code right above can't work.
            $checked = isset($value) && !empty($value) ? $value != 0 : FALSE;
         }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['value'] = $value;
       // $data['name'] = $name;
        $data['label'] = isset($label) ? $label :$this->label;
        //two cases
        //1. explicit use of data-input type="checkbox" where checked is set and some value of value is needed for submission (could be 'foo')
        //2. General data input tag looping through dd where only value="$somevalue" is set
        //If there is no checkbox attribute passed in, then and only then check the value and use it
        $data['checked']  = $checked;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid): '';
        $data['tplmodule']  = !isset($tplmodule) ? $this->tplmodule : $tplmodule;
        $data['template'] = (!isset($template) || empty($template)) ? 'checkbox' : $template;

        return parent::showInput($data);
    }

    function showOutput(Array $data= array())
    {
        extract($data);
        if (isset($validation) && !empty($validation)) $this->validation = $validation;
        //we may be calling showoutput from unbound property so parse the validation in case
        $this->parseValidation($this->validation);

        if (!isset($value)) {
            $value = $this->value;
        }
        $data['value']=$value;
        $data['hasvalue']   = $this->xv_hasvalue;
        $data['hasnovalue'] = $this->xv_hasnovalue;
        $template = (!isset($template) || empty($template)) ? 'checkbox' : $template;
        $data['template']= $template;
        $data['tplmodule']=  (isset($tplmodule) && !empty($tplmodule)) ? $tplmodule : $this->tplmodule;

        return parent::showOutput($data);
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
             $validations= array(
                                'xv_hasvalue' =>  array('label'=>xarML('Checked box value'),
                                                  'description'=>xarML('Text to display on output when this checkbox is checked'),
                                                  'propertyname'=>'textbox',
                                                   'ignore_empty'  =>1
                                                  ),
                                'xv_hasnovalue'  =>  array('label'=>xarML('Unchecked box value'),
                                      'description'=>xarML('Text to display on output when this checkbox is unchecked'),
                                       'propertyname'=>'textbox',
                                        'ignore_empty'  =>1
                                       )
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
                              'id'         => 14,
                              'name'       => 'checkbox',
                              'label'      => 'Checkbox',
                              'format'     => '14',
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