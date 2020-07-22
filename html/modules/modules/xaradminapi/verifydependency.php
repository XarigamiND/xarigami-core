<?php
/**
 * Verifies if all dependencies of a module are satisfied.
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Verifies if all dependencies of a module are satisfied.
 * To be used before initializing a module.
 *
 * @author Xaraya Development Team
 * @param int $mainId ID of the module to look up the dependents for; in $args
 * @return bool true on dependencies verified and ok, false for not
 * @throws NO_PERMISSION
 */
function modules_adminapi_verifydependency($args)
{
    $mainId = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // Argument check
    if (!isset($mainId)) {
        $msg = xarML('Missing module regid (#(1)).', $mainId);
         throw new BadParameterException(null,$msg);
    }

    // Get module information
    $modInfo = xarMod::getInfo($mainId);
    if (!isset($modInfo)) {
        $msg = xarML("Tried to retrieve module information (modInfo) but none was found for module with regid #(1)",$mainId);
        throw new ModuleNotFoundException($mainId,$msg);
    }

    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('modules','admin','checkmissing')) {
        $msg = xarML('A module is missing.');
        throw new ModuleNotFoundException(null,$msg);
    }

    // Get all modules in DB
    // A module is able to fullfil a dependency only if it is activated at least.
    // So db modules should be a safe start to go looking for them
    $dbModules = xarMod::apiFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) {
        $msg = xarML('Tried to retrieve module information (adminapi_getdbmodules) from the database but failed.');
        throw new ModuleNotFoundException(null,$msg);
    }

    $dbMods = array();

    //Find the modules which are active (should upgraded be added too?)
    foreach ($dbModules as $name => $dbInfo) {
        if ($dbInfo['state'] == XARMOD_STATE_ACTIVE ||
            $dbInfo['state'] == XARMOD_STATE_UPGRADED) { // upgrade added, it's satisfiable
            $dbMods[$dbInfo['regid']] = $dbInfo;
        }
    }

    if (!empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
               $msg = xarML("Required PHP extension '#(1)' is missing for module '#(2)'", $extension, $modInfo['displayname']);
               $data = array();
               $data['id'] = $mainId;
                if (isset($modInfo['dependency'])) {
                    $data['dependency'] = $modInfo['dependency'];
                } else {
                    $data['dependency'] = array();
                }
                $data['authid']       = xarSecGenAuthKey();
                $data['dependencies'] = xarMod::apiFunc('modules','admin','getalldependencies',array('regid'=>$mainId));
                $data['displayname'] = $modInfo['displayname'];
                $data['msg'] = $msg;
                return $data;
            }
        }
    }

   $dependency = $modInfo['dependency'];
   if (empty($dependency)) {
        $dependency = array();
    }
    $dependencyinfo = isset($modInfo['dependencyinfo']) && !empty($modInfo['dependencyinfo'])?$modInfo['dependencyinfo']: $dependency;
    // set current core version static, since it won't likely change
    static $core_cur = '';
    if (empty($core_cur)) {
        // get current core version for dependency checks
        $core_cur = xarConfigGetVar('System.Core.VersionNum');
    }

    $msg = '';
    foreach ($dependencyinfo as $module_id => $conditions) {
        if (!empty($conditions) && is_numeric($conditions)) {
            $modId = $conditions;
        } else {
            $modId = $module_id;
        }

        if (!empty($modId) && is_numeric($modId) && $modId != 0) {
            //Required module non-existent
            if (!isset($dbMods[$modId])) {

               $msg = xarML("Required module missing (ID #(1))", $modId);
               $data = array();
               $data['id'] = $mainId;
                if (isset($modInfo['dependency'])) {
                    $data['dependency'] = $modInfo['dependency'];
                } else {
                    $data['dependency'] = array();
                }
                $data['authid']       = xarSecGenAuthKey();
                $data['dependencies'] = xarMod::apiFunc('modules','admin','getalldependencies',array('regid'=>$mainId));
                $data['displayname'] = $modInfo['displayname'];
                $data['msg'] = $msg;
                return $data;
            }
            if (!is_array($conditions) && isset($dependency[$modId]))
                $conditions = $dependency[$modId];
        }
        //check core version first
        if ($modId == 0 && is_array($conditions)) {

             // dependency(info) = array('0' => array('name' => 'Core', 'version_(eq|le|ge)' => 'version'))
            // core dependency checks
            $core_req = isset($conditions['version_eq']) ? $conditions['version_eq'] : '';
            if (!empty($core_req)) {
                // match exact core version required
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
                $core_min = isset($conditions['version_ge']) ? $conditions['version_ge'] : '';
                $core_max = isset($conditions['version_le']) ? $conditions['version_le'] : '';
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
                // Current core version doesn't meet module requirements
                // Need to add some info for the user
                return false;
            }
         //modID isn't zero so must be a module
        } elseif (is_array($conditions)) {
            // dependency(info) = array('module_id' => array('name' => 'modname', 'version_(eq|le|ge)' => 'version'))
            // module dependency checks
            $mver_cur = $dbMods[$modId]['version'];
            $mver_req = isset($conditions['version_eq']) ? $conditions['version_eq'] : '';
            if (!empty($mver_req)) {
                // match exact core version required
                $vercompare = xarMod::apiFunc(
                    'base', 'versions', 'compare',
                    array(
                        'ver1'=>$mver_req,
                        'ver2'=>$mver_cur,
                        'strict' => false
                    )
                );
                $mver_pass = $vercompare == 0 ? true : false;
                 if (!$mver_pass)  $msg = xarML("Dependency problem: the module '#(1)' must have a version of #(2) but is version #(3). ", $conditions['name'], $mver_req, $mver_cur);
            }  else {
                $mver_min = isset($conditions['version_ge']) ? $conditions['version_ge'] : '';
                $mver_max = isset($conditions['version_le']) ? $conditions['version_le'] : '';
                // legacy declarations, deprecated as of 1.2.0
                if (empty($mver_min) && isset($conditions['minversion'])) {
                    $mver_min = $conditions['minversion'];
                }
                if (empty($mver_max) && isset($conditions['maxversion'])) {
                    $mver_max = $conditions['maxversion'];
                }
                if (!empty($mver_min)) {
                    $vercompare = xarMod::apiFunc(
                        'base', 'versions', 'compare',
                        array(
                            'ver1'=>$mver_cur,
                            'ver2'=>$mver_min,
                            'strict' => false
                        )
                    );

                    $min_pass = $vercompare <= 0 ? true : false;
                    if (!$min_pass) $msg = xarML("Dependency problem: the module '#(1)' must have a minimum version #(2) but is only version #(3). ", $conditions['name'], $mver_req, $mver_cur);

                } else {
                    $min_pass = true;
                }
                if (!empty($mver_max)) {
                    $vercompare = xarMod::apiFunc(
                        'base', 'versions', 'compare',
                        array(
                            'ver1'=>$mver_cur,
                            'ver2'=>$mver_max,
                            'strict' => false
                        )
                    );
                    $max_pass = $vercompare >= 0 ? true : false;
                    if (!$max_pass) $msg = xarML("Dependency problem: the module '#(1)' must have a version less than #(2) but is version #(3). ", $conditions['name'], $mver_req, $mver_cur);
                } else {
                    $max_pass = true;
                }

                $mver_pass = $min_pass && $max_pass ? true : false;
            }
            if (!$mver_pass) {
                // Current dependent module version doesn't meet module requirements
                // Need to add some info for the user

                $msg = xarML("Dependency problem: the module '#(1)' is required for continued installation but this version #(3) does not meet module version requirements of #(2)", $conditions['name'], $mver_req, $mver_cur);

            }
        }
    }
    return true;
}

?>
