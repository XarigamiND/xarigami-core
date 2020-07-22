<?php
/**
 * Dynamic Static Text property
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
 */
class Dynamic_StaticText_Property extends Dynamic_Property
{
    public $id         = 1;
    public $name       = 'static';
    public $desc       = 'Static Text';
    public $module     = 'base';
    public $reqmodules = 'base';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'static';
        $this->filepath = 'modules/base/xarproperties';
    }
    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }
    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('static text: #(1)', $this->name);;
            $this->value = null;
            return false;
        }
        return true;
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args = array();
        $baseInfo = array(
                              'id'         => 1,
                              'name'       => 'static',
                              'label'      => 'Static text',
                              'format'     => '1',
                              'validation' => serialize(array()),
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