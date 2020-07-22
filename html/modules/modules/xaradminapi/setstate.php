<?php
/**
 * Set the state of a module
 *
 * @package modules
 * @copyright (C) 2005-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Set the state of a module
 * @param $args['regid'] the module id
 * @param $args['state'] the state
 * @return bool true
 * @throws BAD_PARAM,NO_PERMISSION
 */
function modules_adminapi_setstate($args)
{
    // Get arguments from argument array

    extract($args);

    // Argument check
    if ((!isset($regid)) ||
        (!isset($state))) {
        $msg = xarML('Empty regid (#(1)) or state (#(2)).', $regid, $state);
        throw new BadParameterException('regid',$msg);
    }

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Clear cache to make sure we get newest values
    if (xarCoreCache::isCached('Mod.Infos', $regid)) {
        xarCoreCache::delCached('Mod.Infos', $regid);
    }

    //Get module info
    $modInfo = xarMod::getInfo($regid);

    //Set up database object
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $sitePrefix = xarDB::$prefix;
    $oldState = $modInfo['state'];

    // Check valid state transition
    switch ($state) {
        case XARMOD_STATE_UNINITIALISED:

            if (($oldState == XARMOD_STATE_MISSING_FROM_UNINITIALISED) ||
                ($oldState == XARMOD_STATE_ERROR_UNINITIALISED) ||
                ($oldState == XARMOD_STATE_CORE_ERROR_UNINITIALISED)
            ) break;

            if ($oldState != XARMOD_STATE_INACTIVE) {
                // New Module - we don't need to do anything
             /*   $module_statesTable = $sitePrefix.'_module_states';
                $query = "SELECT * FROM $module_statesTable WHERE xar_regid = ?";
                $result = $dbconn->Execute($query,array($regid));
                if (!$result) return;
                if ($result->EOF) {

                    // Bug #1813 - Have to use GenId to get or create the sequence
                    // for xar_id or the sequence for xar_id will not be available
                    // in PostgreSQL
                    $seqId = $dbconn->GenId($module_statesTable);

                    $query = "INSERT INTO $module_statesTable
                                (xar_id, xar_regid, xar_state)
                        VALUES  (?,?,?)";
                    $bindvars = array($seqId,$regid,$state);

                    $result = $dbconn->Execute($query,$bindvars);
                    if (!$result) return;
                }
                */
                return true;
            }

            break;
        case XARMOD_STATE_INACTIVE:
            if (($oldState != XARMOD_STATE_UNINITIALISED) &&
                ($oldState != XARMOD_STATE_ACTIVE) &&
                ($oldState != XARMOD_STATE_MISSING_FROM_INACTIVE) &&
                ($oldState != XARMOD_STATE_ERROR_INACTIVE) &&
                ($oldState != XARMOD_STATE_CORE_ERROR_INACTIVE) &&
                ($oldState != XARMOD_STATE_UPGRADED)) {
                xarSession::setVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
        case XARMOD_STATE_ACTIVE:
            if (($oldState != XARMOD_STATE_INACTIVE) &&
                ($oldState != XARMOD_STATE_ERROR_ACTIVE) &&
                ($oldState != XARMOD_STATE_CORE_ERROR_ACTIVE) &&
                ($oldState != XARMOD_STATE_MISSING_FROM_ACTIVE)) {
                xarSession::setVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
        case XARMOD_STATE_UPGRADED:
            if (($oldState != XARMOD_STATE_INACTIVE) &&
                ($oldState != XARMOD_STATE_ACTIVE) &&
                ($oldState != XARMOD_STATE_ERROR_UPGRADED) &&
                ($oldState != XARMOD_STATE_CORE_ERROR_UPGRADED) &&
                ($oldState != XARMOD_STATE_MISSING_FROM_UPGRADED)) {
                xarSession::setVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
    }
    //Get current module mode to update the proper table
    //$modMode  = $modInfo['mode'];

    $modulesTable = $xartable['modules'];

    $query = "UPDATE $modulesTable
              SET xar_state = ? WHERE xar_regid = ?";
    $bindvars = array($state,$regid);
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) {return;}
    $modInfo['state']=$state;
    xarCoreCache::setCached('Mod.Infos',$regid,$modInfo);
    //xarCoreCache::setCached('Mod.BaseInfos',$modInfo['name'],$modInfo);
    return true;
}

?>
