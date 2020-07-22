<?php
/**
 * Initialise a theme
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Initialise a theme
 *
 * Loads theme admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * @author Marty Vance
 * @param id $ the theme id to initialise
 * @return bool true
 */
function themes_admin_initialise()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;
    // Initialise theme
    $initialised = xarMod::apiFunc('themes', 'admin', 'initialise',
        array('regid' => $id));

    if (!isset($initialised)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>