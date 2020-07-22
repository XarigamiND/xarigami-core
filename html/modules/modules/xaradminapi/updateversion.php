<?php
/**
 * Update the module version in the database
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Update the module version in the database
 *
 * @author Xaraya Development Team
 * @param $args['regId'] the id number of the module to update
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_updateversion($args)
{
    //this function is also used by installer
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regId)) {
        $msg = xarML('Empty regId (#(1)).', $regId);
         throw new BadParameterException(null,$msg);
    }

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regId")) return;

    //  Get database connection and tables
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $modules_table = $xartable['modules'];

    // Get module information from the filesystem
    $fileModule = xarMod::apiFunc('modules', 'admin', 'getfilemodules',
                                array('regId' => $regId));
    if (!isset($fileModule)) return;

    // Update database version
    $sql = "UPDATE $modules_table SET xar_version = ? WHERE xar_regid = ?";
    $bindvars = array($fileModule['version'],$fileModule['regid']);

    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    return true;
}

?>