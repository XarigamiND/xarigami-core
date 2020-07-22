<?php
/**
 * Combo Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Handle the combo property
 *
 * @package dynamicdata
 */
class Dynamic_Combo_Property extends Dynamic_Select_Property
{
        public $id         = 506;
        public $name       = 'combobox';
        public $desc       = 'Combo Dropdown Box';
        public $xv_override   = true;
        public $xv_firstline = '';


    function __construct($args)
    {
        parent::__construct($args);
        $this->template  = 'combobox';
        $this->xv_firstline =  xarML("Select or enter an item ==&gt;");

    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;

        $tbname = 'tb_dd_'.$this->id;
        $this->fieldname = $tbname;
        if (!xarVarFetch($tbname, 'isset', $tbvalue,  NULL, XARVAR_DONT_SET)) {return;}

        // store the fieldname for configurations that might need it (e.g. file uploads)
        $this->fieldname = $tbname;
        $errorlabel = isset($this->label)?$this->label: $this->name;

        if(isset($tbvalue) && $tbvalue !='')
        {
            // check as a textbox
            $value = $tbvalue;
            $textbox = Dynamic_Property_Master::getProperty(array('name' => 'textbox'));
            $isvalid = $textbox->validateValue($tbvalue);
            if ($isvalid) {
                $this->value = $textbox->value;
            } else {
                $this->invalid = $textbox->invalid;
            }

        } else {
            $name = 'dd_'.$this->id;
            $this->fieldname = $name;
            // check as a dropdown
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
            // Did we find a dropdown value?
            if(!isset($value)) {
                $this->invalid = xarML('#(1) must have a value',$errorlabel);
                return false;
            }
            $isvalid = parent::checkInput($name, $value);
        }
        return $isvalid;
    }


    public function showInput(Array $data = array())
    {
        extract($data);
        //check for specific overrides in this property
        $firstline = $this->xv_firstline;
        $this->parseValidation($this->validation);
        $data['firstline'] = isset($this->xv_firstline)?$this->xv_firstline: $firstline;
        return parent::showInput($data);
    }


    public function showOutput(Array $data = array())
    {
        extract($data);
        if (isset($value)) {
            $this->value = $value;
        }
        $data['value'] = $this->value;
        // get the option corresponding to this value
        $result = $this->getOption();
        $data['option'] = array('id' => $this->value,
                                'name' => xarVarPrepForDisplay($result));

        // If the value wasn't found in the select list data, then it was
        // probably typed in -- so just display it.
        if( !isset($data['option']['name']) || ( $data['option']['name'] == '') )
        {
            $data['option']['name'] = xarVarPrepForDisplay($this->value);
        }
         $data['template'] = (!isset($template) || empty($template)) ? $this->template:'combobox';
        return parent::showOutput($data);
    }

    /**
     * Get the base information for this property.
     * @return array base information for this property
     **/
     public function getBasePropertyInfo()
     {
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 506,
                              'name'       => 'combo',
                              'label'      => 'Combo dropdown textbox',
                              'format'     => '506',
                              'validation' => serialize($validations),
                              'source'         => '',
                              'filepath'    => 'modules/base/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}
?>