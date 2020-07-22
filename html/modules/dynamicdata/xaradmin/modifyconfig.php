<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * This is a standard function to modify the configuration parameters of the
 * module
 * @return array
 */
function dynamicdata_admin_modifyconfig()
{

    $data = array();

    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');

    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    $data['authid'] = xarSecGenAuthKey();

    $data['itemsperpage'] = xarModGetVar('dynamicdata', 'itemsperpage');
    $data['useritemsperpage'] = xarModGetVar('dynamicdata', 'useritemsperpage');
    $systemobjects = xarModGetVar('dynamicdata', 'systemobjects');

    $data['systemobjects'] = @unserialize($systemobjects);

    $sysobjects = xarMod::apiFunc('dynamicdata','user','getobjects');

    foreach ($sysobjects as $objectid=>$objectdata) {
        $id = $objectdata['objectid'];
        $sysobjectoptions[$id] = $objectdata['name'] ;//.' ['.xarMod::getName($objectdata['moduleid']).']';
    }
    $data['sysobjectoptions'] = $sysobjectoptions;

    $hooks = xarMod::callHooks('module', 'modifyconfig', 'dynamicdata',
                       array('module' => 'dynamicdata'));
    if (empty($hooks)) {
        $data['hooks'] = array();
    } else {
        $data['hooks'] = $hooks;
    }

    return $data;
}

?>