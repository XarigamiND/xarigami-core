<?php
/**
 * Delete a user from a group
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
 * deleteuser - delete a user from a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gid'] group id
 * @param $args['uid'] user id
 * @return true on success, false on failure
 */
function roles_adminapi_deleteuser($args)
{
    extract($args);

    if((!isset($gid)) && (!isset($uid))) {
        $msg = xarML('roles_adminapi_deleteuser');
        throw new BadParameterException(null,$msg);
    }

    if(!xarSecurityCheck('DeleteRole',0)) return xarResponseForbidden();

    $roles = new xarRoles();
    $group = $roles->getRole($gid);
    if($group->isUser()) {
        $msg = xarML('Did not find a group');
        throw new BadParameterException(null,$msg);
    }

    $user = $roles->getRole($uid);
    if(count($user->getParents()) == 1) {
        $msg = xarML('The user only has one parent group - cannot remove');
         return xarTplModule('roles','user','errors',array('errortype' => 'remove_sole_parent'));
    }

    return $group->removeMember($user);
}
?>