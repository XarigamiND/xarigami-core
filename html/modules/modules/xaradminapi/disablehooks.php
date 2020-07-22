<?php
/**
 * Disable hooks between a caller module and a hook module
 *
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Disable hooks between a caller module and a hook module
 * Note : generic hooks will not be disabled if a specific item type is given
 *
 * @param $args['callerModName'] caller module
 * @param $args['callerItemType'] optional item type for the caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @throws BAD_PARAM
 */
function modules_adminapi_disablehooks($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName)) throw new EmptyParameterException('callerModName');
    if (empty($hookModName))  throw new EmptyParameterException('hookModName');

    if (empty($callerItemType)) {
        $callerItemType = '';
    }

    // Rename operation
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    // Delete hooks regardless
    $sql = "DELETE FROM $xartable[hooks]
            WHERE xar_smodule = ?
              AND xar_stype = ?
              AND xar_tmodule = ?";
    $bindvars = array($callerModName,$callerItemType,$hookModName);

    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    return true;
}

?>