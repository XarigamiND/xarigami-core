<?php
/**
 * Assign a user or group to a group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * addMember - assign a user or group to a group
 *
 * Make a user or group a member of another group.
 * This is an action page..
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param none $
 * @return none
 * @throws none
 * @todo none
 */
function roles_admin_addmember()
{
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;
    // get parameters
    if (!xarVarFetch('uid',    'int:1:', $uid)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid)) return;
    // call the Roles class and get the parent and child objects
    $roles  = new xarRoles();
    $role   = $roles->getRole($roleid);
    $member = $roles->getRole($uid);

    // Security Check
    if(!xarSecurityCheck('ModerateGroupRoles',0,'Group',$roleid) && !xarSecurityCheck('AttachRole',0,'Relation',$roleid . ":" . $uid)) return xarResponseForbidden();

    // check that this assignment hasn't already been made
    if ($member->isEqual($role))
        return xarTplModule('roles','user','errors',array('errortype' => 'self_assignment'));

    // check that this assignment hasn't already been made
    if ($member->isParent($role))
        return xarTplModule('roles','user','errors',array('errortype' => 'duplicate_assignment'));

    // check that the parent is not already a child of the child
    if ($role->isAncestor($member))
        return xarTplModule('roles','user','errors',array('errortype' => 'circular_assignment'));

    // assign the child to the parent and bail if an error was thrown
    if (!xarMod::apiFunc('roles','user','addmember', array('uid' => $uid, 'gid' => $roleid))) return;

    // if successful redirect to the next page
    if (xarSecurityCheck('ModerateGroupRoles',0,'Group',$roleid) || xarSecurityCheck('EditRole',0,'Roles',$uid)) {
        xarResponseRedirect(xarModURL('roles', 'admin', 'modifyrole', array('uid' => $uid,'pparentid'=>$roleid)));
    } else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
    }
    return;
}

?>