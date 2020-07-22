<?php
/**
 * Update the module version in the database
 *
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2009 2skies.com
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
function themes_adminapi_updateversion($args)
{
    //this function is also used by installer
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regId)) {
        $msg = xarML('Empty regId (#(1)).', $regId);
         throw new EmptyParameterException(null,$msg);
    }

    // Security Check
    if(!xarSecurityCheck('AdminTheme',0,'All',"All:All:$regId")) return xarResponseForbidden();

    //  Get database connection and tables
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $themes_table = $xartable['themes'];

    // Get module information from the filesystem
    $fileTheme= xarMod::apiFunc('themes','admin','getfilethemes',
                                array('regId' => $regId));
    if (!isset($fileTheme)) return;

    // Update database version
    $sql = "UPDATE $themes_table SET xar_version = ? WHERE xar_regid = ?";
    $bindvars = array($fileTheme['version'],$fileTheme['regid']);

    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    return true;
}

?>