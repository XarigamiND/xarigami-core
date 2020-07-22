<?php
/**
 * Modify configuration
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * modify configuration
 */
function roles_admin_modifyemailformat()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;
    $hooks = array();
    switch (strtolower($phase)) {
        case 'modify':
        default:
            $data['authid']      = xarSecGenAuthKey('roles');
            $data['updatelabel'] = xarML('Update Email Format Configuration');
            $usehtmlmail = xarModGetVar('roles', 'usehtmlmail');
            if (!empty($usehtmlmail)) {
                $data['usehtmlmail'] = $usehtmlmail;
            } else {
                $data['usehtmlmail'] = false;
            }
            $hooks = xarMod::callHooks('module', 'modifyconfig', 'roles', array('module' => 'roles'));
            $data['hooks'] = $hooks;

            break;

        case 'update':
            if (!xarVarFetch('usehtmlmail', 'checkbox', $usehtmlmail, false, XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            // Update module variables
            xarModSetVar('roles', 'usehtmlmail', $usehtmlmail);
            $htmlstatus = $usehtmlmail == TRUE ? xarML('On') : xarML('Off');
            xarMod::callHooks('module', 'updateconfig', 'roles', array('module' => 'roles'));
            $msg = xarML('HTML Email template use is turned  #(1).',$htmlstatus);
            xarTplSetMessage($msg,'status');
            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyemailformat'));
            // Return
            return true;

            break;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');
    return $data;
}
?>