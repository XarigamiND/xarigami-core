<?php
/**
 * Remove a module
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Remove a module
 * @param $args['regid'] the id of the module
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_remove($args)
{
    // Get arguments from argument array
    extract($args);

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Remove variables and module
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    // Get module information
    $modinfo = xarMod::getInfo($regid);

    //TODO: Add check if there is any dependents
/*
    if (!xarMod::apiFunc('modules','admin','verifydependents',array('regid'=>$regid))) {
        //TODO: Add description of the dependencies
        $msg = xarML('There are dependents to the module "#(1)" that weren\'t removed yet.', $modInfo['displayname']);
        throw new Exception($msg);
    }
*/
    // If the files have been removed, the module will now also be removed from the db
    if ($modinfo['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED ) {

        // Delete any module variables that the module cleanup function might
        // have missed.
        // This needs to be done before the module entry is removed.
        xarModDelAllVars($modinfo['name']);

        $query = "DELETE FROM " . $tables['modules'] . " WHERE xar_regid = ?";
        $result = $dbconn->Execute($query,array($modinfo['regid']));
        if (!$result) return;
    }
    else {
        // Module deletion function
        if (!xarMod::apiFunc('modules', 'admin', 'executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'delete'))) {
            //Raise an Exception
            return;
        }

        // Delete any module variables that the module cleanup function might
        // have missed.
        // This needs to be done before the module ntry is removed.
        // <mikespub> But *after* the delete() function of the module !
        xarModDelAllVars($modinfo['name']);

        // Update state of module
        $res = xarMod::apiFunc('modules', 'admin', 'setstate',
                             array('regid' => $regid,
                                  'state' => XARMOD_STATE_UNINITIALISED));
    }

    // Delete any masks still around
    xarRemoveMasks($modinfo['name']);
    // Call any 'category' delete hooks assigned for that module
    // (notice we're using the module name as object id, and adding an
    // extra parameter telling xarModCallHooks for *which* module we're
    // calling hooks here)
    xarMod::callHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

    // Delete any hooks assigned for that module, or by that module
    $query = "DELETE FROM $tables[hooks] WHERE xar_smodule = ? OR xar_tmodule = ?";
    $bindvars = array($modinfo['name'],$modinfo['name']);
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) {return;}

    //
    // Delete block details for this module.
    //

    // Get block types.
    $blocktypes = xarMod::apiFunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => $modinfo['name'])
    );

    // Delete block types.
    if (is_array($blocktypes) && !empty($blocktypes)) {
        foreach($blocktypes as $blocktype) {
            $result = xarMod::apiFunc(
                'blocks', 'admin', 'delete_type', $blocktype
            );
        }
    }

    // Check whether the module was the default module
    $defaultmod = xarConfigGetVar('Site.Core.DefaultModuleName');
    if ($modinfo['name'] == $defaultmod){
        xarConfigSetVar('Site.Core.DefaultModuleName', 'base');
    }
     xarLogMessage('MODULES: Module with Registered ID '.$regid.' was removed by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}
?>