<?php
/**
 * Display form for a new block instance
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
 * display form for a new block instance
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_new_instance()
{
    // Security Check
    if(!xarSecurityCheck('AddBlock',0)) return xarResponseForbidden();

    // Can specify block types for a single module.
    xarVarFetch('formodule', 'str:1', $module, NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('invalid', 'array',     $invalid, array(), XARVAR_NOT_REQUIRED);

    $allowedblocktypes = array();

    // Fetch block type list.
    $block_types = xarMod::apiFunc('blocks', 'user', 'getallblocktypes',
        array('order' => 'module,type', 'module' => $module)
    );
    $blocktypeoptions = array();
    foreach ($block_types as $blocktype) {
        if (xarSecurityCheck('AddBlock', 0, 'Block', "{$blocktype['module']}:{$blocktype['type']}:All"))
        {
            $allowedblocktypes[] = $blocktype;
            $blocktypeoptions[$blocktype['tid']] = xarVarPrepForDisplay($blocktype['module']).'/'.xarVarPrepForDisplay($blocktype['type']);
        }
    }
     //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');

    // Fetch available block groups.
    $block_groups = xarMod::apiFunc('blocks', 'user', 'getallgroups', array('order' => 'name'));
    $blockstateoptions = array('0'=>xarML('Inactive'),'1'=>xarML('Hidden'),'2'=>xarML('Visible'));
   //options for groups
   $groupoptions = array();
   foreach ($block_groups as $k=>$v) {
    $groupoptions[$v['gid']] = $v['name'];
   }
    return array(
        'block_types'  => $allowedblocktypes,
        'block_groups' => $block_groups,
        'groupoptions'  => $groupoptions,
        'blocktypeoptions' =>$blocktypeoptions,
        'blockstateoptions' => $blockstateoptions,
        'createlabel'  => xarML('Create Instance'),
        'authid'      => xarSecGenAuthKey('blocks'),
        'menulinks' =>$menulinks,
        'invalid' => $invalid
    );
}

?>