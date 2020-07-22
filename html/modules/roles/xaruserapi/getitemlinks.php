<?php
/**
 * Utility function to pass individual item links to whoever
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * utility function to pass individual item links to whoever
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param  $args ['itemtype'] item type (optional)
 * @param  $args ['itemids'] array of item ids to get
 * @returns array
 * @return array containing the itemlink(s) for the item(s).
 */
function roles_userapi_getitemlinks($args)
{
    $itemlinks = array();
    if (!xarSecurityCheck('ViewRoles', 0)) {
        return $itemlinks;
    }

    foreach ($args['itemids'] as $itemid) {
        $item = xarMod::apiFunc('roles', 'user', 'get',
            array('uid' => $itemid));
        if (!isset($item)) return;
        $itemlinks[$itemid] = array('url' => xarModURL('roles', 'user', 'display',
                array('uid' => $itemid)),
            'title' => xarML('Display User'),
            'label' => xarVarPrepForDisplay($item['name']));
    }
    return $itemlinks;
}

?>