<?php
/**
 * Deactivate a theme
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Deactivate a theme
 *
 * Loads theme admin API and calls the setstate
 * function    to actually    perfrom    the    deactivation,
 * then    redirects to the list function with    a status
 * message and returns true.
 * @author Marty Vance
 * @access public
 * @param id $ the theme id    to deactivate
 * @return bool true
 */
function themes_admin_deactivate()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;

    //Checking if the user has already passed thru the GUI:
    xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);

    // set the target location (anchor) to go to within the page
    $minfo=xarThemeGetInfo($id);
    $target=$minfo['name'];

    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    // deactivate
    $deactivated = xarMod::apiFunc('themes','admin','setstate',array('regid' => $id,'state' => XARTHEME_STATE_INACTIVE));

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}
?>