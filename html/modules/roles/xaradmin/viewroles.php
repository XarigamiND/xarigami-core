<?php
/**
 * View the current groups
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
 * viewRoles - view the current groups
 * Takes no parameters
 */
function roles_admin_viewroles()
{
    // Clear Session Vars
    xarSession::delVar('roles_statusmsg');
    // Security Check - don't throw an exception, handle the response
    if (!xarSecurityCheck('EditRole',0)) return xarResponseForbidden();
    // Call the Roles class
    $roles = new xarRoles();

    sys::import('modules.roles.xartreerenderer');
    $renderer = new xarTreeRenderer();
    // Load Template
    $data['authid'] = xarSecGenAuthKey('roles');
    $data['tree']   = $renderer->drawtree($renderer->maketree());
    return $data;
}

?>