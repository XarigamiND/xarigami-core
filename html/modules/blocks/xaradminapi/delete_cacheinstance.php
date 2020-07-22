<?php
/**
 * Delete a cache block instance
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * delete a cache block
 * @param int $args['bid'] the ID of the block to delete
 * @param str $args['blockmodule'] the module of the block to delete
 * @param str $args['blocktype'] the blocktype of the block to delete
 * @param str $args['blockname'] the name of the block to delete 
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete_cacheinstance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Invalid parameter');
        throw new BadParameterException(null,$msg);
    }
    //jojodee - this will never work when the block is already deleted!
    //$blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => (int)$bid));
    
    // Security Check
    if(!xarSecurityCheck('DeleteBlock',1,'Block',"{$blockmodule}:{$blocktype}:{$blockname}")) return;


    $dbconn =  xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    if (!empty($xartable['cache_blocks'])) {
        $cacheblockstable = $xartable['cache_blocks'];
        $query = "DELETE FROM $cacheblockstable
                  WHERE xar_bid = ?";
        $result = $dbconn->Execute($query,array($bid));
        if (!$result) return;
    }
    return true;
}
?>