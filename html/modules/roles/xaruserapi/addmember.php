<?php
/**
 * Add a role to a group
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * addmember - add a role to a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gid'] group id
 * @param $args['uid'] role id
 * @return true on success, false on failure
 */
function roles_userapi_addmember($args)
{
    extract($args);

    if((!isset($gid)) || (!isset($uid))) {
        $msg = xarML('groups_userapi_addmember');
          throw new BadParameterException(null,$msg);   
    }

    $roles = new xarRoles();
    $group = $roles->getRole($gid);
    if($group->isUser()) {
        $msg = xarML('Did not find a group');
          throw new BadParameterException(null,$msg);   
    }

    $user = $roles->getRole($uid);

// Security Check
    if(!xarSecurityCheck('AttachRole',0,'Relation',$gid . ":" . $uid) && !xarSecurityCheck('ModerateGroupRoles',0,'Group',$gid)) return;

    if (!$group->addMember($user)) return;

    // call item create hooks (for DD etc.)
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $group->getType(); // we might have something separate for groups later on
    $pargs['itemid'] = $gid;
    $pargs['uid'] = $uid;
    //jojo - this is not correct, we are not creating a group
    //xarMod::callHooks('item', 'create', $gid, $pargs);

    return true;
}

?>