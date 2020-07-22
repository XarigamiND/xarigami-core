<?php
/**
 * Update hooks by hook module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Modules
 * @copyright (C) 2006-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Update hooks by hook module
 *
 * @param string $curhook  - name of the module providing the hook
 * @author Xarigami Development Team
 */
function modules_admin_updatehooks()
{
// Security Check
    if(!xarSecurityCheck('AdminModules',0)) {return xarResponseForbidden();}

    if (!xarSecConfirmAuthKey()) {return;}
    if (!xarVarFetch('curhook', 'str:1:', $curhook)) {return;}
    $regId = xarMod::getId($curhook);
    if (!isset($curhook) || !isset($regId)) {
        $msg = xarML('Invalid hook');
        throw new BadParameterException($msg);
    }

    // Only update if the module is active.
    $modinfo = xarMod::getInfo($regId);
    if (!empty($modinfo) && xarMod::isAvailable($modinfo['name'])) {
        // Pass to API
        $updated = xarMod::apiFunc('modules', 'admin', 'updatehooks',
            array('regid' => $regId)
        );
        if (!isset($updated)) {return;}
    }

    if (!xarVarFetch('return_url', 'isset', $return_url, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } else {
        xarResponseRedirect(xarModURL('modules', 'admin', 'hooks',
                                      array('hook' => $curhook)));
    }
    return true;
}

?>