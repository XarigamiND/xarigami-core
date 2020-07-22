<?php
/**
 * Clear all user theme vars
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Clear all user theme vars
 *
 * @author Xarigami Core Development Team
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function themes_adminapi_clearuservars()
{
    // Security Check
    if(!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    // Remove variables and theme
    $dbconn = xarDB::$dbconn;
    $tables = xarDBGetTables();
    $uservartable = $tables['module_uservars'];

    // Get theme information
    $mvid = xarModGetVarId('themes','default');

    $sql = "DELETE FROM $uservartable WHERE xar_mvid=?";
    $result = $dbconn->Execute($sql, array($mvid));

    if (!$result) {

        $msg = xarML('There was a problem when trying to delete theme uservars from the database.');
             xarTplSetMessage('error',$msg);
             //exceptions may be turned off
            throw new BadParameterException(null,$msg);

    }

    $result->Close();

    return true;
}
?>