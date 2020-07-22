<?php
/**
 * View an error with a module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * View an error with a module
 * @param id the module's registered id
 * @returns bool
 * @return true on success, error message on failure
 */
function themes_admin_viewerror()
{
    // Get parameters
    xarVarFetch('id', 'id', $regId);

    //if (!xarSecConfirmAuthKey()) return;

    // Get module information from the database
    $dbThemeinfo = xarMod::apiFunc('themes','admin','getdbThemes',
                              array('regId' => $regId));

    $dbTheme = current($dbThemeinfo);
    // Get module information from the filesystem
    $fileTheme = xarMod::apiFunc('themes','admin','getfilethemes',
                                array('regId' => $regId));

  //  if (!isset($fileTheme)) return;

    // Get the module state and display appropriate template
    // for the error that was encountered with the module
    switch($dbTheme['state']) {
        case XARTHEME_STATE_ERROR_UNINITIALISED:
        case XARTHEME_STATE_ERROR_INACTIVE:
        case XARTHEME_STATE_ERROR_ACTIVE:
        case XARTHEME_STATE_ERROR_UPGRADED:
            // Set template to 'update'
            $template = 'errorupdate';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbTheme['name'])) {
                $data['themename'] = $dbTheme['name'];
            } else {
                $data['themename'] = xarML('[unknown]');
            }

            // Set db version
            if (isset($dbTheme['version'])) {
                $data['dbversion'] = $dbTheme['version'];
            } else {
                $data['dbversion'] = xarML('[unknown]');
            }

            // Set file version number of module
            if (isset($fileTheme['version'])) {
                $data['fileversion'] = $fileTheme['version'];
            } else {
                $data['fileversion'] = xarML('[unknown]');
            }
            break;

        case XARTHEME_STATE_MISSING_FROM_UNINITIALISED:
        case XARTHEME_STATE_MISSING_FROM_INACTIVE:
        case XARTHEME_STATE_MISSING_FROM_ACTIVE:
        case XARTHEME_STATE_MISSING_FROM_UPGRADED:
            // Set template to 'missing'
            $template = 'missing';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbTheme['name'])) {
                $data['themename'] = $dbTheme['name'];
            } else {
                $data['themename'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbTheme['version'])) {
                $data['dbversion'] = $dbTheme['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileTheme['version'])) {
                $data['fileversion'] = $fileTheme['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            break;

        default:
            break;
    }

    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');
    // Return the template variables to BL
    return xarTplModule('themes', 'admin', $template, $data);
}

?>
