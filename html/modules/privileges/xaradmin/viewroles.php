<?php
/**
 * Display the roles this privilege is assigned to
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * viewroles - display the roles this privilege is assigned to
 */
function privileges_admin_viewroles()
{
    // Security Check
    if(!xarSecurityCheck('EditRole',0)) return xarResponseForbidden();

    $data = array();

    if (!xarVarFetch('pid',  'isset', $pid,          NULL,       XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) {return;}

    // Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

    //Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);

    //Get the array of current roles this privilege is assigned to
    $curroles = array();
    foreach ($priv->getRoles() as $role) {
        array_push($curroles, array('roleid'=>$role->getID(),
                                    'name'=>$role->getName(),
                                    'type'=>$role->getType(),
                                    'uname'=>$role->getUser(),
                                    'pass'=>$role->getPass(),
                                    'auth_module'=>$role->getAuthModule()));
    }

    // Load Template
     sys::import('modules.privileges.xartreerenderer');
    $renderer = new xarTreeRenderer();

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                                    'parentname'=>$parent->getName());
    }
    $data['radiooptions'] = array('assigned'=>xarML('Assigned'),'unassigned'=>xarML('Unassigned'),'all'=>xarML('All'));

    $data['pname'] = $priv->getName();
    $data['pid'] = $pid;
    $data['roles'] = $curroles;
    //    $data['allgroups'] = $roles->getAllPrivileges();
    $data['removeurl'] = xarModURL('privileges',
                             'admin',
                             'removerole',
                             array('pid'=>$pid));
    $data['trees'] = $renderer->drawtrees($data['show']);
    $data['parents'] = $parents;
    $data['groups'] = xarMod::apiFunc('roles','user','getallgroups');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    return $data;
}

?>
