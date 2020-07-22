<?php
/**
 * Get all active users
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
 * get all active  (online) users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 * @param bool $include_anonymous whether or not to include anonymous user
 * @param bool $include_myself whether or not to include myself user
 * @param bool $uid select for individual user
 * @param string $group comma-separated list of group names or IDs (default NULL = all)
 * @param integer $uid user ID of user to check (default NULL = all)
 * @param array $uidlist array of uids separated by commas
 * @param book $unique only return unique sessions
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getallactive($args)
{
    extract($args);

    if (!isset($include_anonymous)) {
        $include_anonymous = true;
    } else {
        $include_anonymous = (bool) $include_anonymous;
    }
    if (!isset($include_myself)) {
        $include_myself = true;
    } else {
        $include_myself = (bool) $include_myself;
    }
    // Optional arguments.
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = -1;
    }
    if (!isset($order)) {
        $order = "name";
    }
      if (!isset($sort)) {
        $sort = "ASC";
    }
    $unique = isset($unique) && ($unique == TRUE) ? ' DISTINCT ' :'';

    if (empty($filter)){
            //users that are active now
            $filter = time() - (xarConfigGetVar('Site.Session.InactivityTimeout') * 60);
    }

    $roles = array();

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    // Restriction by group.
    // This is a CSV list of IDs or names (or a mix)
    if (!empty($group)) {
        $groups = explode(',', $group);
        $group_list = array();
        foreach ($groups as $group) {
            $group = xarMod::apiFunc(
                'roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'uid' : 'name') => $group,
                    'type' => 1
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $group_list[] = (int) $group['uid'];
            }
        }
    }

    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $sessioninfoTable = $xartable['session_info'];
    $rolestable = $xartable['roles'];
    $rolemembtable = $xartable['rolemembers'];
    $bindvars = array();

    if (empty($group_list)) {
        $query = "SELECT $unique a.xar_uid,
                         a.xar_uname,
                         a.xar_name,
                         a.xar_email,
                         a.xar_date_reg,
                         b.xar_ipaddr
                  FROM $rolestable a, $sessioninfoTable b";
    } else {

        $query = "SELECT $unique a.xar_uid,
                        a.xar_uname,
                        a.xar_name,
                        a.xar_email,
                        a.xar_date_reg,
                        b.xar_ipaddr
                  FROM $rolestable a
                  JOIN $sessioninfoTable b
                  ";
    }

    if (!empty($unique)) {
        if (!$include_anonymous) {
           $query .= " JOIN (SELECT xar_uid FROM  $rolestable a GROUP BY xar_uid) d ON b.xar_uid = d.xar_uid ";
        } else {
            //we can only use ip as there may be lots of anon
            $query .= " JOIN (SELECT xar_ipaddr FROM  $sessioninfoTable GROUP BY xar_ipaddr) d ON b.xar_ipaddr = d.xar_ipaddr ";
        }
    }

    if (isset($uid) && $uid !=0 && empty($group_list)) {
         $query .="  WHERE a.xar_uid = b.xar_uid AND b.xar_lastused > ? AND a.xar_uid =? AND a.xar_state = 3";
         $bindvars[] = $filter;
         $bindvars[] = $uid;
    } elseif (empty($group_list)) {
        $query .="  WHERE a.xar_uid = b.xar_uid AND b.xar_lastused > ? AND a.xar_uid > 1 AND a.xar_state = 3";
        $bindvars[] = $filter;
    } else {

        $query .=" JOIN $rolemembtable c  WHERE a.xar_uid = b.xar_uid AND b.xar_lastused > ? AND a.xar_uid > 1 AND a.xar_uid = c.xar_uid";
        $bindvars[] = $filter;
        if (count($group_list) > 1) {
            $query .= ' AND c.xar_parentid in (?' . str_repeat(',?',count($group_list)-1) . ')';
            $bindvars = array_merge($bindvars, $group_list);
        } else {
            $query .= ' AND c.xar_parentid = ?';
            $bindvars[] = $group_list[0];
        }
    }
    if (isset($selection) && !empty($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query
    if (!$include_anonymous) {
        $anon['uid'] = xarConfigGetVar('Site.User.AnonymousUID'); //xarMod::apiFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND a.xar_uid != ?";
        $bindvars[] = (int) $anon['uid'];
    }
    if (!$include_myself) {
        $thisrole = xarMod::apiFunc('roles','user','get',array('uname'=>'myself'));
        $query .= " AND a.xar_uid != ?";
        $bindvars[] = (int) $thisrole['uid'];
    }
    if (isset($uidlist) && is_array($uidlist) && count($uidlist) > 0) {
        $query .= ' AND a.xar_uid IN(' . join(',',$uidlist) . ') ';
    }
     $query .= ' AND xar_type = 0';

//    if (!empty($unique)) {
//        if (!$include_anonymous) {
            //we can use uid
//            $query .= " GROUP BY b.xar_uid ";
//        } else {
            //we can only use ip as there may be lots of anon
//            $query .= " GROUP BY b.xar_ipaddr ";
//        }
//    }

    if ($order != -1) {

        $query .= " ORDER BY xar_" . $order .' '.$sort;
    }

    // cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles','cache.userapi.getallactive');

    if ($startnum == 0) { // deprecated - use countallactive() instead
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

    if (!$result) return;

    // Put users into result array
    $sessions = array();
    for (; !$result->EOF; $result->MoveNext())
    {
        list($uid, $uname, $name, $email, $date_reg, $ipaddr) = $result->fields;
        if (xarSecurityCheck('ViewRoles', 0, 'Roles', "$uid"))
        {
            $sessions[] = array('uid'       => (int) $uid,
                                'name'      => $name,
                                'uname'     => $uname,
                                'email'     => $email,
                                'date_reg'  => $date_reg,
                                'ipaddr'    => $ipaddr);
        }
    }

    return $sessions;
}
?>