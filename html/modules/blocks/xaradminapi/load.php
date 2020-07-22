<?php
/**
 * Load a block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Load a block.
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @param string modName the module name (deprec)
 * @param string module the module name
 * @param string blockType the name of the block (deprec)
 * @param string type the name of the block
 * @param string blockFunc the block function to load (deprec)
 * @param string func the block function to load ('modify', 'display', 'info', 'help')
 * @return boolean success or failure
 * @throws EmptyParameterException, ClassNotFoundException, FunctionNotFoundException,
 *         FileNotFoundException
 */
function blocks_adminapi_load($args)
{
    // Array of block loaded flags.
    static $loaded = array();

    extract($args);

    // Legacy
    if (isset($modName)) {$module = $modName;}
    if (isset($blockType)) {$type = $blockType;}
    if (isset($blockFunc)) {$func = $blockFunc;}
 
    if (empty($module)) throw new EmptyParameterException('module');

    // Legacy - some modules still passing in a 'blockName'.
    if (!empty($blockName)) {$type = $blockName;}

    // These really are block types, as defined in the block_types.xar_type column.
    if (empty($type)) {
        throw new EmptyParameterException('type');
    }

    if (
        (isset($loaded[$module . ':' . $type]) && empty($func))
        || (!empty($func) && isset($loaded[$func . '-' . $module . ':' . $type]))
    ) {
        // The relevant files have already been loaded.
        return true;
    }

    // Details for the module.
    $modBaseInfo = xarMod::getBaseInfo($module);
    if (empty($modBaseInfo)) {return;}

    // Directory holding the block scripts.
    $blockDir = sys::code().'modules/' . $modBaseInfo['osdirectory'] . '/xarblocks';

    // Load the block.
    // The base block file will always be loaded, and a more specific block
    // function will be loaded if available and requested.

    if (!isset($loaded[$module . ':' . $type])) {
        // Load the block base script.

        $blockFile = $type . '.php';
        $filePath = $blockDir . '/' . xarVarPrepForOS($blockFile);

        if (!file_exists($filePath)) {
            // TODO: should the block base be optional now?
            // i.e. do we really need to raise an error?
            throw new FileNotFoundException($filePath,xarML("No file found at #(1)"));
        }
        include($filePath);
        $loaded[$module . ':' . $type] = 1;

        // Load the block language files
        if(!xarMLSLoadTranslations($filePath)) {
            // What to do here? return doesnt seem right
            return;
        }
    }

    if (!empty($func) && !isset($loaded[$func . '-' . $module . ':' . $type])) {
        // Load the block function script, if available.

        $blockFile = $func . '-' . $type . '.php';
        $filePath = $blockDir . '/' . xarVarPrepForOS($blockFile);

        if (file_exists($filePath)) {
            include($filePath);
        }

        // Flag the script as loaded.
        $loaded[$func . '-' . $module . ':' . $type] = 1;
    }

    return true;
}

?>