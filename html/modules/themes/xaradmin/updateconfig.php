<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update the configuration parameters of the
 * module given the information passed back by the modification form
 */
function themes_admin_updateconfig()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;
    // Security Check
    if (!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();
    // Get parameters
    if (!xarVarFetch('sitename', 'str:1:', $sitename, 'Your Site Name', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('separator', 'str:1:', $separator, ' - ', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pagetitle', 'str:1:', $pagetitle, 'default', XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('slogan', 'str::', $slogan, 'Your Site Slogan', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('footer', 'str:1:', $footer, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('copyright', 'str:1:', $copyright, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('AtomTag', 'str:1:', $atomtag, '', XARVAR_NOT_REQUIRED)) return;
    // enable or disable dashboard
    if(!xarVarFetch('dashboard', 'checkbox', $dashboard, FALSE, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('admintheme', 'str', $admintheme,'', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('adminpagemenu', 'checkbox', $adminpagemenu, FALSE, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('showbreadcrumbs', 'checkbox', $showbreadcrumbs, TRUE, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('showuserbreadcrumbs', 'checkbox', $showuserbreadcrumbs,  TRUE, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dashtemplate', 'str:1:', $dashtemplate, '', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('usermenu', 'checkbox', $usermenu, FALSE, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('showmodheader', 'checkbox', $showmodheader, TRUE, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('showusermodheader', 'checkbox', $showusermodheader,  TRUE, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('useadmintheme', 'checkbox', $useadmintheme, FALSE, XARVAR_DONT_SET)) {return;}
    xarModSetVar('themes', 'SiteName', $sitename);
    xarModSetVar('themes', 'SiteTitleSeparator', $separator);
    xarModSetVar('themes', 'SiteTitleOrder', $pagetitle);
    xarModSetVar('themes', 'SiteSlogan', $slogan);
    xarModSetVar('themes', 'SiteCopyRight', $copyright);
    xarModSetVar('themes', 'SiteFooter', $footer);
    xarModSetVar('themes', 'AtomTag', $atomtag);

    $dashtemplate = basename(trim($dashtemplate),'.xt');
    xarModSetVar('themes', 'adminpagemenu', ($adminpagemenu) ? 1 : 0);
    xarModSetVar('themes', 'dashtemplate', $dashtemplate);
    xarModSetVar('themes', 'showbreadcrumbs', $showbreadcrumbs);
    xarModSetVar('themes', 'showmodheader', $showmodheader);
    xarModSetVar('themes', 'showuserbreadcrumbs', $showuserbreadcrumbs);
    xarModSetVar('themes', 'showusermodheader', $showusermodheader);

    $themedir = xarTplGetThemeDir();
    if ($dashboard == TRUE) {
        if (empty($dashtemplate)) {
             $msg = xarML('There is no dashboard template selected. The Dashboard will be switched OFF.');
            xarTplSetMessage($msg,'error');
            $dashboard = FALSE;
        } elseif (!file_exists($themedir.'/pages/'.$dashtemplate.'.xt')) {
            $msg = xarML('The dashboard template named does not exist in this theme. The Dashboard will be switched OFF until a valid dashboard template is available.');
            xarTplSetMessage($msg,'error');
            $dashboard = FALSE;
        }
        if (($dashboard == TRUE) && ($useadmintheme == TRUE)) {
            $msg = xarML('The Dashboard is currently turned on and the Admin Theme.
                The Dashboard will be switched OFF while the Admin Theme is ON.');
              xarTplSetMessage($msg,'alert');
             $dashboard = FALSE;
        }
    }
    xarModSetVar('themes', 'usedashboard', ($dashboard) ? 1 : 0);

    if (($dashboard == TRUE) && ($useadmintheme == TRUE)) {
        $msg = xarML('The Dashboard is currently turned on. Please  does not exist in this theme. The Dashboard will be switched OFF until a valid dashboard template is available.');
            xarTplSetMessage($msg,'error');
    }
    xarModSetVar('themes', 'admintheme', trim($admintheme));
    xarModSetVar('themes', 'useadmintheme', $useadmintheme);

    // make sure we dont miss empty variables (which were not passed thru)
    if (empty($selstyle)) $selstyle = 'plain';
    if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;
    if (empty($selsort)) $selsort = 'namedesc';
    if (!empty($flushmsg)) {
        xarSessionSetVar('statusmsg.themes',$flushmsg);
    }
    xarModSetVar('themes', 'hidecore', $hidecore);
    xarModSetVar('themes', 'selstyle', $selstyle);
    xarModSetVar('themes', 'selfilter', $selfilter);
    xarModSetVar('themes', 'selsort', $selsort);

    // Only go through updatehooks() if there was a change.
    if (xarMod::isHooked('themes', 'roles') != $usermenu) {
        $hooked_roles = array();
        if ($usermenu) {
            $hooked_roles[0] = 1;
            // turning on, so remember previous hook config
            if (xarMod::isHooked('themes', 'roles', 1)) {
                xarModSetVar('themes', 'group_hooked', true);
            }
            $msg = xarML('Users can now set a theme in their User Account page');
            xarTplSetMessage($msg,'status');
        } else {
            // turning off, so restore previous hook config
            if (xarModGetVar('themes', 'group_hooked')) {
                $hooked_roles[0] = 2;
                $hooked_roles[1] = 1; // groups only
                xarModSetVar('themes', 'group_hooked', false);
            } else {
                $hooked_roles[0] = 0; // nothing hooked at all
            }
            $msg = xarML("Users' ability to set a theme in their User Account is now turned Off");
            xarTplSetMessage($msg,'status');
        }

        // we need to redirect instead of using xarMod::apiFunc() because the
        // updatehooks() API function calls xarVarFetch rather than taking
        // input via an $args array.
        $redirecturl = xarModURL('modules', 'admin', 'updatehooks', array(
            'authid' => xarSecGenAuthKey('modules'),
            'curhook' => 'themes',
            'hooked_roles' => $hooked_roles,
            'return_url' => xarModURL('themes', 'admin', 'modifyconfig'),
        ));
    } else {
        $redirecturl = xarModURL('themes', 'admin', 'modifyconfig');
    }
    $msg = xarML("Configuration settings were successfully updated.");
    xarTplSetMessage($msg,'status');
    xarLogMessage('THEMES: Configuration settings were  modified by user  '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    // lets update status and display updated configuration
    xarResponseRedirect($redirecturl);
    // Return
    return true;
}

?>
