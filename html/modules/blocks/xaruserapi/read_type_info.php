<?php
/**
 * Read a block's type info.
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
 * @param args['module'] the module name
 * @param args['type'] the block type name
 * @return the block 'info' details (an array) or NULL if no details present
 */

function blocks_userapi_read_type_info($args)
{
    extract($args);

    if (empty($module) && empty($type)) {
        // No identifier provided.
        $msg = xarML('Invalid parameter: missing module or type');
        throw new EmptyParameterException(null,$msg);;
    }

    // Function to execute, to get the block info.
    $infofunc = $module . '_' . $type . 'block_info';

    if (function_exists($infofunc)) {
        return $infofunc();
        
    }

    // Load and execute the info function of the block.
    if (!xarMod::apiFunc(
        'blocks', 'admin', 'load',
        array(
            'modName' => $module,
            'blockName' => $type,
            'blockFunc' => 'info'
        )
    )) {return;}

    if (function_exists($infofunc)) {
        return $infofunc();
    } else {
        // No block info function found.
        return;
    }
}

?>
