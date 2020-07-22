<?php
/**
 * Modify a block instance
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * modify a block instance
 * @author Jim McDonald, Paul Rosania
 */

function blocks_admin_modify_instance()
{
    // Get parameters
    if (!xarVarFetch('bid', 'int:1:', $bid)) {return;}
    xarVarFetch('returnurl', 'str:0:254',     $returnurl, '', XARVAR_NOT_REQUIRED);
    xarVarFetch('invalid', 'array',     $invalid, array(), XARVAR_NOT_REQUIRED);
    // Get the instance details.
    $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));

    // Security Check
    if(!xarSecurityCheck('EditBlock',0,'Block',"{$instance['module']}:{$instance['type']}:{$instance['name']}")) {
        return xarResponse::Forbidden();
    }
    // Load block
    if (!xarMod::apiFunc('blocks', 'admin', 'load',
        array(
            'modName' => $instance['module'],
            'blockName' => $instance['type'],
            'blockFunc' => 'modify')
        )
    ) {return;}

    // Determine the name of the update function.
    // Execute the function if it exists.
    $usname = preg_replace('/ /', '_', $instance['module']);
    $modfunc = $usname . '_' . $instance['type'] . 'block_modify';

    if (function_exists($modfunc)) {
        $extra = $modfunc($instance);
        if (is_array($extra)) {
            // Render the extra settings if necessary.
            $extra = xarTplBlock($instance['module'], 'modify-' . $instance['type'], $extra);
        }
    } else {
        $extra = '';
    }

    // Get the block info flags.
    $block_info = xarMod::apiFunc(
        'blocks', 'user', 'read_type_info',
        array(
            'module' => $instance['module'],
            'type' => $instance['type']
        )
    );

    if (empty($block_info)) {
        // Function does not exist so throw error
        throw new FunctionNotFoundException(array($instance['module'],$instance['type']),
                                        'Block info function for module "#(1)" and type "#(2)" was not found or could not be loaded');
    }

    // Build refresh times array.
    // TODO: is this still used? Is it specific to certain types of block only?
    $refreshtimes = array(
        array('id' => 1800, 'name' => xarML('Half Hour')),
        array('id' => 3600, 'name' => xarML('Hour')),
        array('id' => 7200, 'name' => xarML('Two Hours')),
        array('id' => 14400, 'name' => xarML('Four Hours')),
        array('id' => 43200, 'name' => xarML('Twelve Hours')),
        array('id' => 86400, 'name' => xarML('Daily'))
    );

    // Fetch complete block group list.
    $block_groups = xarMod::apiFunc('blocks', 'user', 'getallgroups', array('order' => 'name'));

    // In the modify form, we want to provide an array of checkboxes: one for each group.
    // Also a field for the overriding template name for each group instance.
    $groupoptions = array();
    foreach ($block_groups as $key => $block_group) {
        $gid = $block_group['gid'];
        if (isset($instance['groups'][$gid])) {
            $block_groups[$key]['selected'] = true;
            $block_groups[$key]['template'] = $instance['groups'][$gid]['group_inst_template'];
        } else {
            $block_groups[$key]['selected'] = false;
            $block_groups[$key]['template'] = '';
        }
        $groupoptions[$gid] = $block_group['name'];
    }
    //set blockstate options
    $stateoptions =  array('0'=>xarML('Inactive'),'1'=>xarML('Hidden'),'2'=>xarML('Visible'));
    //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');

    $args = array();
    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    $hooks = array();
    $hooks = xarMod::callHooks('item', 'modify', $bid, $args);

   $infoarray = array(
        'authid'         => xarSecGenAuthKey('blocks'),
        'bid'            => $bid,
        'block_groups'   => $block_groups,
        'instance'       => $instance,
        'extra_fields'   => $extra,
        'block_settings' => $block_info,
        'hooks'          => $hooks,
        'refresh_times'  => $refreshtimes,
        'stateoptions'   => $stateoptions,
        'groupoptions'   => $groupoptions,
        'returnurl'      => $returnurl,
        // Set 'group_method' to 'min' for a compact group list,
        // only showing those groups that have been selected.
        // Set to 'max' to show all possible groups that the
        // block could belong to.
        'group_method'   => 'min', // 'max'
        'menulinks'      => $menulinks,
        'invalid'        => $invalid
    );

 return $infoarray;
}

?>