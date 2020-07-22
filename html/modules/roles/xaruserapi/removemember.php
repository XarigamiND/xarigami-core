<?php
/**
 * Remove a role from a group
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
 * removemember - remove a role from a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gid'] group id
 * @param $args['uid'] role id
 * @return true on succes, false on failure
 */
function roles_userapi_removemember($args)
{
    extract($args);

    if((!isset($gid)) || (!isset($uid))) {
        $msg = xarML('groups_userapi_removemember');
        throw new BadParameterException(null,$msg);
    }

    $roles = new xarRoles();
    $group = $roles->getRole($gid);
    if($group->isUser()) {
        $msg = xarML('Did not find a group');
       throw new BadParameterException(null,$msg);
    }

    $user = $roles->getRole($uid);
    
    $isancestor = xarIsAncestor($group->name,$user->uname);

    if (!$isancestor) {
        return;
    }
// Security Check
    if(!xarSecurityCheck('ModerateGroupRoles',1,'Group',$gid) && !xarSecurityCheck('RemoveRole',1,'Relation',$gid . ":" . $uid)) return;

    if (!$group->removeMember($user)) return;

    // call item create hooks (for DD etc.)
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $group->getType(); // we might have something separate for groups later on
    $pargs['itemid'] = $gid;
    $pargs['uid'] = $uid;
    //jojo - this is not correct - we are not deleting a group
    //xarMod::callHooks('item', 'delete', $gid, $pargs);
    return true;
}

?>