<?php
/**
 * View the current privileges
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
 * viewPrivileges - view the current privileges
 * Takes no parameters
 */
function privileges_admin_viewprivileges()
{
    // Security Check

    if(!xarSecurityCheck('EditPrivilege',0)) return xarResponseForbidden();

    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

    // call the Privileges class
    $privs = new xarPrivileges();
    $data['radiooptions'] = array('assigned'=>xarML('Assigned'),'unassigned'=>xarML('Unassigned'),'all'=>xarML('All'));
    //Load Template
    sys::import('modules.privileges.xartreerenderer');
    $renderer = new xarTreeRenderer();
    
    $data['showrealms'] = xarModGetVar('privileges', 'showrealms');
    $data['authid'] = xarSecGenAuthKey('privileges');
    $data['trees'] = $renderer->drawtrees($data['show']);
    $data['refreshlabel'] = xarML('Refresh');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');       
    
    return $data;
}


?>