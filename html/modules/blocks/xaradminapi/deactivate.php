<?php
/**
 * Deactivate a block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * deactivate a block
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] the ID of the block to deactivate
 * @return bool true on success, false on failure
 */
function blocks_adminapi_deactivate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid)) {
        xarSession::setVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
    if(!xarSecurityCheck('CommentBlock',0,'Block',"::$bid")) return xarResponseForbidden();

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $blockstable = $xartable['block_instances'];

    // Deactivate
    $query = "UPDATE $blockstable SET xar_state = ?  WHERE xar_id = ?";
    $result = $dbconn->Execute($query,array(0, $bid));
    if (!$result) return;

    return true;
}

?>