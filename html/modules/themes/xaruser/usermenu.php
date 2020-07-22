<?php
/**
 * main themes module user function
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * main themes module function
 * @return themes _admin_main
 */
function themes_user_usermenu($args)
{
    extract($args);
    // Security Check
    if (!xarSecurityCheck('ViewThemes',0)) return xarResponseForbidden();

    if(!xarVarFetch('phase','notempty', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));
    switch(strtolower($phase)) {
        case 'menu':

            $icon = xarTplGetImage('themes.gif','themes'); //'modules/themes/xarimages/themes.gif';
            $current = xarModURL('roles', 'user', 'account', array('moduleload' => 'themes'));
            $data = xarTplModule('themes', 'user', 'usermenu_icon', array('icon' => $icon, 'current' => $current));

            break;

        case 'form':
            // Get list of active themes, system (0) or user (2)
            $filter['Class'] = array('0', '2');
            $data['themes'] = xarMod::apiFunc('themes', 'admin', 'getthemelist', array('filter'=>$filter));

            $defaulttheme = xarModUserVars::get('themes', 'default');

            $name = xarUserGetVar('name');
            $uid = xarUserGetVar('uid');
            $authid = xarSecGenAuthKey('themes');
            $data = xarTplModule('themes', 'user', 'usermenu_form',
                array('authid' => $authid,
                      'name' => $name,
                      'uid' => $uid,
                      'defaulttheme' => $defaulttheme,
                      'themes' => $data['themes']));
            break;

        case 'update':
            if (!xarVarFetch('uid', 'int:1:', $uid)) return;
            if (!xarVarFetch('defaulttheme', 'str:1:100', $defaulttheme, '', XARVAR_NOT_REQUIRED)) return;
            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;
            $themeInfo = xarThemeGetInfo($defaulttheme);
            //TODO: save name or regid? default theme is also set as 'name' - resolve this
            xarModSetUserVar('themes', 'default', $themeInfo['name'], $uid);
            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account', array('moduleload' => 'themes')));

            break;
    }

    return $data;
}

?>
