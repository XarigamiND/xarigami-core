<?php
/**
 * Remove module and its dependents
 *
 * @package modules
 * @copyright (C) 2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Remove module and its dependents
 * To be used after the user assured he wants to unitialize the module
 * and all its dependents (should show a list of them to the user)
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @return bool true on succesful removal
 * @throws NO_PERMISSION
 */
function modules_adminapi_removewithdependents ($args)
{
    xarLogMessage('Removing with dependents');
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
        xarLogMessage('Missing module since last generation');
        return;
    }

    //Get the dependents list
    $dependents = xarMod::apiFunc('modules','admin','getalldependents',array('regid'=>$mainId));
    xarLogVariable('dependents',$dependents);

    //Deactivate Actives
    foreach ($dependents['active'] as $active_dependent) {
        if (!xarMod::apiFunc('modules', 'admin', 'deactivate', array('regid' => $active_dependent['regid']))) {
            $msg = xarML('Unable to deactivate module "#(1)".', $active_dependent['displayname']);
            throw new BadParameterException('regid',$msg);
        }
    }
    
    //Remove the previously active
    foreach ($dependents['active'] as $active_dependent) {
        if (!xarMod::apiFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
            $msg = xarML('Unable to remove module "#(1)".', $active_dependent['displayname']);
            throw new BadParameterException('regid',$msg);
        }
    }
    
    //Remove the initialised
    foreach ($dependents['initialised'] as $active_dependent) {
        if (!xarMod::apiFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
            $msg = xarML('Unable to remove module "#(1)".', $active_dependent['displayname']);
            throw new BadParameterException('regid',$msg);
        }
    }
    
    return true;
}

?>