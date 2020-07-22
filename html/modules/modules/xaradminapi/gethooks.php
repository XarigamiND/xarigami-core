<?php
/**
 * Obtain list of hooks with specific criteria and a particular module
 *
 * @package modules
 * @subpackage Xarigami Core
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 *  Obtain list of hooks with specific criteria and a particular module
 *
 * @param $args['modName'] optional module we're looking for
 * @param $args['hookObject'] the object of the hook (item, module, ...) (optional)
 * @param $args['hookAction'] the action on that object (transform, display, ...) (optional)
 * @param $args['hookArea'] the area we're dealing with (GUI, API) (optional
 * @returns array
 * @return array of known hooks
 * @throws NO_PERMISSION
 */
function modules_adminapi_gethooks($args)
{
// Security Check
    //we need to be able to call this from non core modules by potential users with eg article edit level privs
    //if(!xarSecurityCheck('AdminModules')) return;
    // Get arguments from argument array
    extract($args);
    
    // Argument check
    if (empty($modName)) {
        $modName = '';
    }

    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();


    $bindvars = array();
    $query = "SELECT DISTINCT xar_smodule, xar_stype, xar_tmodule,
                            xar_object, xar_action, xar_tarea, xar_ttype,
                            xar_tfunc
            FROM $xartable[hooks] 
            WHERE xar_smodule = ?";
    $bindvars[] = $modName;
    if (!empty($hookObject)) {
        $query .= " AND xar_object = ?";
        $bindvars[] = $hookObject;
    }
    if (!empty($hookAction)) {
        $query .= " AND xar_action = ?";
        $bindvars[] = $hookAction;
    }
    if (!empty($hookArea)) {
        $query .= " AND xar_tarea = ?";
        $bindvars[] = $hookArea;
    }

    $result = $dbconn->Execute($query,$bindvars);
    if(!$result) return;

    // hooklist will hold the available hooks
    $hooklist = array();
    for (; !$result->EOF; $result->MoveNext()) {
        list($smodName, $itemType, $tmodName,$object,$action,$area,$tmodType,$tmodFunc) = $result->fields;

        // Avoid single-space module names e.g. for mssql
        if (!empty($smodName)) {
            $smodName = trim($smodName);
        }
        if (!empty($itemType)) {
            $itemType = trim($itemType);
        }

        // Let's check to make sure this isn't a stale hook
        // if it is, unregister it and continue onto the next iteration in the for loop
        if (is_null(xarMod::getId($tmodName))) {
            xarMod::unregisterHook($object, $action, $area, $tmodName, $tmodType, $tmodFunc);
            continue;
        }

          if (!empty($smodName)) {
            if (empty($itemType)) $itemType = 0;
            $hooklist[$tmodName][$itemType] = 1;
        }
    }
    $result->Close();

    return $hooklist;
}

?>