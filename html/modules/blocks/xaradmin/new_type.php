<?php
/**
 * Register a new block type
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Register New Block Type
 */
function blocks_admin_new_type()
{
    // Security Check
    if (!xarSecurityCheck('AdminBlock', 0)) {return xarResponseForbidden();}

    // Get parameters
    if (!xarVarFetch('moduleid',   'id:', $modid, xarMod::getId('base'), XARVAR_NOT_REQUIRED)) { return; }
    if (!xarVarFetch('blockname', 'str:1:', $blockname, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('submit', 'str:1:', $submit, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('scan', 'str:1:', $scan, '', XARVAR_NOT_REQUIRED)) {return;}

    // Initialise the list.
    $type_list = array();
    $modinfo = xarMod::getInfo($modid); //pass registration id not system id
    if (!empty($scan)) {
        // 'Scan' button pressed.

        // Get a list of block types from the module files.
        if (!empty($modinfo)) {
            // TODO: should 'modules' be hard-coded here?
            $blocks_path = 'modules/' . $modinfo['directory'] . '/xarblocks';

            // Open the directory and read all the files.
            $dir_handle = @opendir($blocks_path);
            if ($dir_handle !== FALSE) {
                while (false !== ($file = readdir($dir_handle))) {
                    // A block file contains no underscores, and is not 'index.php'
                    if (preg_match('/^[a-z0-9]+\.php$/', $file) && $file != 'index.php') {
                        // Add the name of the block type to the list.
                        $name = str_replace('.php', '', $file);
                        $type_list[$name] = $name;
                    }
                }
                closedir($dir_handle);
            }
        }
    }
     //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');

    if (!empty($submit)) {
        // Submit button was pressed

        // Confirm Auth Key
        if (!xarSecConfirmAuthKey()) {return;}

        // Create the block type.
        $modulename = $modinfo['name'];
        if (!xarMod::apiFunc('blocks', 'admin', 'create_type',
            array('module' => $modulename, 'type' => $blockname))
        ) {return;}

        xarResponseRedirect(xarModURL('blocks', 'admin', 'view_types'));
        return true;
    } else {
        // Nothing submitted yet - return a blank form.
        return array(
            'authid' => xarSecGenAuthKey('blocks'),
            'moduleid' => $modid,
            'type_list' => $type_list,
            'blockname' => $blockname,
            'menulinks' => $menulinks
        );
    }
}

?>