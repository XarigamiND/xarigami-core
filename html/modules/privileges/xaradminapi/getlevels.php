<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * get the name and description of all levels
 *
 
 */
function privileges_adminapi_getlevels($args)
{
    static $all_levels;

    $bindvars = array();
    $levels = array();
    if (count($args) == 0 && isset($all_levels)) {
        return $all_levels;
    }

    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();
    $levelstable = $xartable['security_levels'];

    // Get item
    $query = "SELECT xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription
               FROM  $levelstable
               WHERE xar_level >= 0
               ORDER BY xar_level ASC";

    $result = $dbconn->Execute($query);
    if (!$result) return;

    if (!$result->EOF) {
        while (!$result->EOF) {
            list($lid, $level, $levelname, $shortdesc, $longdesc) = $result->fields;
            $levels[$level] = array(
                'levelid' => $lid,
                'level' => $level,
                'levelname' => $levelname,
                'shortdesc' => $shortdesc,
                'longdesc'=>$longdesc
            );
            $result->MoveNext();
        }
    }

    // Cache the results if we are fetching all of them.
    if (count($args) == 0) $all_levels = $levels;
    return $levels;
}

?>