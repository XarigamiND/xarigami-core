<?php
/**
 * Update a role state
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
 * Update a user's state
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] user ID
 * @param $args['name'] user display name
 * @param $args['uname'] user nick name
 * @param $args['email'] user email address
 * @param $args['pass'] user password
 * TODO: move url to dynamic user data
 *       replace with status
 * @param $args['url'] user url
 */
function roles_adminapi_stateupdate($args)
{
    extract($args);
    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($uid) || !isset($groupuid) ) {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null,$msg);
    }
     if (!isset($state)) throw new EmptyParameterException('state');
    $item = xarMod::apiFunc('roles','user','get',
                          array('uid' => $uid));

    //Check for privilege to edit role
    if ( !xarSecurityCheck('EditGroupRoles',0,'Group',$groupuid)
         &&
       !xarSecurityCheck('EditRole',0,'Roles',$uid)
    ) return;

    if ($item == false) {
        $msg = xarML('No such user');
       new IDNotFoundException($uid);
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolesTable = $xartable['roles'];

    $query = "UPDATE $rolesTable SET xar_state = ?" ;
    $bindvars = array($state);
    if (isset($valcode)) {
        $query .= ", xar_valcode = ?";
        $bindvars[] = $valcode;
    }
    $query .= " WHERE xar_uid = ?";
    $bindvars[] = $uid;

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
  xarLogMessage('ROLES: Roles user '. $uid.' had state modified to '.$state.' by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>
