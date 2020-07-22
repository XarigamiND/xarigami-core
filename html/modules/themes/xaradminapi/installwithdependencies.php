<?php
/**
 * Install a module with all its dependencies.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Install a module with all its dependencies.
 *
 * @author Marty Vance
 * @param $maindId int ID of the module to look dependents for
 * @return bool true on dependencies activated, false for not
 * @throws NO_PERMISSION
 */
function modules_adminapi_installwithdependencies ($args)
{
    //    static $installed_ids = array();
    $mainId = $args['regid'];


    // FIXME: check if this is necessary, it shouldn't, we should have checked it earlier
    //     if(in_array($mainId, $installed_ids)) {
    //         xarLogMessage("Already installed $mainId in this request, skipping");
    //         return true;
    //     }
    //     $installed_ids[] = $mainId;

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminModules', 0, 'All', 'All', 'modules'))
        return xarResponseForbidden();

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
        throw new EmptyParameterException('modInfo');
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


    $dependency = $modInfo['dependency'];

    if (empty($dependency)) {
        $dependency = array();
    }

    //The dependencies are ok, assuming they shouldnt change in the middle of the
    //script execution.
    foreach ($dependency as $module_id => $conditions) {
        if (is_array($conditions)) {
            //The module id is in $modId
            $modId = $module_id;
        } else {
            //The module id is in $conditions
            $modId = $conditions;
        }

        if (!xarMod::apiFunc('modules', 'admin', 'installwithdependencies', array('regid'=>$modId))) {
            $msg = xarML('Unable to initialize dependency module with ID (#(1)).', $modId);
            throw new BadParameterException(null,$msg);
        }
    }

    //Checks if the module is already initialised
    if (!$initialised) {
        // Finally, now that dependencies are dealt with, initialize the module
        if (!xarMod::apiFunc('modules', 'admin', 'initialise', array('regid' => $mainId))) {
            $msg = xarML('Unable to initialize module "#(1)".', $modInfo['displayname']);
            throw new BadParameterException(null,$msg);
        }
    }

    // And activate it!
    if (!xarMod::apiFunc('modules', 'admin', 'activate', array('regid' => $mainId))) {
        $msg = xarML('Unable to activate module "#(1)".', $modInfo['displayname']);
        throw new BadParameterException(null,$msg);
    }

    return true;
}

?>