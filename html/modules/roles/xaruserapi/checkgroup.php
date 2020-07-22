<?php
/**
 * Check if a user is a member of a specific named group
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * A convenience function to check if a role (uid or uname) is a member of a specific group,
 * or a specific ancestor group
 *
 * @author Jo Dalle Nogare (jojodee)
 * @param   string  $group name of the group role to check for REQUIRED
 * @param   int     $gid group id of the group role to check for REQUIRED if $group not provided
 * @param   array   $gidlist array of the group roles to OR check for. OPTIONAL instead of group or gid
 * @param   int     $uid  user role to check for. Defaults to current user OPTIONAL
 * @param   string  $uname username of role to check for. OPTIONAL
 * @param   bool    $isancestor group can be any ancestor, not direct parent (default false). OPTIONAL
 * @return  bool    true if user is a member of a specific group (or has ancestor if $isancestor is true)
 */
if (!class_exists('xarRoles')) sys::import('modules.roles.xarclass.xarroles');
function roles_userapi_checkgroup($args)
{
    extract($args);


    if (!isset($group) && !isset($gid) && !isset($gidlist) ){

        $msg = xarML('roles_userapi_checkgroup');
          throw new BadParameterException(null,$msg);   ;
    }
    if (empty($group) && empty($gid) && empty($gidlist)) {
        //jojo - don't error if empty, just return FALSE
        return false;
    }
    //Initialize a var to hold name  of test variable for error msgs
    $test = '';
    $testvalue = '';

    if (!isset($isancestor) || is_null($isancestor)) $isancestor = false; //default

    //Check for !isset just in case (str or php ver)
    if (!isset($uid) && !isset($uname) && empty($uid) && empty($uname)) $uid = xarSessionGetVar('uid');

    //Work out what our group is
    $onegroup = true;
    $roles = new xarRoles();
    if (isset($group)  && !empty($group)) {
        //then we have $group name
        $groupinfo = $roles->findRole($group);
    } elseif (isset($gid)  && !empty($gid)) {
        //we have $gid
        $groupinfo = $roles->getRole($gid);
    } elseif (isset($gidlist) && is_array($gidlist)) {
        //we have an array of $gids
        $onegroup = false;
    }
    if ($onegroup) {

        if (!is_object($groupinfo)) {
            //No group found with this info
            //This maybe private info so we don't want an error message show to users
            //let's just return false here else uncomment below for example error message
            /*
            $msg = xarML('No ROLE of given value found in roles_userapi_checkgroup');
              throw new BadParameterException(null,$msg);
            */
            return FALSE;
        }
        //Now check if the user information
        if (empty($uid)) {
            $userinfo = $roles->ufindRole($uname);

        } else {
            $userinfo = $roles->getRole($uid);

        }
        if (!is_object($userinfo)) {
            //No user found with this info
            //This maybe private info so we don't want an error message show to users
            //let's just return false here
            return FALSE;
        }

        //Do we want member of role, or has ancestor of role?
        if (TRUE == $isancestor) {
            return $userinfo->isAncestor($groupinfo);
        } else {
            //check and see if user is a member of the specific group
            return $userinfo->isParent($groupinfo);
        }
    } else {
        //we have a list of group ids
        $ismember = false;
        foreach ($gidlist as $gid) {
            $roles = new xarRoles();
            $groupinfo = $roles->getRole($gid);
            if (!is_object($groupinfo)) {
               break; //go to next gid
            }
            //Now check if the user information
            if (empty($uid)) {
                $userinfo = $roles->ufindRole($uname);
            } else {
                $userinfo = $roles->getRole($uid);
            }
            if (!is_object($userinfo)) {
                break; //go to next gid
            }

            //Do we want member of role, or has ancestor of role?
            if (TRUE == $isancestor) {
                $ismember = $userinfo->isAncestor($groupinfo);
            } else {
                $ismember = $userinfo->isParent($groupinfo);
            }
            if ($ismember) {
                return TRUE; //we don't care if any of these are false - just if one is TRUE
            }
        }
     return $ismember;
    }

}
?>
