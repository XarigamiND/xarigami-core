<?php
/**
 * Dynamic Data Field Status Property
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
 * Dynamic Data Field Status Property
 * @author mikespub <mikespub@xaraya.com>
*/
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Class to handle field status
 *
 * @package dynamicdata
 */
class Dynamic_FieldStatus_Property extends Dynamic_Select_Property
{
    public $id         = 25;
    public $name       = 'fieldstatus';
    public $desc       = 'Field Status';
    public $reqmodules  = 'dynamicdata';
    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->tplmodule  =  'base';
        $this->template   =  'dropdown';

        if (count($this->options) == 0) {
            $this->getOptions();
        }
    }

    function getOptions()
    {
        $options = array(
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE, 'name' => xarML('Active')),
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY, 'name' => xarML('Input and view only')),
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_DISPLAYONLY, 'name' => xarML('Input and display only')),
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_INPUTONLY, 'name' => xarML('Input only')),
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN, 'name' => xarML('Hidden input, no view or display')),
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDENDISPLAY, 'name' => xarML('Hidden input with display')),
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED, 'name' => xarML('Ignored on input')),
                             array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED, 'name' => xarML('Disabled')),
                         );
        return $options;
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $validation = parent::getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 25,
                              'name'       => 'fieldstatus',
                              'label'      => 'Field status',
                              'format'     => '25',
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
