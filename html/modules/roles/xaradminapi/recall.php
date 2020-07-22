<?php
/**
 * Recall deleted roles
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * @param $args['uid'] uid of the role that is being called
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_recall($args)
{
    // Get arguments
    extract($args);

    if (!isset($uid) || $uid == 0) throw new EmptyParameterException('uid');
    if (!isset($state) || $state == 0) throw new EmptyParameterException('state');
    //for reporting only
    $statearray = array(
                        ROLES_STATE_INACTIVE => xarML('Inactive'),
                        ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                        ROLES_STATE_ACTIVE => xarML('Active'),
                        ROLES_STATE_PENDING => xarML('Pending')
                        );
    $newstate= $statearray[$state];

    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $rolestable = $xartable['roles'];

    $deletedml = '[' . xarML('deleted') . ']';
    //jojo - surely we have to use this not xarML!
    $deleted = '[deleted]';
    //let us try to support both for now -and deprecate teh xarML
    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    $uname = explode($deleted,$role->getUser());
    //support backward compat with ML .
    if (!is_array($uname)) {
         $uname = explode($deletedml,$role->getUser());
    }
    $email = explode($deleted,$role->getEmail());
    if (!is_array($email)) {
        explode($deletedml,$role->getEmail());
    }

//            echo $uname[0];exit;
    $query = "UPDATE $rolestable
              SET xar_uname = ?, xar_email = ?, xar_state = ?
              WHERE xar_uid = ?";
    $bindvars = array($uname[0],$email[0],$state,$uid);
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    // Let any hooks know that we have recalled this user.
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['method'] = 'recall';
    xarMod::callHooks('item', 'create', $uid, $item);

    $msg = xarML('The user "#(1)" was successfully recalled and is "#(2)".',$uname, $newstate);
    xarTplSetMessage($msg,'status');
     xarLogMessage('ROLES: Roles user '. $uid.' was recalled from deleted by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    //finished successfully
    return true;
}

?>
