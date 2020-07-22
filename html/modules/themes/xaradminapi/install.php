<?php
/**
 * Install a theme.
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage XarigamiThemes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Install a theme.
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @throws NO_PERMISSION
 */
function themes_adminapi_install($args)
{
    //    static $installed_ids = array();
    $mainId = $args['regid'];

    $installed_ids[] = $mainId;

    // Argument check
    if (!isset($mainId)) {
        $msg = xarML('Missing theme regid (#(1)).', $mainId);
        throw new BadParameterException(null,$msg);
    }

    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('themes', 'admin', 'checkmissing')) return;

    // Make xarModGetInfo not cache anything...
    //We should make a funcion to handle this or maybe whenever we
    //have a central caching solution...
    $GLOBALS['xarTheme_noCacheState'] = true;

    // Get module information
    $modInfo = xarThemeGetInfo($mainId);

    if (!isset($modInfo)) {
        throw new BadParameterException('modInfo');
    }

    switch ($modInfo['state']) {
        case XARTHEME_STATE_ACTIVE:
        case XARTHEME_STATE_UPGRADED:
            //It is already installed
            return true;
        case XARTHEME_STATE_INACTIVE:
            $initialised = true;
            break;
        default:
            $initialised = false;
            break;
    }

    //Checks if the theme is already initialised
    if (!$initialised) {
        // Finally, now that dependencies are dealt with, initialize the module
        if (!xarMod::apiFunc('themes', 'admin', 'initialise', array('regid' => $mainId))) {
            $msg = xarML('Unable to initialize theme "#(1)".', $modInfo['displayname']);
            throw new BadParameterException(null,$msg);
        }
    }

    // And activate it!
    if (!xarMod::apiFunc('themes', 'admin', 'activate', array('regid' => $mainId))) {
        $msg = xarML('Unable to activate module "#(1)".', $modInfo['displayname']);
        throw new BadParameterException(null,$msg);
    }
     xarLogMessage('THEMES: A theme with Regid '.$mainId.' was installed by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);

    return true;
}
?>