<?php
/**
 * Delete a role
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
 * deleteRole - delete a role
 * prompts for confirmation
 */
function roles_admin_deleterole()
{
    // get parameters
    if (!xarVarFetch('uid',          'int:1:', $uid)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('groupuid',  'str:1:', $groupuid,  '', XARVAR_NOT_REQUIRED)) return;
     if (!xarVarFetch('returnurl',  'str:0:', $returnurl,  '', XARVAR_NOT_REQUIRED)) return;
    // Call the Roles class
    $roles = new xarRoles();
    // get the role to be deleted
    $role = $roles->getRole($uid);
    $type = $role->isUser() ? 0 : 1;
    $roletype = $type;
    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    $allowedgids = array();
    foreach ($role->getParents() as $parent) {
        $parentid = $parent->getID();
        $parents[] = array('parentid'   => $parent->getID(),
                           'parentname' => $parent->getName());
        if  (xarSecurityCheck('DeleteGroupRoles',0,'Group',$parentid) || xarSecurityCheck('DeleteRole',0,'Roles',$parentid)) {
            $allowedgids[] = $parentid;
        }
    }
    $data['parents'] = $parents;
    if (!isset($groupid) && is_array($allowedgids) && !empty($allowedgids)) {
        $groupuid = current($allowedgids);
    }
    $name = $role->getName();

    // Security Check
    $data['frozen']= FALSE;
     if ($roletype == ROLES_GROUPTYPE) {
        if (!xarSecurityCheck('DeleteRole',0,'Roles',$groupuid))  return xarResponseForbidden();

    } else {
        $inallowedgroup = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$allowedgids,'uid'=>$uid));
        if (!$inallowedgroup  && !xarSecurityCheck('DeleteRole',0,'Roles',$uid)) {
            $data['frozen'] = !xarSecurityCheck('DeleteRole',0,'Roles',$uid);
             return xarTplModule('roles','user','errors',array('errortype' => 'role_required', 'var1' => $role->getName()));
        }
    }
    $data['groupuid'] = $groupuid;


// Prohibit removal of any groups that have children
    if($role->countChildren()) {
         return xarTplModule('roles','user','errors',array('errortype' => 'remove_nonempty_group','var1' => $role->getName()));
    }
// Prohibit removal of any groups or users the system needs
    if($uid == xarModGetVar('roles','admin')) {
         return xarTplModule('roles','user','errors',array('errortype' => 'remove_siteadmin', 'var1' =>  $role->getName()));
    }

    //Prohibit removal of special roles such as myself and anonymous

    $anon = _XAR_ID_UNREGISTERED;
    $everybody = xarModGetVar('roles','everybody');
    $myselfinfo = xarMod::apiFunc('roles','user','get',array('uname'=>'myself'));
    $myself = $myselfinfo['uid'];
    $administrators = xarUFindRole('Administrators');
    $adminguid = $administrators->uid;
    $defaultusergroup = xarMod::apiFunc('roles','user','getdefaultgroup');
    $nodelete = array($anon,$everybody,$myself,$adminguid);
    if (in_array($uid,$nodelete)){

        return xarTplModule('roles','user','errors',array('errortype' => 'role_required', 'var1' => $role->getName()));

    }
    if(strtolower($role->getName()) == strtolower($defaultusergroup)) {

        return xarTplModule('roles','user','errors',array('errortype' => 'remove_defaultusergroup', 'var1' => $role->getName()));
    }

    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');
    if (empty($confirmation)) {
        // Load Template
        $data['authid']  = xarSecGenAuthKey('roles');
        $data['uid']     = $uid;
        $data['ptype']   = $role->getType();
        $data['deletelabel'] = xarML('Delete');
        $data['name']    = $name;
        return $data;
    } else {
        // Check for authorization code
        if (!xarSecConfirmAuthKey()) return;
        // Check to make sure the user is not active on the site.
        $check = xarMod::apiFunc('roles', 'user', 'getactive',
                              array('uid' => $uid));

    if (empty($check)) {
            // Try to remove the role and bail if an error was thrown
        if (!$role->remove()) {
            return;
        } else {
            $tname = ($roletype == ROLES_GROUPTYPE) ? xarML('Group'): xarML('User');
             $msg = xarML('The #(1) named "#(2)" has been marked as deleted.',$tname, $name);
             xarTplSetMessage($msg,'status');
        }
            // call item delete hooks (for DD etc.)

            $pargs['module']   = 'roles';
            $pargs['itemtype'] = $type; // we might have something separate for groups later on
            $pargs['itemid']   = $uid;
            xarMod::callHooks('item', 'delete', $uid, $pargs);
        } else {
            return xarTplModule('roles','user','errors',array('errortype' => 'remove_active_session', 'var1' => $role->getName()));
        }
        // redirect to the next page
        $returnurl = isset($returnurl) && !empty($returnurl)? $returnurl:xarModURL('roles', 'admin', 'showusers');

        xarResponseRedirect($returnurl);
    }
}
?>