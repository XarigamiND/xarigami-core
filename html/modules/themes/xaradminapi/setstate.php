<?php
/**
 * Set the state of a theme
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Set the state of a theme
 * @param $args['regid'] the theme id
 * @param $args['state'] the state
 * @throws BAD_PARAM,NO_PERMISSION
 */
function themes_adminapi_setstate($args)
{
    // Get arguments from argument array

    extract($args);

    // Argument check
    if ((!isset($regid)) || (!isset($state))) {
        $msg = xarML('Empty regid (#(1)) or state (#(2)).', $regid, $state);
        throw new EmptyParameterException(null,$msg);
    }

// Security Check
    if(!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    // Clear cache to make sure we get newest values
    if (xarCoreCache::isCached('Theme.Infos', $regid)) {
        xarCoreCache::delCached('Theme.Infos', $regid);
    }

    //Get theme info
    $themeInfo = xarThemeGetInfo($regid);

    //Set up database object
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $oldState = $themeInfo['state'];

    $issystem = FALSE;
    $isdefault = FALSE;
    $defaulttheme = xarModGetVar('themes','default');
    $defaultid = xarThemeGetIdFromName($defaulttheme);
    if ($defaultid == $regid) $isdefault = TRUE;
    if ($themeInfo['class'] == 0) $issystem = TRUE;

        switch ($state) {
        case XARTHEME_STATE_UNINITIALISED:

            if (($oldState == XARTHEME_STATE_MISSING_FROM_UNINITIALISED) ||
                ($oldState == XARTHEME_STATE_ERROR_UNINITIALISED)) break;

            if ($oldState != XARTHEME_STATE_INACTIVE) {
                // New Theme - nothing to do
                /*
                $theme_statesTable = $xartable['theme_states'];
                $query = "SELECT * FROM $theme_statesTable WHERE xar_regid = ?";
                $result = $dbconn->Execute($query,array($regid));
                if (!$result) return;
                if ($result->EOF) {
                    $query = "INSERT INTO $theme_statesTable
                       (xar_regid, xar_state)
                        VALUES (?,?)";
                    $bindvars = array($regid,$state);
                    $result = $dbconn->Execute($query,$bindvars);
                    if (!$result) return;
                }*/
                return true;
            }

            break;
        case XARTHEME_STATE_INACTIVE:
            if (($oldState != XARTHEME_STATE_UNINITIALISED) &&
                ($oldState != XARTHEME_STATE_ACTIVE) &&
                ($oldState != XARTHEME_STATE_MISSING_FROM_INACTIVE) &&
                ($oldState != XARTHEME_STATE_ERROR_INACTIVE) &&
                ($oldState != XARTHEME_STATE_UPGRADED)) {
                xarSession::setVar('errormsg', xarML('Invalid theme state transition'));
                return false;
            }
            break;
        case XARTHEME_STATE_ACTIVE:
            if ($issystem ||$isdefault) break;
            if (($oldState != XARTHEME_STATE_INACTIVE) &&
                ($oldState != XARTHEME_STATE_ERROR_ACTIVE) &&
                ($oldState != XARTHEME_STATE_MISSING_FROM_ACTIVE)) {
                xarSession::setVar('errormsg', xarML('Invalid theme state transition'));
                return false;
            }
            break;
        case XARTHEME_STATE_UPGRADED:
            if (($oldState != XARTHEME_STATE_INACTIVE) &&
                ($oldState != XARTHEME_STATE_ACTIVE) &&
                ($oldState != XARTHEME_STATE_ERROR_UPGRADED) &&
                $oldState != XARTHEME_STATE_MISSING_FROM_UPGRADED) {
                xarSession::setVar('errormsg', xarML('Invalid theme state transition'));
                return false;
            }
            break;
    }
    //Get current theme mode to update the proper table

    $themeMode  = isset($themeInfo['mode'])?$themeInfo['mode']:1;
    $themesTable = $xartable['themes'];

    $sql = "UPDATE $themesTable SET xar_state = ? WHERE xar_regid =?";
    $bindvars = array($state,$regid);
    $result = $dbconn->Execute($sql, $bindvars);
    if (!$result) return;

    //update cache info
     $themeInfo['state']=$state;
    xarCoreCache::setCached('Theme.Infos',$regid,$themeInfo);
    return true;
}

?>