<?php
/**
 * Dynamic Color Picker property
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * @package modules
 * @subpackage Base module
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');
class Dynamic_ColorPicker_Property extends Dynamic_TextBox_Property
{
    public $id         = 144;
    public $name       = 'colorpicker';
    public $desc       = 'Colour picker - visual';

    public $xv_size       = 10; //Default number of characters visible in the input box
    public $xv_max_length  = 7; //default maximum length of string when no max_lengt
    public $xv_min_length = 1; //minimum length of string - we use this to get thru parent validation

   function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'colorpicker';
        $this->filepath = 'modules/base/xarproperties';
    }

    function validateValue($value = NULL)
    {
        if (!parent::validateValue($value)) return false;
        if (!isset($value)) {
            $value = $this->value;
        }

        if (!empty($value)) {
            if (strlen($value) > $this->xv_maxlength || strlen($value) < 7 ||  !preg_match('/^\#(([a-f0-9]{6}))$/i', $value)) {
                $this->invalid = xarML('Invalid:  "#(1)" value must be in the format "#RRGGBB"', $this->name);
                $this->value = null;
                return false;
            }
        }

        return true;
    }

    function showInput(Array $data = array())
    {
        extract($data);

        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        if (!isset($value)) {
            $value = $this->value;
        }

       // Include color picker javascript.
        xarMod::apiFunc(
            'base','javascript','modulefile',
            array('module' => 'base', 'filename' => 'jscolor.js')
        );

        $data['baseuri']   =xarServer::getBaseURI();
        $data['name']     = $name;
        $data['id']       = $id;
        $data['size']     = $this->xv_size;
        $data['maxlength']= $this->xv_max_length;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['template'] = isset($template) && !empty($template)?$template: 'colorpicker';
        return parent::showInput($data);
    }
    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
          $validations = parent::getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 144,
                            'name'       => 'colorpicker',
                            'label'      => 'Color picker - visual',
                            'format'     => '144',
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
