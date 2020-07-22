<?php
/**
 * Find all the module's dependencies
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Find all the module's dependencies with all the dependencies of its
 * siblings
 *
 * @author Xaraya Development Team
 * @param $mainId int ID of the module to look dependents for, from $args['regid']
 * @return array Array with dependency information
 * @throws NO_PERMISSION
 */
function modules_adminapi_getalldependencies($args)
{
    static $checked_ids = array();

    $mainId = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules'))
        return;

    // Argument check
    if (!isset($mainId)) {
        $msg = xarML('Missing module regid (#(1)).', $mainId);
        throw new BadParameterException('mainId',$msg);
    }

    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    //Initialize the dependecies array
    $dependency_array = array();
    $dependency_array['unsatisfiable'] = array();
    $dependency_array['satisfiable']   = array();
    $dependency_array['satisfied']     = array();
    // add some arrays to categorise unsatisfiable dependencies
    $dependency_array['missing'] = array(); // modules not found in filesystem
    $dependency_array['error'] = array(); // modules in an invalid state or otherwise corrupted
    $dependency_array['version'] = array(); // modules which have unfulfilled dependency version requirements
    $dependency_array['php_ext'] = array(); // missing php extensions

    if(in_array($mainId,$checked_ids)) {
        xarLogMessage("Already got the dependencies of $mainId, skipping");
        return $dependency_array; // Done that, been there
    }
    $checked_ids[] = $mainId;

    $mainKey = $mainId;
    // Get module information
    try {
        $modInfo = xarMod::getInfo($mainId);
    } catch (Exception $e) {

    }
    if (!isset($modInfo)) {
        //Add this module to the unsatisfiable list
        $dependency_array['unsatisfiable'][$mainKey] = $mainId;
        // Add the module to list of modules not found in filesystem
        $dependency_array['missing'][$mainId] = $mainId;

        //Return now, we cant find more info about this module
        return $dependency_array;
    }

    if (!empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
                // hash the extension to prevent duplicates
                $extKey = $extension;
                //Add this extension to the unsatisfiable list
                $dependency_array['unsatisfiable'][$extKey] = $extension;
                // Add this extension to list of missing php extensions
                $dependency_array['php_ext'][$extension] = $extension;
            }
        }
    }

    $dependency = $modInfo['dependency'];
    $dependencyinfo = $modInfo['dependencyinfo'];

    if (empty($dependency) && !empty($dependencyinfo)) {
        $dependency = $dependencyinfo;
    }
    if (empty($dependency)) {
        $dependency = array();
    }
    $dependencyinfo = !isset($modInfo['dependencyinfo'])?$dependency:$modInfo['dependencyinfo'];

    // set current core version static, since it won't likely change
    static $core_cur = '';
    if (empty($core_cur)) {
        // get current core version for dependency checks
        $core_cur = xarConfigGetVar('System.Core.VersionNum');
    }
    //The dependencies are ok, they shouldnt change in the middle of the
    //script execution, so let's assume this.
    foreach ($dependency as $module_id => $conditions) {
        if (is_numeric($conditions)) {
            //The module id is in $conditions
            $modId = $conditions;
        } else {
            //The module id is in $module_id
            $modId = $module_id;
        }
        if (!is_array($conditions) && isset($dependencyinfo[$modId])) {
            $conditions = $dependencyinfo[$modId];
        } else {
            $conditions = array();
        }

        //first check Core
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
                //Add this module to the unsatisfiable list
                $dependency_array['unsatisfiable'][$mainKey] = $modInfo;
                // Add the module to list of modules with conflicting versions
                $dependency_array['version'][$mainId]['0'] = $conditions;
                //Return now, since it can't be installed and the correct version may differ
                // @CHECKME: do we want to check dependencies anyway?
                return $dependency_array;
            }
        //now check modules (as per dependency array
        } elseif (is_array($conditions)) {
            // dependency = array('module_id' => array('name' => 'modname', 'version_(eq|le|ge)' => 'version'))
            // we have to check version compatibility here while we know the
            // identity of the module with dependency version requirements
            // Get module information
           $msg = '';
          // Get module information
            try {
                $depInfo = xarMod::getInfo($modId);
            } catch (Exception $e) {

            }
            if (!isset($depInfo)) {
                // no need to add to unsatisifiable here, the
                // recursive call means this happens anyway
            } else {
                // module dependency checks
                $modKey = $modId;
                $mver_cur = $depInfo['version'];
                $mver_req = isset($conditions['version_eq']) ? $conditions['version_eq'] : '';
                if (!empty($mver_req)) {
                    // match exact module version required
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
                        if (!$max_pass)  $msg = xarML("Dependency problem: the module '#(1)' must have a version less than #(2) but is version #(3). ", $conditions['name'], $mver_req, $mver_cur);

                    } else {
                        $max_pass = true;
                    }
                    $mver_pass = $min_pass && $max_pass ? true : false;
                }
                if (!isset($mver_pass) || !$mver_pass) {
                    // Current dependent module version doesn't meet module requirements
                    // Need to add some info for the user
                    //Add this dependency to the unsatisfiable list
                    $dependency_array['unsatisfiable'][$modKey] = $depInfo;
                    // Add the module to list of modules with conflicting version dependencies
                    // along with calling module id and conditions
                    $dependency_array['version'][$modId][$mainId] = $conditions;

                }

            }

            // RECURSIVE CALL
            //if there is an unsatisfiable dependency related to this id do not call output again on it
             $output = xarMod::apiFunc('modules', 'admin', 'getalldependencies', array('regid'=>$modId));
            if (!$output) {
                if (empty($msg)) {
                    $msg = xarML('Unable to get dependencies for module with ID (#(1)).', $modId);
                }
           throw new BadParameterException('output',$msg);
            }
            //This is giving : recursing detected.... ohh well
    //        $dependency_array = array_merge_recursive($dependency_array, $output);

            // FIXME: as the array uses numeric keys, this creates duplicates
            $dependency_array['satisfiable'] = array_merge(
                $dependency_array['satisfiable'],
                $output['satisfiable']);
            $dependency_array['unsatisfiable'] = array_merge(
                $dependency_array['unsatisfiable'],
                $output['unsatisfiable']);
            $dependency_array['satisfied'] = array_merge(
                $dependency_array['satisfied'],
                $output['satisfied']);
            // merge in unsatisfiable dependencies
            $dependency_array['missing'] = array_merge(
                $dependency_array['missing'],
                $output['missing']);
            $dependency_array['error'] = array_merge(
                $dependency_array['error'],
                $output['error']);
            $dependency_array['version'] = array_merge(
                $dependency_array['version'],
                $output['version']);
            $dependency_array['php_ext'] = array_merge(
                $dependency_array['php_ext'],
                $output['php_ext']);
        }
    }

    // Unsatisfiable and Satisfiable are assuming the user can't
    //use some hack or something to set the modules as initialized/active
    //without its proper dependencies
    if (count($dependency_array['unsatisfiable'])) {
        //Then this module is unsatisfiable too
        $dependency_array['unsatisfiable'][$mainKey] = $modInfo;
    } elseif (count($dependency_array['satisfiable'])) {

        //Then this module is satisfiable too
        //As if it were initialized, then all dependencies would have
        //to be already satisfied
        $dependency_array['satisfiable'][$mainKey] = $modInfo;
    } else {
        //Then this module is at least satisfiable
        //Depends if it is already initialized or not

        // Add a new state in the dependency array for version
        // So that we can present that nicely in the gui...

        switch ($modInfo['state']) {
            case XARMOD_STATE_ACTIVE:
            case XARMOD_STATE_UPGRADED:
                //It is satisfied if already initialized
                $dependency_array['satisfied'][$mainKey] = $modInfo;
            break;
            case XARMOD_STATE_INACTIVE:
            case XARMOD_STATE_UNINITIALISED:
                //If not then it is satisfiable
                $dependency_array['satisfiable'][$mainKey] = $modInfo;
            break;
            default:
                //If not then it is unsatisfiable
                $dependency_array['unsatisfiable'][$mainKey] = $modInfo;
                $dependency_array['error'][$mainId] = $mainId;
            break;

        }
    }

    return $dependency_array;
}

?>
