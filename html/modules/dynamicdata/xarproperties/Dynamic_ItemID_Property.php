<?php
/**
 * Dynamic Item Id property Property
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
 * Include the base class
 *
 */
sys::import('modules.base.xarproperties.Dynamic_NumberBox_Property');

/**
 * handle item id property
 *
 * @package dynamicdata
 */
class Dynamic_ItemID_Property extends Dynamic_NumberBox_Property
{
    public $id         = 21;
    public $name       = 'itemid';
    public $desc       = 'Item ID';
    public $reqmodules  = 'dynamicdata';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule    = 'dynamicdata';
        $this->template     = 'itemid';
        $this->filepath     = 'modules/dynamicdata/xarproperties';
    }

    function checkInput($name='', $value = null)
    {
        $name = 'dd_'.$this->id;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);

    }

    /**
     * Get the base information for this property.
     *
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $validation = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 21,
                              'name'       => 'itemid',
                              'label'      => 'Item ID',
                              'format'     => '21',
                              'validation' => serialize($validation),
                              'source'         => '',
                              'dependancies'   => '',
                              'filepath'    => 'modules/dynamicdata/xarproperties',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>