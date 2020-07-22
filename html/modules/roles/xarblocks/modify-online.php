<?php
/**
 * Modify Online block
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Modify Function to the Blocks Admin
 * @author Jason Judge
 * @param $blockinfo array containing title,content
 */
function roles_onlineblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (!isset($vars['groups'])) {
        $vars['groups'] = array();
    }
    $vars['max_users'] = isset( $vars['max_users']) ? $vars['max_users']: 20;

    // Values used in the form update: list of groups, max count of users.
    $vars['all_groups'] = xarMod::apiFunc('roles','user','getallgroups');

    $vars['bid'] = $blockinfo['bid'];

    return $vars;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function roles_onlineblock_update($blockinfo)
{
    // Ensure content is an array.
    // TODO: remove this once all blocks can accept content arrays.
    if (!is_array($blockinfo['content'])) {
        $blockinfo['content'] = unserialize($blockinfo['content']);
    }

    // Pointer to content array.
    $vars =& $blockinfo['content'];

    if (xarVarFetch('groups', 'list:id', $groups, array(), XARVAR_NOT_REQUIRED)) {
        $vars['groups'] = $groups;
    }

    if (xarVarFetch('max_users', 'int:0:500', $max_users, 20, XARVAR_NOT_REQUIRED)) {
        $vars['max_users'] = $max_users;
    }

    return $blockinfo;
}

?>