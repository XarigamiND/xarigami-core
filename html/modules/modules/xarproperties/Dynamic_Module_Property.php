<?php
/**
 * Dynamic Data Module Property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Dynamic Data Module Property
 * @author mikespub
 */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * Handle the module property
 *
 * @package dynamicdata
 */
class Dynamic_Module_Property extends Dynamic_Select_Property
{
    public $id         = 43;
    public $name       = 'module';

    function __construct($args)
    {
        parent::__construct($args);

        if (count($this->options) == 0) {
            $modlist = xarMod::apiFunc('modules','admin','getlist',$args);
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }
    }
    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 19,
                            'name'       => 'module',
                            'label'      => 'Module',
                            'format'     => '19',
                            'validation' => serialize($validations),
                            'source'     => '',
                            'dependancies' => '',
                            'source'    => $this->source,
                            'filepath'    => 'modules/modules/xarproperties',
                            'requiresmodule' => 'modules',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>