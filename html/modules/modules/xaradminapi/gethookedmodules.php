<?php
/**
 * Get list of modules calling a particular hook module
 *
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Core
 * @copyright (C) 2006-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Get list of modules calling a particular hook module
 *
 * @author Xaraya Development Team
 * @param $args['hookModName'] hook module we're looking for
 * @param $args['hookObject'] the object of the hook (item, module, ...) (optional)
 * @param $args['hookAction'] the action on that object (transform, display, ...) (optional)
 * @param $args['hookArea'] the area we're dealing with (GUI, API) (optional)
 * @returns array
 * @return array of modules calling this hook module
 * @throws BAD_PARAM
 */
function modules_adminapi_gethookedmodules($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($hookModName)) {
        throw new EmptyParameterException('hookModName');
    }

    $dbconn     = xarDB::$dbconn;
    $xartable   = xarDBGetTables();

    $bindvars = array();
    $query = "SELECT DISTINCT xar_smodule, xar_stype
              FROM $xartable[hooks] 
              WHERE xar_tmodule= ?";
    $bindvars[] = $hookModName;
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

    // modlist will hold the hooked modules
    $modlist = array();
    for (; !$result->EOF; $result->MoveNext()) {
        list($callerModName,$callerItemType) = $result->fields;
        if (empty($callerModName)) continue;
        if (empty($callerItemType)) {
            $callerItemType = 0;
        }
        $modlist[$callerModName][$callerItemType] = 1;
    }
    $result->Close();

    return $modlist;
}

?>