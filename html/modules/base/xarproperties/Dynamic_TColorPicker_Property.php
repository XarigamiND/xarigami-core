<?php
/**
 * Dynamic Color Picker property
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
 * @package modules
 * @subpackage Base module
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');
class Dynamic_TColorPicker_Property extends Dynamic_TextBox_Property
{
    public $id         = 44;
    public $name       = 'tcolorpicker';
    public $desc       = 'Tigra Color Picker';

    public $xv_size       = 7; //Default number of characters visible in the input box
    public $xv_maxlength  = 7; //default maximum length of string when no max_lengt
    public $xv_min_length = 7; //minimum length of string

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'tcolorpicker';
        $this->filepath = 'modules/base/xarproperties';
    }
    /* Special condition */
     //<jojo> - we need a special case here
    // if no option is selected, we should treat it as 'empty' in this special case
    //while not strictly true from a usability pov it is what would be expected for 'allow empty'
     public function checkInput($name = '', $value = null)
    {        //store the fieldname in case it is required by other methods/functions (e.g. file uploads)
        $name = empty($name) ? 'dd_'.$this->id : $name;
          $this->fieldname = $name;
        $namelabel = isset($this->label)?$this->label:$this->name;
        $this->invalid = '';
         if (!isset($value)) {
             if (isset($this->label) && !empty($this->label)) {
                $namelabel = $this->label;
            } else {
                $namelabel = $this->name;
            }
            list($found,$value) = $this->fetchValue($name);
            if ((empty($value) || !found) && $this->xv_allowempty !=1) {
                $this->invalid = xarML("You must input a value for '#(1)'", $namelabel);
                $this->objectref->missingfields[] = $namelabel;//$this->name;
                return null;
            }
        }
        return $this->validateValue($value);
    }
    public function validateValue($value = NULL)
    {
       // if (!parent::validateValue($value)) return false;

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (strlen($value) > $this->xv_maxlength || !preg_match('/^\#(([a-f0-9]{3})|([a-f0-9]{6}))$/i', $value)) {
                $this->invalid = xarML('- color must be in the format "#RRGGBB" or "#RGB"');
                $this->value = null;
                return false;
            }
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        if (empty($maxlength) && isset($this->xv_max)) {
            $this->xv_maxlength = $this->xv_max;
            if ($this->xv_size > $this->xv_maxlength) {
                $this->xv_size = $this->xv_maxlength;
            }
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        if (!isset($value)) {
            $value = $this->value;
        }

        // Include color picker javascript options.
        // Allows the options to be over-ridden in a theme.
        xarMod::apiFunc(
            'base', 'javascript', 'modulecode',
            array('module' => 'base', 'filename' => 'tcolorpickeroptions.js')
        );

        // Include color picker javascript.
        xarMod::apiFunc(
            'base','javascript','modulefile',
            array('module' => 'base', 'filename' => 'tcolorpicker.js')
        );

        $data['baseuri']   =xarServer::getBaseURI();
        $data['name']     = $name;
        $data['id']       = $id;
        $data['size']     = $this->xv_size;
        $data['maxlength']= $this->xv_maxlength;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['template'] = isset($template) && !empty($template)?$template: 'tcolorpicker';
        return parent::showInput($data);
    }

     /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     public function getBasePropertyInfo()
     {
         $args = array();
         $validations = parent::getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 44,
                              'name'       => 'tcolorpicker',
                              'label'      => 'Color picker - tigra',
                              'format'     => '44',
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