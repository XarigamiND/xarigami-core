<?php
/**
 * View an error with a module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * View an error with a module
 *
 * @author Xarigami Development Team
 * @param id the module's registered id
 * @returns bool
 * @return true on success, error message on failure
 */
function modules_admin_viewerror()
{
    // Get parameters
    xarVarFetch('id', 'id', $regId);

    //if (!xarSecConfirmAuthKey()) return;

    // Get module information from the database
    $dbModule = xarMod::apiFunc('modules',
                              'admin',
                              'getdbmodules',
                              array('regId' => $regId));
   // if (!isset($dbModule)) return;

    // Get module information from the filesystem
    $fileModule = xarMod::apiFunc('modules',
                                'admin',
                                'getfilemodules',
                                array('regId' => $regId));
  //  if (!isset($fileModule)) return;

    // Get the module state and display appropriate template
    // for the error that was encountered with the module
    if (!isset($template)) $template = '';
    switch($dbModule['state']) {
        case XARMOD_STATE_ERROR_UNINITIALISED:
        case XARMOD_STATE_ERROR_INACTIVE:
        case XARMOD_STATE_ERROR_ACTIVE:
        case XARMOD_STATE_ERROR_UPGRADED:
            // Set template to 'update'
            $template = 'errorupdate';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbModule['name'])) {
                $data['modname'] = $dbModule['name'];
            } else {
                $data['modname'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbModule['version'])) {
                $data['dbversion'] = $dbModule['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileModule['version'])) {
                $data['fileversion'] = $fileModule['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            break;

        case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
        case XARMOD_STATE_MISSING_FROM_INACTIVE:
        case XARMOD_STATE_MISSING_FROM_ACTIVE:
        case XARMOD_STATE_MISSING_FROM_UPGRADED:
            // Set template to 'missing'
            $template = 'missing';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbModule['name'])) {
                $data['modname'] = $dbModule['name'];
            } else {
                $data['modname'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbModule['version'])) {
                $data['dbversion'] = $dbModule['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileModule['version'])) {
                $data['fileversion'] = $fileModule['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            break;

        case XARMOD_STATE_CORE_ERROR_UNINITIALISED:
        case XARMOD_STATE_CORE_ERROR_INACTIVE:
        case XARMOD_STATE_CORE_ERROR_ACTIVE:
        case XARMOD_STATE_CORE_ERROR_UPGRADED:
            // Set template to 'update'
            $template = 'coreconflict';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbModule['name'])) {
                $data['modname'] = $dbModule['name'];
            } else {
                $data['modname'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbModule['version'])) {
                $data['dbversion'] = $dbModule['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileModule['version'])) {
                $data['fileversion'] = $fileModule['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            $data['core_cur'] = xarConfigGetVar('System.Core.VersionNum');
            $data['core_min'] = isset($fileModule['dependency'][0]['version_ge']) ? $fileModule['dependency'][0]['version_ge'] : '';
            $data['core_max'] = isset($fileModule['dependency'][0]['version_le']) ? $fileModule['dependency'][0]['version_le'] : '';
            $data['core_req'] = isset($fileModule['dependency'][0]['version_eq']) ? $fileModule['dependency'][0]['version_eq'] : '';
            break;

        default:
            break;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('modules','admin','getmenulinks');
    // Return the template variables to BL
    return xarTplModule('modules', 'admin', $template, $data);
}

?>
