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
 * get all users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['order'] comma-separated list of order items; default 'name'
 * @param $args['sort'] asc or desc
 * @param $args['selection'] extra coonditions passed into the where-clause
 * @param $args['group'] comma-separated list of group names or IDs, or
 * @param $args['uidlist'] array of user ids
 * @param $args['pending'] bool show pending as well
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getall($args)
{

    extract($args);

    // Optional arguments.
    if (!isset($startnum)) $startnum = 1;
    if (!isset($numitems)) $numitems = -1;
    $pending = isset($pending) ? $pending: false;
    // Security check - need overview level to see that the roles exist
    if (!xarSecurityCheck('ViewRoles')) return;
    $sort = isset($sort) ? $sort : 'asc';
    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolestable = $xartable['roles'];
    $rolemembtable = $xartable['rolemembers'];

    // Create the order array.
    if (isset($order) && $order == -1)  {
        $order_clause = NULL;
    } elseif(!isset($order)) {
        $order_clause = array('roletab.xar_name');
        $order='name';
    } else {
       $order_clause = array();
       $orderlist = explode(',', $order);

        foreach ($orderlist as $order_field) {
            //existing roles getall calls
            if (preg_match('/^[-]?(name|uname|email|uid|state|date_reg)$/', $order_field)) {
                if (strstr($order_field, '-')) {
                    $order_clause[] = 'roletab.xar_' . str_replace('-', '', $order_field) . ' '.$sort;
                } else {
                    $order_clause[] = 'roletab.xar_' . $order_field . ' '.$sort;
                }
                //calls with xarquery rewrite
            } elseif  (preg_match('/^[-]?(xar_name|xar_uname|xar_email|xar_uid|xar_state|xar_date_reg)$/', $order_field)) {
                $order_clause[] = 'roletab.' . $order_field . ' '.$sort;
            }
        }
    }

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
        $query = '
            SELECT  roletab.xar_uid,
                    roletab.xar_uname,
                    roletab.xar_name,
                    roletab.xar_email,
                    roletab.xar_pass,
                    roletab.xar_state,
                    roletab.xar_date_reg';
        $query .= ' FROM ' . $rolestable . ' AS roletab';
    } else {
        // Select-clause.
        $query = '
            SELECT  DISTINCT roletab.xar_uid,
                    roletab.xar_uname,
                    roletab.xar_name,
                    roletab.xar_email,
                    roletab.xar_pass,
                    roletab.xar_state,
                    roletab.xar_date_reg,
                    rolememb.xar_parentid';
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

    // Add the order clause.
    if (!empty($order_clause)) {
        $query .= ' ORDER BY ' . implode(', ', $order_clause);
    }

    // cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles', 'cache.userapi.getall');
    if (($startnum == 0) || ($order == -1)) { //we want specific uids and no order, just get what we want
        if (!empty($expire)){
            $result = $dbconn->CacheExecute($expire,$query,$bindvars);
        } else {
            $result = $dbconn->Execute($query,$bindvars);
        }
    } else {
        if (!empty($expire)){
            $result = $dbconn->CacheSelectLimit($expire, $query, $numitems, $startnum-1,$bindvars);
        } else {
            $result = $dbconn->SelectLimit($query, $numitems, $startnum-1,$bindvars);
        }
    }
    if (!$result) {return;}

    // Put users into result array
    $roles = array();
    for (; !$result->EOF; $result->MoveNext()) {
        if (!empty($group_list)) {
                list($uid, $uname, $name, $email, $pass, $state, $date_reg, $pid) = $result->fields;
        } else {
            list($uid, $uname, $name, $email, $pass, $state, $date_reg) = $result->fields;    
        }

        
            if (!empty($uidlist)) {
                if (xarSecurityCheck('ReadRole', 0, 'Roles', "$uid")) {
                    $roles[$uid] = array(
                        'uid'       => (int) $uid,
                        'uname'     => $uname,
                        'name'      => $name,
                        'email'     => $email,
                        'pass'      => $pass,
                        'state'     => $state,
                        'date_reg'  => $date_reg
                    );
                } elseif (xarSecurityCheck('ViewRoles', 0, 'Roles', "$uid")) {
                   $roles[$uid] = array(
                        'uid'       => (int) $uid,
                        'uname'     => $uname,
                        'name'      => $name,
                        'state'     => $state,
                        'date_reg'  => $date_reg
                    );
                }
            } elseif (!empty($group_list)) {  
                if (xarSecurityCheck('ReadRole', 0, 'Roles', "$uid") || xarSecurityCheck('ReadGroupRoles', 0, 'Group', "$pid")) {
                    $roles[] = array(
                        'uid'       => (int) $uid,
                        'uname'     => $uname,
                        'name'      => $name,
                        'email'     => $email,
                        'pass'      => $pass,
                        'state'     => $state,
                        'date_reg'  => $date_reg,
                        'pparentid' => $pid
                    );  
                } elseif (xarSecurityCheck('ViewRole', 0, 'Roles', "$uid") || xarSecurityCheck('ViewGroupRoles', 0, 'Group', "$pid")) {
                    $roles[] = array(
                        'uid'       => (int) $uid,
                        'uname'     => $uname,
                        'name'      => $name,
                        'state'     => $state,
                        'date_reg'  => $date_reg,
                        'pparentid' => $pid
                    );  
                }            
            } else {
                if (xarSecurityCheck('ReadRole', 0, 'Roles', "$uid")) {
                    $roles[] = array(
                        'uid'       => (int) $uid,
                        'uname'     => $uname,
                        'name'      => $name,
                        'email'     => $email,
                        'pass'      => $pass,
                        'state'     => $state,
                        'date_reg'  => $date_reg
                    );
                } elseif (xarSecurityCheck('ViewRoles', 0, 'Roles', "$uid")) {
                    $roles[] = array(
                        'uid'       => (int) $uid,
                        'uname'     => $uname,
                        'name'      => $name,
                        'state'     => $state,
                        'date_reg'  => $date_reg
                    );                 
                }
            }       
    }

    // Return the users
    return $roles;
}

?>