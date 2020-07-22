<?php
/**
 * Count all active users
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
 * count all active (online) users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 * @param bool $include_anonymous whether or not to include anonymous user
 * @param book $unique only return unique sessions
 * @returns integer
 * @return number of users that are active on a site
 */
function roles_userapi_countallactive($args)
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
    $unique = isset($unique) ? (bool) $unique : FALSE;

    // Optional arguments.
    if (empty($filter)){
         //only include active users within inactivity timeout
         $filter = time() - (xarConfigGetVar('Site.Session.InactivityTimeout') * 60);
    }

    // Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    // Restriction by group.
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
    //only active users state =3
    $bindvars = array();
/*
    if ($unique != FALSE) {
        $query = "SELECT DISTINCT *,
          COUNT(*) as count ";
    } else {
        $query = "SELECT COUNT(*) ";
    }
*/
    $query = "SELECT COUNT(*) ";

    if (empty($group_list)) {
        $query .= " FROM $rolestable a, $sessioninfoTable b
                   WHERE a.xar_uid = b.xar_uid AND b.xar_lastused > ? AND a.xar_uid > 1 AND a.xar_state = 3";
        $bindvars[] = $filter;

    } else {
        $query .= " FROM $rolestable a, $sessioninfoTable b, $rolemembtable AS c
                  WHERE a.xar_uid = b.xar_uid AND b.xar_lastused > ? AND a.xar_uid > 1 AND a.xar_uid = c.xar_uid";

         $bindvars[] = $filter;

        if (count($group_list) > 1) {
            $query .= ' AND c.xar_parentid in (?' . str_repeat(',?',count($group_list)-1) . ')';
            $bindvars = array_merge($bindvars, $group_list);
        } else {
            $query .= ' AND c.xar_parentid = ?';
            $bindvars[] = $group_list[0];
        }
    }

    if (isset($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query
    if (!$include_anonymous) {
        $anon = xarConfigGetVar('Site.User.AnonymousUID'); //xarMod::apiFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND a.xar_uid != ?";
        $bindvars[] = (int) $anon['uid'];
    }
    if (!$include_myself) {
        $thisrole = xarMod::apiFunc('roles','user','get',array('uname'=>'myself'));
        $query .= " AND a.xar_uid != ?";
        $bindvars[] = (int) $thisrole['uid'];
    }

    $query .= " AND xar_type = 0";


    if ($unique != FALSE) {
        if (!$include_anonymous) {
            //we can use uid and count by users
            $query .= " GROUP BY b.xar_uid ";
        } else {
            //we can only use ip as there may be lots of anon
            //this could be wildly inaccurate as well
            $query .= " GROUP BY b.xar_ipaddr ";
        }
    }

// cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles','cache.userapi.countallactive');
    if (!empty($expire)){
        $result = $dbconn->CacheExecute($expire,$query,$bindvars);
    } else {
        $result = $dbconn->Execute($query,$bindvars);
    }
    if (!$result) return;

    // Obtain the number of users
    if ($unique != FALSE) {
        $numroles = $result->_numOfRows;
    } else {
        list($numroles) = $result->fields;
    }
    $result->Close();
    // Return the number of users

    return (int)$numroles;
}

?>