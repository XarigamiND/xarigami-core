<?php
/**
 * Get all users
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * count all users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['order'] comma-separated list of order items; default 'name'
 * @param $args['selection'] extra coonditions passed into the where-clause
 * @param $args['group'] comma-separated list of group names or IDs, or
 * @param $args['uidlist'] array of user ids
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_countall($args)
{
    extract($args);

    // Optional arguments.
    if (!isset($startnum)) $startnum = 1;
    if (!isset($numitems)) $numitems = -1;
    $pending = isset($pending) ? $pending: false;
    // Security check - need overview level to see that the roles exist
    if (!xarSecurityCheck('ViewRoles')) return;

    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolestable = $xartable['roles'];
    $rolemembtable = $xartable['rolemembers'];

   // Restriction by group.
    if (isset($group)) {
        $groups = explode(',', $group);
        $group_list = array();
        foreach ($groups as $group) {
            $group = xarMod::apiFunc('roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'uid' : 'name') => $group,
                    'type' => ROLES_GROUPTYPE
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $group_list[] = (int) $group['uid'];
            }
        }
    }

    $where_clause = array();
    $bindvars = array();
    if (!empty($state) && is_numeric($state) && $state != ROLES_STATE_CURRENT) {
        $where_clause[] = 'roletab.xar_state = ?';
        $bindvars[] = (int) $state;
    } else {
        $where_clause[] = 'roletab.xar_state <> ?';
        $bindvars[] = (int) ROLES_STATE_DELETED;
    }

    if (empty($group_list)) {
        // Simple query.
        $query = 'SELECT COUNT(xar_uid)';
        $query .= ' FROM ' . $rolestable . ' AS roletab';
    } else {
        // Select-clause.
        $query = '
            SELECT  COUNT(1) FROM DISTINCT roletab.xar_uid';
        // Restrict by group(s) - join to the group_members table.
        $query .= ' FROM ' . $rolestable . ' AS roletab, ' . $rolemembtable . ' AS rolememb';
        $where_clause[] = 'roletab.xar_uid = rolememb.xar_uid';
        if (count($group_list) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($group_list)-1);
            $where_clause[] = 'rolememb.xar_parentid in (' . $bindmarkers. ')';
            $bindvars = array_merge($bindvars, $group_list);
        } else {
            $where_clause[] = 'rolememb.xar_parentid = ?';
            $bindvars[] = $group_list[0];
        }
    }

    // Hide pending users from non-admins
    if (!$pending && !xarSecurityCheck('AdminRole', 0)) {
        $where_clause[] = 'roletab.xar_state <> ?';
        $bindvars[] = (int) ROLES_STATE_PENDING;
    }

    // If we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query.
    // By default, include both 'myself' and 'anonymous'.
    if (isset($include_anonymous) && !$include_anonymous) {
        $thisrole = xarMod::apiFunc('roles', 'user', 'get', array('uname'=>'anonymous'));
        $where_clause[] = 'roletab.xar_uid <> ?';
        $bindvars[] = (int) $thisrole['uid'];
    }
    if (isset($include_myself) && !$include_myself) {

        $thisrole = xarMod::apiFunc('roles', 'user', 'get', array('uname'=>'myself'));
        $where_clause[] = 'roletab.xar_uid <> ?';
        $bindvars[] = (int) $thisrole['uid'];
    }

    // Return only users (not groups).
    $where_clause[] = 'roletab.xar_type = ?';
    $bindvars[] = ROLES_USERTYPE;
    
    // Add the where-clause to the query.
    $query .= ' WHERE ' . implode(' AND ', $where_clause);

    // Add extra where-clause criteria.
    if (isset($selection)) {
        $query .= ' ' . $selection;
    }

    if (isset($uidlist) && is_array($uidlist) && count($uidlist) > 0) {
        $query .= ' AND roletab.xar_uid IN (' . join(',',$uidlist) . ') ';
    }

 
    // cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles', 'cache.userapi.getall');

    if (!empty($expire)){
        $result = $dbconn->CacheExecute($expire,$query,$bindvars);
    } else {
        $result = $dbconn->Execute($query,$bindvars);
    }
    if (!$result) {return;}
    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

?>