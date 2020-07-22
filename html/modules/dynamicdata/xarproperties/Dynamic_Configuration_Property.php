<?php
/**
 * Dynamic Configuration property
 *
 * @package modules
 * @copyright (C) 2011 skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dymamic data module
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Include the base class
 *
 */
sys::import('modules.base.xarproperties.Dynamic_TextArea_Property');

/**
 * handle the validation property
 *
 * @package dynamicdata
 */
class Dynamic_Configuration_Property extends Dynamic_TextArea_Property
{
    public $id      = 998;
    public $name    = 'configuration';
    public $desc    = 'Configuration';
    public $reqmodules  = 'dynamicdata';
    public $proptype    = 1;
    public $xv_allowempty = 1;

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->include_reference = 1;
        $this->template = 'configuration';

    }

    public function checkInput($name = '', $value = null)
    {
        // set property type from object reference if possible
        if (!empty($this->objectref) && !empty($this->objectref->properties['id'])) {
            $this->proptype = $this->objectref->properties['id']->value;
        }
        $data['type'] = $this->proptype;

        if (empty($data['type'])) {
            $data['type'] = 1; // default DataProperty class
        }

        $data['name'] = !empty($name) ? $name : 'dd_'.$this->id;

        $property = Dynamic_Property_Master::getProperty($data);
        if (empty($property)) return;

        if (!xarVarFetch($data['name'],'isset',$data['validation'],NULL,XARVAR_NOT_REQUIRED)) return;

        if (!$property->updateValidation($data)) return false;
        $this->value = $property->validation;

        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);

        if(isset($data['value']['proptype'])) $data['type'] = $data['value']['proptype'];
        if (!empty($this->objectref) && !empty($this->objectref->properties['id'])) {
            $this->proptype = $this->objectref->properties['id']->value;
            $data['type'] = $this->proptype;
        }

        // Override from input
        if (!empty($data['type'])) {
            $this->proptype = $data['type'];
        } else {
            $data['type'] = $this->proptype;
        }

        $property = Dynamic_Property_Master::getProperty($data);

        $property->id = $this->id;

        $property->parseValidation($this->value);

        if (!isset($template)) $template = $this->template;
        $data['template'] = $template;

        // call its showValidation() method and return
        return $property->showValidation($data);
    }

    function showOutput(Array $data = array())
    {
        extract($data);

        if (isset($value)) {
            $value = xarVarPrepHTMLDisplay($value);
        } else {
            $value = xarVarPrepHTMLDisplay($this->value);
        }
     if (!isset($template)) $template = $this->template;
        $data['template'] = $template;
        return $value;
    }

    /**
     * Get the base information for this property.
     *
     * @return array Base information for this property
     **/
    function getBasePropertyInfo()
    {
        $args = array();
        $validation = parent::getBaseValidationInfo();
        $baseInfo = array(
                          'id'         => 998,
                          'name'       => 'configuration',
                          'label'      => 'Configuration',
                          'format'     => '998',
                          'validation' => serialize($validation),
                          'source'     => $this->source,
                          'dependancies' => '',
                          'filepath'    => 'modules/dynamicdata/xarproperties',
                          'requiresmodule' => 'dynamicdata',
                          'aliases' => '',
                          'args'       => serialize( $args ),
                          // ...
                         );
        return $baseInfo;
    }
}

?>