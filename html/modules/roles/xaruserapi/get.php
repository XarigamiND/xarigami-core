<?php
/**
 * Get a specific user by any of his attributes
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2006-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 * get a specific user by any of his attributes
 * uname, uid and email are guaranteed to be unique,
 * otherwise the first hit will be returned
 * @author Xaraya Core Development Team
 * @param $args['uid'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['name'] name of user to get
 * @param $args['email'] email of user to get
 * @param int $args['state'] Status of the user to get
 * @param int $args['type'] set to 1 for group (default 0 = user)
 * NOTE: for groups, use 'name' not 'uname'
 * @return array user array, or false on failure
 */
function roles_userapi_get($args)
{
    // Get arguments from argument array
    extract($args);
    //let's use some standard variables for the get function, used in hooks
    if (!isset($uid) && isset($itemid) && !empty($itemid)) {
        $uid = $itemid;
    }
    // Argument checks
    if (empty($uid) && empty($name) && empty($uname) && empty($email)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        throw new BadParameterException(null,$msg);
     } elseif (!empty($uid) && !is_numeric($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        throw new EmptyParameterException(null,$msg);
    }

    if (empty($type)) $type = 0;
    $bindvars = array();
    $wherelist = array();

    if (!empty($uid) && is_numeric($uid)) {
        $wherelist[] = "xar_uid = ?";
        $bindvars[] = $uid;
    }
    if (!empty($name)) {
        $wherelist[] = "xar_name = ?";
        $bindvars[] = $name;
    }
    if (!empty($uname)) {
        $wherelist[] = "xar_uname = ?";
        $bindvars[] = $uname;
    }
    if (!empty($email)) {
        $wherelist[] = "xar_email = ?";
        $bindvars[] = $email;
    }
    if (!empty($state) && $state == ROLES_STATE_CURRENT) {
        $deleted = ROLES_STATE_DELETED;
        $wherelist[] = "xar_state != $deleted";
    }
    elseif (!empty($state) && $state != ROLES_STATE_ALL) {
        $wherelist[] = "xar_state = ?";
        $bindvars[] = $state;
    }

    $wherelist[] = "xar_type = ?";
    $bindvars[] = $type;

    if (count($wherelist) > 0) {
            $where = " WHERE " . join(' AND ',$wherelist);
    } else {
            $where = '';
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $rolestable = $xartable['roles'];

    // Get user
    // UID is a reserved word in Oracle (cannot be redefined)
    // TYPE is a key word in several databases (avoid for the future)
    $query = "SELECT xar_uid,
                     xar_uname AS uname,
                     xar_name AS name,
                     xar_type,
                     xar_email AS email,
                     xar_pass AS pass,
                     xar_date_reg AS date_reg,
                     xar_valcode AS valcode,
                     xar_state AS state
               FROM $rolestable
               $where ";

    $result = $dbconn->Execute($query,$bindvars);

    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        return; //let the calling function handle any empty return in this case
    }
    /* Obtain the item information from the result set */
    list($uid,$uname,$name,$xar_type,$email,$pass,$date_reg,$valcode,$state) = $result->fields;
    $user = array();
    /* Create the item array */
    $user= array('uid'      => (int)$uid,
                 'uname'    => $uname,
                 'name'     => $name,
                 'type'     => $xar_type,
                 'email'    => $email,
                 'pass'     => $pass,
                 'date_reg' => $date_reg,
                 'valcode'  => $valcode,
                 'state'    => $state
                 );
    $result->Close();
    return $user;
}

?>