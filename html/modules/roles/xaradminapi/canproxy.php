<?php
/**
 * Check if a user has privs to login as another user
 *
 * @package modules
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Check if a user can currently login as another user
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 * @return true on success, false if not
 */
function roles_adminapi_canproxy($args)
{
    //initialize
    $canproxy= false;
    
    if (!xarUserIsLoggedIn()) {
        return $canproxy;
    } elseif (!xarSecurityCheck('EditGroupRoles',0) && !xarSecurityCheck('EditRole',0)) {
        return $canproxy;
    }
      
    $proxygroup = xarModGetVar('roles','defaultproxy');

    if (isset($proxygroup) && $proxygroup > 0) {
        //check if user is in proxy group
        $currentuser = xarUserGetVar('uid');
        $ismember = xarMod::apiFunc('roles','user','checkgroup',array('uid'=>$currentuser, 'gid'=>$proxygroup));

        if ($ismember) {
             $canproxy = true;
        } else {
             $canproxy = false;
        }
    
    } else {
         $canproxy = false;
    }

    return  $canproxy;
}

?>