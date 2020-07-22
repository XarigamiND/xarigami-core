<?php
/**
 * Reset all menus to the active state
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * reset all menus to the active state
 * this is primarily used to prevent users still having
 * collapsed menus if the administrator turns off
 * collapseable menu support
 * @author Jim McDonald, Paul Rosania
 * @return true on success, false on failure
 */
function blocks_userapi_reactivate_menus()
{
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $ublockstable = $xartable['userblocks'];

    $query="UPDATE $ublockstable
               SET xar_active=?
             WHERE xar_active=?";

    $result = $dbconn->Execute($query,array(1,0));
    if (!$result)
        return;

    return true;
}

?>