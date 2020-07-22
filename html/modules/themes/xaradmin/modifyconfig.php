<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * This is a standard function to modify the configuration parameters of the
 * module
 *
 * @author Xarigami Core Development Team
 */
function themes_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();
    // everything else happens in Template for now
    // prepare labels and values for display by the template
    $data['title'] = xarVarPrepForDisplay(xarML('Configure Themes'));
    $data['configoverview'] = xarVarPrepForDisplay(xarML('Configure Overview'));
    $data['showhelplabel'] = xarVarPrepForDisplay(xarML('Show module "Help" in the menu:'));
    $data['showhelp'] = xarModGetVar('modules', 'showhelp') ? 'checked' : '' ;
    $data['submitbutton'] = xarVarPrepForDisplay(xarML('Submit'));
    // Dashboard
    $data['dashboard']= xarModGetVar('themes', 'usedashboard');
    $data['adminpagemenu']= xarModGetVar('themes', 'adminpagemenu');
    $data['admintheme']= xarModGetVar('themes', 'admintheme');
    $data['useadmintheme']= xarModGetVar('themes', 'useadmintheme');
    $data['dashtemplate']= trim(xarModGetVar('themes', 'dashtemplate'));
    if (!isset($data['dashtemplate']) || trim ($data['dashtemplate']=='')) {
        $data['dashtemplate']='dashboard';
    }


    $data['SiteName'] = xarModGetVar('themes', 'SiteName');
    $data['SiteSlogan'] = xarModGetVar('themes', 'SiteSlogan');
    $data['SiteCopyRight'] = xarModGetVar('themes', 'SiteCopyRight');
    $data['SiteFooter'] = xarModGetVar('themes', 'SiteFooter');
    $separator = xarModGetVar('themes', 'SiteTitleSeparator');
    $data['SiteTitleSeparator'] = $separator;
    $data['SiteTitleOrder'] = xarModGetVar('themes', 'SiteTitleOrder');
    $data['sitetitleorderoptions'] = array('default' =>xarML('Site Name') .$separator. xarML('Module Name').$separator.xarML('Page Name'),
                                           'sp' =>xarML('Site Name').$separator.xarML('Page Name'),
                                           'mps'=>xarML('Module Name').$separator.xarML('Page Name').$separator.xarML('Site Name'),
                                           'pms'=>xarML('Page Name').$separator.xarML('Module Name').$separator.xarML('Site Name'),
                                           'ps' =>xarML('Page Name').$separator.xarML('Site Name'),
                                           'to' => xarML('Page Name'),
                                           'theme'=>xarML('Theme Driven')
                                           );
    $data['roleshooked'] = xarMod::isHooked('themes', 'roles');
    $data['showbreadcrumbs'] = xarModGetVar('themes', 'showbreadcrumbs');
    $data['showmodheader'] = xarModGetVar('themes', 'showmodheader');
    $data['showuserbreadcrumbs'] = xarModGetVar('themes', 'showuserbreadcrumbs');
    $data['showusermodheader'] = xarModGetVar('themes', 'showusermodheader');
    $data['atom'] = xarThemeIsAvailable('atom');
    $data['atomtag'] = xarModGetVar('themes', 'AtomTag', 1);
    $data['dashdir'] = xarTplGetThemeDir().'/pages/';
    // Get list of active themes, system (0) or user (2)
    $filter['Class'] = array('0', '2');
    $themelist = xarMod::apiFunc('themes', 'admin', 'getthemelist', array('filter'=>$filter));
    $themes = array();
    foreach($themelist as $k =>$v) {
        $themes[] = array('id'=>$v['name'], 'name'=>$v['displayname']);
    }
    $data['themes']= $themes;
    $defaulttheme = xarModUserVars::get('themes', 'default');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');

    // everything else happens in Template for now
    return $data;
}
?>
