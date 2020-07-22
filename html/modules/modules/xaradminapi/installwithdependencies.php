<?php
/**
 * Install a module with all its dependencies.
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Install a module with all its dependencies.
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @throws NO_PERMISSION
 */
function modules_adminapi_installwithdependencies ($args)
{
    //    static $installed_ids = array();
    $mainId = $args['regid'];

   // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules'))
        return;

    // Argument check
    if (!isset($mainId)) {
        $msg = xarML('Missing module regid (#(1)).', $mainId);
         throw new BadParameterException(null,$msg);
    }

    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    // Make xarModGetInfo not cache anything...
    //We should make a funcion to handle this or maybe whenever we
    //have a central caching solution...
    $GLOBALS['xarMod_noCacheState'] = true;

    // Get module information
    $modInfo = xarMod::getInfo($mainId);

    if (!isset($modInfo)) {
       $msg = xarML("Tried to retrieve module information (modInfo) but none was found for module with regid #(1)",$mainId);
        throw new ModuleNotFoundException($mainId,$msg);
    }

    switch ($modInfo['state']) {
        case XARMOD_STATE_ACTIVE:
        case XARMOD_STATE_UPGRADED:
            //It is already installed
            return true;
        case XARMOD_STATE_INACTIVE:
            $initialised = true;
            break;
        default:
            $initialised = false;
            break;
    }
    if (isset($modInfo['extensions']) && !empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
                //jojo - todo - handle appropriately
                 $data = array();
                 $data['id'] = $mainId;
                    if (isset($modInfo['dependency'])) {
                        $data['dependency'] = $modInfo['extensions'];
                    } else {
                        $data['dependency'] = array();
                    }
                    $data['authid']       = xarSecGenAuthKey();
                    $data['dependencies'] = xarMod::apiFunc('modules','admin','getalldependencies',array('regid'=>$mainId));
                    $data['displayname'] = $modInfo['displayname'];
                    //jojo - is this used - does it ever get to here?
                    $data['msg'] = xarML("Required PHP extension '#(1)' is missing for module '#(2)'", $extension, $modInfo['displayname']);
                    $data['errorstack'] = $e->getTraceAsString();
                    return $data;
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

    //The dependencies are ok, assuming they shouldnt change in the middle of the
    //script execution.
    foreach ($dependency as $module_id => $conditions) {
        if (!empty($conditions) && is_numeric($conditions)) {
            $modId = $conditions;
        } else {
            $modId = $module_id;
        }
        if (empty($modId) ) continue;
        $testinstall = xarMod::apiFunc('modules', 'admin', 'installwithdependencies', array('regid'=>$modId));

        if (!$testinstall || is_array($testinstall)) {
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
            $data['msg'] = xarML('Unable to initialize dependency module with ID (#(1)).', $modId);
            $data['errorstack'] = $e->getTraceAsString();
            return $data;
        }
        /*
        $testinstall = xarMod::apiFunc('modules', 'admin', 'installwithdependencies', array('regid'=>$modId));

        if (!$testinstall || is_array($testinstall)) {
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {

                return;
            } else {
                $msg = xarML('Unable to initialize dependency module with ID (#(1)).', $modId);
                throw new Exception(null,$msg);
            }
        }*/
    }

    //Checks if the module is already initialised
    if (!$initialised) {
        // Finally, now that dependencies are dealt with, initialize the module
        if (!xarMod::apiFunc('modules', 'admin', 'initialise', array('regid' => $mainId))) {
                $msg = xarML('Unable to initialize module "#(1)".', $modInfo['displayname']);
                throw new Exception($msg);
        }

    }

    // And activate it!
    if (!xarMod::apiFunc('modules', 'admin', 'activate', array('regid' => $mainId))) {
            $msg = xarML('Unable to activate module "#(1)".', $modInfo['displayname']);
             throw new Exception($msg);
    }

    return true;
}

?>
