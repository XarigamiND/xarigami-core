<?php
/**
 * Modify configuration
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
 * modify configuration
 */
function roles_admin_modifynotice()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole',0)) return xarResponseForbidden();
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;
    $hooks = array();
    switch (strtolower($phase)) {
        case 'modify':
        default:
            //$data['ips']         = unserialize(xarModGetVar('roles', 'disallowedips'));
            $data['authid']      = xarSecGenAuthKey('roles');
            $data['updatelabel'] = xarML('Update Notification Configuration');

            $hooks = xarMod::callHooks('module', 'modifyconfig', 'roles',
                array('module' => 'roles'));
            $data['hooks'] = $hooks;

            break;

        case 'update':
            if (!xarVarFetch('askwelcomeemail',      'checkbox', $askwelcomeemail,      false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askdeactivationemail', 'checkbox', $askdeactivationemail, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askvalidationemail',   'checkbox', $askvalidationemail,   false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askpendingemail',      'checkbox', $askpendingemail,      false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askpasswordemail',     'checkbox', $askpasswordemail,     false, XARVAR_NOT_REQUIRED)) return;
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            // Update module variables
            xarModSetVar('roles', 'askwelcomeemail', $askwelcomeemail);
            xarModSetVar('roles', 'askdeactivationemail', $askdeactivationemail);
            xarModSetVar('roles', 'askvalidationemail', $askvalidationemail);
            xarModSetVar('roles', 'askpendingemail', $askpendingemail);
            xarModSetVar('roles', 'askpasswordemail', $askpasswordemail);

            xarMod::callHooks('module', 'updateconfig', 'roles',
                array('module' => 'roles'));
            $msg = xarML('Mail notifications have been updated.');
            xarTplSetMessage($msg,'status');
            xarResponseRedirect(xarModURL('roles', 'admin', 'modifynotice'));
            // Return
            return true;

            break;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');
    return $data;
}
?>