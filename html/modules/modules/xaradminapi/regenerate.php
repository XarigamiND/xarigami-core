<?php
/**
 * Regenerate module list
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Regenerate module list
 *
 * @author Xaraya Development Team
 * @param none
 * @returns bool
 * @return true on success, false on failure
 * @throws NO_PERMISSION
 */
function modules_adminapi_regenerate()
{
    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules')) {return;}

    //Finds and updates missing modules
    if (!xarMod::apiFunc('modules', 'admin', 'checkmissing')) {return;}

    //Finds and adds new modules to the db
    if (!xarMod::apiFunc('modules', 'admin', 'checknew')) {return;}

    //Get all modules in the filesystem
    $fileModules = xarMod::apiFunc('modules', 'admin', 'getfilemodules');
    if (!isset($fileModules)) {return;}

    // Get all modules in DB
    $dbModules = xarMod::apiFunc('modules', 'admin', 'getdbmodules');
    if (!isset($dbModules)) {return;}

    //Setup database object for module insertion
    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();
    $modules_table = $xartable['modules'];
    // get current core version for dependency checks
    $core_cur = xarConfigGetVar('System.Core.VersionNum');

    // see if any modules have changed since last generation
    foreach ($fileModules as $name => $modinfo) {
    // check core dependency
        $core_min = isset($modinfo['dependencyinfo'][0]['version_ge']) ? $modinfo['dependencyinfo'][0]['version_ge'] : '';
        $core_max = isset($modinfo['dependencyinfo'][0]['version_le']) ? $modinfo['dependencyinfo'][0]['version_le'] : '';
        $core_req = isset($modinfo['dependencyinfo'][0]['version_eq']) ? $modinfo['dependencyinfo'][0]['version_eq'] : '';
        // module specified an exact core version requirement
        if (!empty($core_req)) {
            $vercompare = xarMod::apiFunc(
                'base', 'versions', 'compare',
                array(
                    'ver1'=>$core_req,
                    'ver2'=>$core_cur,
                    'strict' => false
                )
            );
            $core_pass = $vercompare == 0 ? true : false;
        } else {
            if (!empty($core_min)) {
                $vercompare = xarMod::apiFunc(
                    'base', 'versions', 'compare',
                    array(
                        'ver1'=>$core_cur,
                        'ver2'=>$core_min,
                        'strict' => false
                    )
                );
                $min_pass = $vercompare <= 0 ? true : false;
            } else {
                $min_pass = true;
            }
            if (!empty($core_max)) {
                $vercompare = xarMod::apiFunc(
                    'base', 'versions', 'compare',
                    array(
                        'ver1'=>$core_cur,
                        'ver2'=>$core_max,
                        'strict' => false
                    )
                );
                $max_pass = $vercompare >= 0 ? true : false;
            } else {
                $max_pass = true;
            }
            $core_pass = $min_pass && $max_pass ? true : false;
        }
        if (!$core_pass) {
            // module is incompatible with current core version
            // We can't deactivate or remove the module as the user will
            // lose all of their data, so the module should be placed into
            // a holding state until the user has updated the files for
            // the module to a compatible version

            // Check if error state is already set
            if (($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_UNINITIALISED) ||
                ($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_INACTIVE) ||
                ($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_ACTIVE) ||
                ($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_UPGRADED)) {
                // Continue to next module
                continue;
            }
            // Set error state
            $modstate = XARMOD_STATE_ANY;
            switch ($dbModules[$name]['state']) {
                case XARMOD_STATE_UNINITIALISED:
                case XARMOD_STATE_ERROR_UNINITIALISED:
                    $modstate = XARMOD_STATE_CORE_ERROR_UNINITIALISED;
                    break;
                case XARMOD_STATE_INACTIVE:
                case XARMOD_STATE_ERROR_INACTIVE:
                    $modstate = XARMOD_STATE_CORE_ERROR_INACTIVE;
                    break;
                case XARMOD_STATE_ACTIVE:
                case XARMOD_STATE_ERROR_ACTIVE:
                    $modstate = XARMOD_STATE_CORE_ERROR_ACTIVE;
                    break;
                case XARMOD_STATE_UPGRADED:
                case XARMOD_STATE_ERROR_UPGRADED:
                    $modstate = XARMOD_STATE_CORE_ERROR_UPGRADED;
                    break;
            }
            if ($modstate != XARMOD_STATE_ANY) {
                if (!xarMod::apiFunc(
                    'modules', 'admin', 'setstate',
                    array(
                        'regid' => $dbModules[$name]['regid'],
                        'state' => $modstate
                    )
                )) return;
            }
            // Continue to next module
            continue;
        } // End core dep checks

        // Check if the version strings are different.
        if ($dbModules[$name]['version'] != $modinfo['version']) {
            $vercompare = xarMod::apiFunc(
                'base', 'versions', 'compare',
                array(
                    'ver1'=>$dbModules[$name]['version'],
                    'ver2'=>$modinfo['version'],
                    'strict' => false
                )
            );
            // Check that the new version is equal to, or greater than the db version
            if ($vercompare >= 0) {
                // Check if we're dealing with a core module
                $is_core = (substr($dbModules[$name]['class'], 0, 4) == 'Core') ? true : false;
                // found equivalent or newer version
                // Handle core module upgrades
                if ($is_core) {
                    // Bug 2879: Attempt to run the core module upgrade and activate functions.
                    xarMod::apiFunc(
                        'modules', 'admin', 'upgrade',
                        array(
                            'regid' => $modinfo['regid'],
                            'state' => XARMOD_STATE_INACTIVE
                        )
                    );

                    xarMod::apiFunc('modules', 'admin', 'activate',
                        array(
                            'regid' => $modinfo['regid'],
                            'state' => XARMOD_STATE_ACTIVE
                        )
                    );
                }
                // Automatically update the module version for uninstalled modules or
                // where the version number is equivalent (but could be a different format)
                // or if the module is a core module.
                if ($dbModules[$name]['state'] == XARMOD_STATE_UNINITIALISED ||
                    $dbModules[$name]['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
                    $dbModules[$name]['state'] == XARMOD_STATE_ERROR_UNINITIALISED ||
                    $vercompare == 0 || $is_core)
                {
                    // First we check if this module belongs to class Core or not - file system
                    if(substr($modinfo['class'], 0, 4)  == 'Core')
                    {
                        // Get module ID
                        $regId = $modinfo['regid'];

                        $newstate = XARMOD_STATE_INACTIVE;
                        xarMod::apiFunc('modules','admin','upgrade',
                                        array(    'regid'    => $regId,
                                                'state'    => $newstate));

                        $newstate = XARMOD_STATE_ACTIVE;
                        xarMod::apiFunc('modules','admin','activate',
                                        array(  'regid'    => $regId,
                                                'state'    => $newstate));
                    }

                    // Update the module version number
                    $sql = "UPDATE $modules_table SET xar_version = ? WHERE xar_regid = ?";
                    $result = $dbconn->Execute($sql, array($modinfo['version'], $modinfo['regid']));
                    if (!$result) {return;}
                } else {
                    //check if we need to autoupgrade any other already installed modules - they need to be in active state already
                    $auto3ptupgrade = xarModGetVar('modules','auto3ptupgrade');
                    $ver2ptcompare = -1;
                    if ($auto3ptupgrade) {
                        $ver2ptcompare = xarMod::apiFunc('base', 'versions', 'compare',
                            array(
                                'ver1'=>$dbModules[$name]['version'],
                                'ver2'=>$modinfo['version'],
                                'levels' => 2
                            )
                        );
                    }
                    //if the version is the same at level 2 (but was different at level 3) then we upgrade
                    // AND activate as long as the module was already in an active state
                    if (($ver2ptcompare == 0) && ($dbModules[$name]['state']==XARMOD_STATE_ACTIVE)) {
                        // Get module ID
                        $regId = $modinfo['regid'];

                        $newstate = XARMOD_STATE_INACTIVE;
                        $set1 = xarMod::apiFunc('modules','admin','upgrade',
                                        array(    'regid'    => $regId,
                                                'state'    => $newstate));

                        if (!isset($set1)) {return;}//jojo: TODO - appropriate handling for user info

                        $newstate = XARMOD_STATE_ACTIVE;
                        $set2 = xarMod::apiFunc('modules','admin','activate',
                                        array(  'regid'    => $regId,
                                                'state'    => $newstate));
                        if (!isset($set2)) {return;}   //jojo: TODO - appropriate handling for user info
                    } else {
                    // Else set the module state to upgraded
                        $set = xarMod::apiFunc(
                            'modules', 'admin', 'setstate',
                            array(
                                'regid' => $modinfo['regid'],
                                'state' => XARMOD_STATE_UPGRADED
                            )
                        );

                        if (!isset($set)) {return;}
                    }
                }
            } else {
                // found regressed version
                // The database version is greater than the file version.
                // We can't deactivate or remove the module as the user will
                // lose all of their data, so the module should be placed into
                // a holding state until the user has updated the files for
                // the module and the module version is the same or greater
                // than the db version.

                // Check if error state is already set
                if (($dbModules[$name]['state'] == XARMOD_STATE_ERROR_UNINITIALISED) ||
                    ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_INACTIVE) ||
                    ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_ACTIVE) ||
                    ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_UPGRADED)) {
                    // Continue to next module
                    continue;
                }

                // Set error state
                $modstate = XARMOD_STATE_ANY;
                switch ($dbModules[$name]['state']) {
                    case XARMOD_STATE_UNINITIALISED:
                        $modstate = XARMOD_STATE_ERROR_UNINITIALISED;
                        break;
                    case XARMOD_STATE_INACTIVE:
                        $modstate = XARMOD_STATE_ERROR_INACTIVE;
                        break;
                    case XARMOD_STATE_ACTIVE:
                        $modstate = XARMOD_STATE_ERROR_ACTIVE;
                        break;
                    case XARMOD_STATE_UPGRADED:
                        $modstate = XARMOD_STATE_ERROR_UPGRADED;
                        break;
                }
                if ($modstate != XARMOD_STATE_ANY) {
                    $set = xarMod::apiFunc(
                            'modules', 'admin', 'setstate',
                            array(
                                'regid' => $dbModules[$name]['regid'],
                                'state' => $modstate
                            )
                        );
                    if (!isset($set)) {return;}
                    // Continue to next module
                    continue;
                }
            }
        } // End version checks

        // From here on we have a module in the file system and the db
        $newstate = XARMOD_STATE_ANY;
        switch ($dbModules[$name]['state']) {
            case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
            case XARMOD_STATE_ERROR_UNINITIALISED:
            case XARMOD_STATE_CORE_ERROR_UNINITIALISED:
                $newstate = XARMOD_STATE_UNINITIALISED;
                break;
            case XARMOD_STATE_MISSING_FROM_INACTIVE:
            case XARMOD_STATE_ERROR_INACTIVE:
            case XARMOD_STATE_CORE_ERROR_INACTIVE:
                $newstate = XARMOD_STATE_INACTIVE;
                break;
            case XARMOD_STATE_MISSING_FROM_ACTIVE:
            case XARMOD_STATE_ERROR_ACTIVE:
            case XARMOD_STATE_CORE_ERROR_ACTIVE:
                $newstate = XARMOD_STATE_ACTIVE;
                break;
            case XARMOD_STATE_MISSING_FROM_UPGRADED:
            case XARMOD_STATE_ERROR_UPGRADED:
            case XARMOD_STATE_CORE_ERROR_UPGRADED:
                $newstate = XARMOD_STATE_UPGRADED;
                break;
        }
        if ($newstate != XARMOD_STATE_ANY) {
            $set = xarMod::apiFunc(
                'modules', 'admin', 'setstate',
                array(
                    'regid' => $dbModules[$name]['regid'],
                    'state' => $newstate
                )
            );
        }

        // BUG 2580 - check for changes in version info and update db accordingly
        $updatearray = array('class','category','admin_capable','user_capable');
        $updaterequired = false;
        foreach ($updatearray as $fieldname) {
            if ($dbModules[$name][$fieldname] != $modinfo[$fieldname]) {
                $updaterequired = true;
            }
        }
        if ($updaterequired) {
            //update all these fields to the database
            $updatemodule = xarMod::apiFunc('modules','admin','updateproperties',
                      array('regid' => $dbModules[$name]['regid'],
                            'class' => $modinfo['class'],
                            'category' => $modinfo['category'],
                            'admincapable' => $modinfo['admin_capable'],
                            'usercapable' => $modinfo['user_capable']
                        )
                );
        }
    }
    xarLogMessage('REGENERATE: finished regeneration');
    // Finds and updates event handlers
    if (!xarMod::apiFunc('modules', 'admin', 'geteventhandlers')) {return;}
    return true;
}
?>
