<?php
/**
 * Get a specific deleted user by any of his attributes
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
 * get a specific deleted user by any of his attributes
 * uname, uid and email are guaranteed to be unique,
 * otherwise the first hit will be returned
 * @author Richard Cave <rcave@xaraya.com>
 * @param $args['uid'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['name'] name of user to get
 * @param $args['email'] email of user to get
 * @returns array
 */
function roles_userapi_getdeleteduser($args)
{
    // Extract arguments
    extract($args);

    // Argument checks
    if (empty($uid) && empty($name) && empty($uname) && empty($email)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        throw new BadParameterException(null,$msg);
    } elseif (!empty($uid) && !is_numeric($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        throw new BadParameterException(null,$msg);
    }

    // Set type to user
    if (empty($type)){
        $type = 0;
    }

    // Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();

    $rolestable = $xartable['roles'];

    $bindvars = array();
    $query = "SELECT xar_uid,
                   xar_uname,
                   xar_name,
                   xar_email,
                   xar_pass,
                   xar_date_reg,
                   xar_valcode,
                   xar_state
            FROM $rolestable
            WHERE xar_state = 0
            AND xar_type = ?";
    $bindvars[] = $type;

    if (!empty($uid) && is_numeric($uid)) {
        $query .= " AND xar_uid = ?";
        $bindvars[] = $uid;
    } elseif (!empty($name)) {
        $query .= " AND xar_name = ?";
        $bindvars[] = $name;
    } elseif (!empty($uname)) {
        // Need to add 'deleted' string to username
        $deleted = '[' . xarML('deleted') . ']';
        $query .= " AND xar_uname LIKE ?";
        $bindvars[] = $uname.$deleted."%";
    } elseif (!empty($email)) {
        $query .= " AND xar_email = ?";
        $bindvars[] = $email;
    }

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    // Check for no rows found, and if so return
    if ($result->EOF) {
        return false;
    }

    // Obtain the item information from the result set
    list($uid, $uname, $name, $email, $pass, $date, $valcode, $state) = $result->fields;

    $result->Close();

    // Create the user array
    $user = array('uid'         => $uid,
                  'uname'       => $uname,
                  'name'        => $name,
                  'email'       => $email,
                  'pass'        => $pass,
                  'date_reg'    => $date,
                  'valcode'     => $valcode,
                  'state'       => $state);

    // Return the user array
    return $user;

}
?>
