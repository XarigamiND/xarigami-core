<?php
/**
 * Remove a privilege
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
 * removeprivilege - remove a privilege
 * prompts for confirmation
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_removeprivilege()
{
    if (!xarVarFetch('privid',       'int:1:', $privid)) return;
    if (!xarVarFetch('roleid',       'int:1:', $roleid)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return;
    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role  = $roles->getRole($roleid);

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid'   => $parent->getID(),
                           'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    // Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($privid);
    // some assignments can't be removed, for your own good
    if ((($roleid == 1) && ($privid == 1)) ||
        (($roleid == 2) && ($privid == 6)) ||
        (($roleid == 4) && ($privid == 2)))
        {
            return xarTplModule('roles','user','errors',array('errortype' => 'remove_privilege'));
        }

    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
    // some info for the template display
    $rolename = $role->getName();
    $privname = $priv->getName();

    if (empty($confirmation)) {
        // Load Template
        $data['authid']   = xarSecGenAuthKey('roles');
        $data['roleid']   = $roleid;
        $data['privid']   = $privid;
        $data['ptype']    = $role->getType();
        $data['privname'] = $privname;
        $data['rolename'] = $rolename;
        $data['removelabel'] = xarML('Remove');
        return $data;
    } else {
        // Check for authorization code
        if (!xarSecConfirmAuthKey()) return;
        // Try to remove the privilege and bail if an error was thrown
        if (!$role->removePrivilege($priv)) return;

        // We need to tell some hooks that we are coming from the add privilege screen
        // and not the update the actual roles screen.  Right now, the keywords vanish
        // into thin air.  Bug 1960 and 3161
        xarCoreCache::setCached('Hooks.all','noupdate',1);

// CHECKME: do we really want to do that here (other than for flushing the cache) ?
        // call update hooks and let them know that the role has changed
        $pargs['module'] = 'roles';
        $pargs['itemid'] = $roleid;
        xarMod::callHooks('item', 'update', $roleid, $pargs);

        // redirect to the next page
        xarResponseRedirect(xarModURL('roles', 'admin', 'showprivileges', array('uid' => $roleid)));
    }
}

?>