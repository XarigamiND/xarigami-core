<?php
/**
 * Get a specific user by specifc attributes for the reset password
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami
 */
/**
 * uname, uid and email are guaranteed to be unique,
 * @param $args['uid'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['email'] email of user to get
 * @param int $args['state'] Status of the user to get
 * @param int $args['valcode'] set to 1 for group (default 0 = user)
 * @return array user array, or false on failure
 */
function roles_userapi_getstrict($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument checks - if any are missing return
    if ( empty($resetcode) || empty($userstate)) {
        $msg = xarML('Wrong arguments to roles_userapi_getstrict.');
        throw new BadParameterException(null,$msg);
    }
                
    if (!isset($uname)) $uname = '';
    $type = 0; //user
    if (!xarSecurityCheck('ViewRoles')) return;

    // Get database setup
    $dbconn = xarDB::$dbconn;

    $xartable = &xarDB::$tables;
    $rolestable = $xartable['roles'];
    $bindvars = array($resetcode);
    if (!empty($uname)) {
        $bindvars[] = $uname;
    }
    // Get user
      $query = "SELECT  xar_uid,
                        xar_uname,
                        xar_name,
                        xar_email,
                        xar_pass,
                        xar_state,
                        xar_valcode,
                        xar_date_reg
                 FROM $rolestable
                 WHERE  xar_state = 3 AND xar_valcode = ?  ";
     if (!empty($uname)) {
        $query .= " AND xar_uname = ? ";     
     }
    $result = $dbconn->Execute($query, $bindvars);

    if (!$result) {return ;}
    $user = array();
    for (; !$result->EOF; $result->MoveNext()) {    
        list($uid, $uname, $name, $email, $pass, $state, $valcode, $date_reg) = $result->fields;
                    $user = array(
                        'uid'       => (int) $uid,
                        'uname'     => $uname,
                        'name'      => $name,
                        'email'     => $email,
                        'pass'      => $pass,
                        'state'     => $state,
                        'valcode'   => $valcode,
                        'date_reg'  => $date_reg
                    );
    }
    return $user;
}

?>
