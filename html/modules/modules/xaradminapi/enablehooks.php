<?php
/**
 * Enable hooks between a caller module and a hook module
 *
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Enable hooks between a caller module and a hook module
 * Note : hooks will be enabled for all item types if no specific item type is given
 *
 * @param $args['callerModName'] caller module
 * @param $args['callerItemType'] optional item type for the caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @throws BAD_PARAM
 */
function modules_adminapi_enablehooks($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName) || empty($hookModName)) {
        $msg = xarML('callerModName or hookModName');
         throw new EmptyParameterException($msg);
    }
    if (empty($callerItemType)) {
        $callerItemType = '';
    }

    // Rename operation
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    // Delete hooks regardless
    $sql = "DELETE FROM $xartable[hooks]
            WHERE xar_smodule = ? AND xar_stype = ? AND xar_tmodule = ?";
    $bindvars = array($callerModName,$callerItemType,$hookModName);

    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    $sql = "SELECT DISTINCT xar_id, xar_smodule, xar_stype, xar_object,
                            xar_action, xar_tarea, xar_tmodule, xar_ttype,
                            xar_tfunc
            FROM $xartable[hooks]
            WHERE xar_smodule = '' AND xar_tmodule = ?";

    $result = $dbconn->Execute($sql,array($hookModName));
    if (!$result) return;

    for (; !$result->EOF; $result->MoveNext()) {
        list($hookid,
             $hooksmodname,
             $hookstype,
             $hookobject,
             $hookaction,
             $hooktarea,
             $hooktmodule,
             $hookttype,
             $hooktfunc) = $result->fields;

        $sql = "INSERT INTO $xartable[hooks] (
                      xar_id, xar_object, xar_action, xar_smodule, xar_stype,
                      xar_tarea, xar_tmodule, xar_ttype, xar_tfunc)
                    VALUES (?,?,?,?,?,?,?,?,?)";
        $bindvars = array($dbconn->GenId($xartable['hooks']),
                          $hookobject, $hookaction, $callerModName,
                          $callerItemType, $hooktarea, $hooktmodule,
                          $hookttype, $hooktfunc);
        $subresult = $dbconn->Execute($sql,$bindvars);
        if (!$subresult) return;
    }
    $result->Close();

    if (!sys::isInstall()) {
      xarLogMessage('MODULES: Hooks for module '.$hookModName.' were enabled for '.$callerModName.' for itemtype '.$callerItemType.' by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    }
    return true;
}

?>