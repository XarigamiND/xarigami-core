<?php
/**
 * Get registered template tags
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Get registered template tags
 * @param array args
 * @return array of tags in the database
 */
function themes_adminapi_gettpltaglist($args)
{
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    extract($args);
    $aTplTags = array();


    // Get all registered tags from the DB
    $bindvars = array();
    $sSql = "SELECT xar_id, xar_name, xar_module
              FROM $xartable[template_tags] WHERE 1=1 ";
        if (isset($module) && trim($module) != '') {
            $sSql .= " AND xar_module = ?";
            $bindvars[] = $module;
        }
        if (isset($id) && trim($id) != '') {
            $sSql .= " AND xar_id = ? ";
            $bindvars[] = $id;
        }

    $oResult = $dbconn->Execute($sSql,$bindvars);
    if (!$oResult) return;
    if (!$oResult) {
        $sMsg = 'Could not get any Tags';
        xarSession::setVar('errormsg',xarML($sMsg));
        return false;
    }

    while(!$oResult->EOF) {
            $aTplTags[] = array(
                    'id'      => $oResult->fields[0],
                    'name'    => $oResult->fields[1],
                    'module'  => $oResult->fields[2]
                );

        $oResult->MoveNext();
    }
    $oResult->Close();

    return $aTplTags;
}

?>