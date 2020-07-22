<?php
/**
 * Update a role core info
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
 * Update a user's core info
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
function roles_adminapi_update($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($uid)) ||
        (!isset($name)) ||
        (!isset($uname)) ||
        (!isset($email)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count');
        throw new BadyParameterException(null,$msg);
    }
    $item = xarMod::apiFunc('roles', 'user', 'get',
            array('uid' => $uid));

    if ($item == false) {
        $msg = xarML('No such user');
        throw new IDNotFoundException($uid);
    }
    if (empty($valcode)) {
        $valcode = '';
    }
    if (!isset($home)) {
        $home = '';
    }
    xarModSetUserVar('roles','userhome',$home,$uid);

    if (isset($dopasswordupdate) && $dopasswordupdate ==TRUE) { //explicity check true and isset
        xarModSetUserVar('roles','passwordupdate',time(), $uid);
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolesTable = $xartable['roles'];

    if (!empty($pass)){
        $cryptpass=md5($pass);
        $query = "UPDATE $rolesTable
                  SET xar_name = ?,
                      xar_uname = ?,
                      xar_email = ?,
                      xar_pass = ?,
                      xar_valcode = ?,
                      xar_state = ?
                WHERE xar_uid = ?";
        $bindvars = array($name,$uname,$email,$cryptpass,$valcode,$state,$uid);
    } else {
        $query = "UPDATE $rolesTable
                SET xar_name = ?,
                    xar_uname = ?,
                    xar_email = ?,
                    xar_valcode = ?,
                    xar_state = ?
                WHERE xar_uid = ?";
        $bindvars = array($name,$uname,$email,$valcode,$state,$uid);
    }

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['name']   = $name;
    $item['home']   = $home;
    $item['uname']  = $uname;
    $item['email']  = $email;

    xarMod::callHooks('item', 'update', $uid, $item);
    xarLogMessage('ROLES: Information for user '.$uid.' was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>