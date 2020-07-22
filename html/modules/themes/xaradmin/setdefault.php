<?php
/**
 * Default theme for site
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Default theme for site
 *
 * Sets the module var for the default site theme.
 *
 * @param id the theme id to set
 * @return bool true
 */
function themes_admin_setdefault()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;
    if (!xarSecurityCheck('AdminTheme')) return;
    if (!xarVarFetch('id', 'int:1:', $defaulttheme)) return;

    $whatwasbefore = xarModGetVar('themes', 'default');

    if (!isset($defaulttheme)) {
        $whatwasbeforeid = xarGetIdFromName($whatwasbefore);
        $defaulttheme = $whatwasbeforeid;
    }
    $themeInfo = xarThemeGetInfo($defaulttheme);

     //jojo - what is this for?
    if ($themeInfo['class'] ==1) { //utility theme
        xarResponseRedirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }

    if (xarCoreCache::isCached('Mod.Variables.themes', 'default')) { //string value
        xarCoreCache::delCached('Mod.Variables.themes', 'default');
    }

    //update the database - activate the theme
    if (!xarMod::apiFunc('themes','admin','install', array('regid'=>$defaulttheme))) {
        xarResponseRedirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }

    // update the data
    xarTplSetThemeDir($themeInfo['directory']);
    xarModSetVar('themes', 'default', $themeInfo['name']);
    xarLogMessage('THEMES: Default theme was set to '.$themeInfo['name'].' by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);

    // set the target location (anchor) to go to within the page
    $target = $themeInfo['name'];

    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
}
?>