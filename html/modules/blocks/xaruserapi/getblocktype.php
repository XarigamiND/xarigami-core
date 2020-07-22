<?php
/**
 * Get a single block type.
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
 * Get a single block type.
 * @param args['tid'] block type ID (optional)
 * @param args['module'] module name (optional, but requires 'type')
 * @param args['type'] block type name (optional, but requires 'module')
 * @return array of block types, keyed on block type ID
 * @author Jason Judge
*/

function blocks_userapi_getblocktype($args)
{
    // Minimum parameters allowed, to fetch a single block type: tid or type.
    if (empty($args['tid']) && (empty($args['module']) || empty($args['type']))) {
        $msg = xarML('blocks_userapi_getblocktype (tid and module/type are NULL)');
        throw new BadParameterException(null,$msg);
    }

    $types = xarMod::apiFunc('blocks', 'user', 'getallblocktypes', $args);

    // We should have exactly one block type: throw back if not.
    if (count($types) <> 1) {return;}

    return(array_pop($types));
}

?>