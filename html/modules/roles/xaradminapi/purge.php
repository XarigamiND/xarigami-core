<?php
/**
 * Delete users based on status
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * delete users based on status
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['state'] state that we are deleting.
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_purge($args)
{
    // Get arguments
    extract($args);


    if ($state == ROLES_STATE_ACTIVE) {
         return xarTplModule('roles','user','errors',array('errortype' => 'purge_active_user'));
    }
    $items = xarMod::apiFunc('roles', 'user', 'getall', array('state' => $state));

    foreach ($items as $item) {

        // The user API function is called.
        $user = xarMod::apiFunc('roles', 'user', 'get',
                array('uid' => $item['uid']));

    // Security check
        if (!xarSecurityCheck('DeleteRole',0,'Roles',$item['uid'])) {
            return xarResponseForbidden();
        }

        // Call the Roles class
        $roles = new xarRoles();
        $role = $roles->getRole($item['uid']);
        if (!$role->purge()) {
            return;
        } else {
           $msg = xarML('The user "#(1)" was purged from the system.',$user['uname']);
           xarTplSetMessage($msg,'status');
        }

    // Let any hooks know that we have purged this user.
        $item['module'] = 'roles';
        $item['itemid'] = $item['uid'];
        $item['method'] = 'purge';
        xarMod::callHooks('item', 'delete', $uid, $item);
    }

   xarLogMessage('ROLES: Roles user '. $uid.' was purged from the system by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    //finished successfully
    return true;
}

?>