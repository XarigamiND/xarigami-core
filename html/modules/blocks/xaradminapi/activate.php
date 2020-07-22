<?php
/**
 * Activate a block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * activate a block
 * @author Jim McDonald, Paul Rosania
 * @param int $args['bid'] the ID of the block to activate
 * @return bool true on success, false on failure
 */
function blocks_adminapi_activate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Wrong arguments for blocks_adminapi_activate');
        throw new BadParameterException(null,$msg);
    }

    // Security
    if(!xarSecurityCheck('CommentBlock',0,'Block',"::$bid")) {return xarResponseForbidden();}

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $blockstable = $xartable['block_instances'];

    // Activate
    $query = "UPDATE $blockstable SET xar_state = ? WHERE xar_id = ?";
    $result = $dbconn->Execute($query,array(2,$bid));
    if (!$result) {return;}

    return true;
}

?>