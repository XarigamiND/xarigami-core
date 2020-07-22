<?php
/**
 * Check for existance of a block type
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Check for existance of a block type
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true if exists, false if not found
 * @throws DATABASE_ERROR, BAD_PARAM
 * @deprec Deprecated 11 Jan 2004 - use countblocktypes directly
 */
function blocks_adminapi_block_type_exists($args)
{
    extract($args);

    if (empty($modName)) {
        throw new EmptyParameterException(array('modName','adminapi','block_type_exists','blocks'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    if (empty($blockType)) {
        throw new EmptyParameterException(array('blockType','adminapi','block_type_exists','blocks'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    $count = xarMod::apiFunc('blocks', 'user', 'countblocktypes', array('module'=>$modName, 'type'=>$blockType));

    return ($count > 0) ? true : false;
}

?>