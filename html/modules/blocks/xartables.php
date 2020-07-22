<?php
/**
 * Blocks table management and initialization
 *
 * @package Blocks module
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

function blocks_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the example item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $blockinstances = xarDB::$prefix . '_block_instances';
    $blocktypes = xarDB::$prefix . '_block_types';
    $cacheblocks = xarDB::$prefix . '_cache_blocks';

    // Set the table name
    $xartable['blockinstances'] = $blockinstances;
    $xartable['block_types'] = $blocktypes;
    $xartable['cache_blocks'] = $cacheblocks;

    // Return the table information
    return $xartable;
}

?>
