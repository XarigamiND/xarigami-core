<?php
/**
 * Delete a users item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * delete a users item
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_delete($args)
{
    // Get arguments
    extract($args);

    // Argument check
    if (!isset($uid)) {
        $msg = xarML('Wrong arguments to roles_adminapi_delete.');
        throw new BadParameterException(null,$msg);
    }
    $roles  = new xarRoles();
    $role   = $roles->getRole($uid);
    // The user API function is called.
    $item = xarMod::apiFunc('roles', 'user','get',
            array('uid' => $uid));

    if ($item == false) {
        $msg = xarML('No such user','roles');
        throw new BadParameterException(null,$msg);
    }

    // Security check
    if (!xarSecurityCheck('DeleteRole',0,'Roles',"$uid")) {
       return xarResponseForbidden();
    }

    // Get datbase setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $rolestable = $xartable['roles'];

    // Delete the item
    $query = "DELETE FROM $rolestable WHERE xar_uid = ?";
    $result = $dbconn->Execute($query,array($uid));
    if (!$result) return;

    // Let any hooks know that we have deleted this user.
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['method'] = 'delete';
    xarMod::callHooks('item', 'delete', $uid, $item);
       xarLogMessage('ROLES: User '.$uid.' was deleted by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    //finished successfully
    return true;
}

?>