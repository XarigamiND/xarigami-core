<?php
/**
 * Obtain list of hooks (optionally for a particular module)
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Core
 * @copyright (C) 2006-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Obtain list of hooks (optionally for a particular module)
 *
 * @author Xaraya Development Team
 * @param $args['modName'] optional module we're looking for
 * @returns array
 * @return array of known hooks
 * @throws NO_PERMISSION
 */
function modules_adminapi_gethooklist($args)
{
// Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($modName)) {
        $modName = '';
    }

    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();

    // TODO: allow finer selection of hooks based on type etc., and
    //       filter out irrelevant ones (like module remove, search...)
    $bindvars = array();
    $query = "SELECT DISTINCT xar_smodule, xar_stype, xar_tmodule,
                            xar_object, xar_action, xar_tarea, xar_ttype,
                            xar_tfunc
            FROM $xartable[hooks] ";

    if (!empty($modName)) {
        $query .= " WHERE xar_smodule=''
                       OR xar_smodule = ?
                 ORDER BY xar_tmodule,
                          xar_smodule DESC";
        $bindvars[] = $modName;
    } else {
        $query .= " ORDER BY xar_tmodule";
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
        // Avoid single-space item types e.g. for mssql
        if (!empty($itemType)) {
            $itemType = trim($itemType);
        }

        // Let's check to make sure this isn't a stale hook
        // if it is, unregister it and continue onto the next iteration in the for loop
        if (is_null(xarMod::getId($tmodName))) {
            xarMod::unregisterHook($object, $action, $area, $tmodName, $tmodType, $tmodFunc);
            continue;
        }

        if (!isset($hooklist[$tmodName])) $hooklist[$tmodName] = array();
        if (!isset($hooklist[$tmodName]["$object:$action:$area"])) $hooklist[$tmodName]["$object:$action:$area"] = array();
        // if the smodName has a value the hook is active
        if (!empty($smodName)) {
            if (!isset($hooklist[$tmodName]["$object:$action:$area"][$smodName])) $hooklist[$tmodName]["$object:$action:$area"][$smodName] = array();
            if (empty($itemType)) $itemType = 0;
            $hooklist[$tmodName]["$object:$action:$area"][$smodName][$itemType] = 1;
        }
    }
    $result->Close();

    return $hooklist;
}

?>