<?php
/**
 * Update a block
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
 * update a block
 */
function blocks_admin_update_instance()
{
    // Get parameters
    $invalid = array();
    if (!xarVarFetch('bid', 'int:1:', $bid)) {return;}
    if (!xarVarFetch('block_groups', 'keylist:id;checkbox', $block_groups, array(), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_new_group', 'id', $block_new_group, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_remove_groups', 'keylist:id;checkbox', $block_remove_groups, array(), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_name', 'pre:lower:ftoken:field:Name:passthru:str:1:100', $name, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_title', 'str:1:255', $title, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_state', 'int:0:4', $state)) {return;}
    if (!xarVarFetch('block_template', 'strlist:;,:pre:trim:lower:ftoken', $block_template, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('group_templates', 'keylist:id;strlist:;,:pre:trim:lower:ftoken', $group_templates, array(), XARVAR_NOT_REQUIRED)) {return;}
    // TODO: deprecate 'block_content' - make sure each block handles its own content entirely.
    if (!xarVarFetch('block_content', 'str:1:', $content, NULL, XARVAR_NOT_REQUIRED)) {return;}
    // TODO: check out where 'block_refresh' is used. Could it be used more effectively?
    // Could the caching be supported in a more consistent way, so individual blocks don't
    // need to handle it themselves?
    if (!xarVarFetch('block_refresh', 'int:0:', $refresh, 0, XARVAR_NOT_REQUIRED)) {return;}
    xarVarFetch('returnurl', 'str:0:254',     $returnurl, '', XARVAR_NOT_REQUIRED);
    //check required fields
    if (!isset($name) || empty($name)) {
         $invalid['block_name'] = xarML('Block name was left blank. Your changes were not saved and original name is restored!');
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Get and update block info.
    $blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));

    // Security Check.
    //we need to know if there is a newly named block .. could it have been changed?
    // If the name is being changed, then check the new name has not already been used.
    if ($blockinfo['name'] != $name) {
        $checkname = xarMod::apiFunc('blocks', 'user', 'get', array('name' => $name));
        if (!empty($checkname)) {
            $invalid['block_name'] = xarML('The block name "#(1)" already exists, no duplicates are allowed.',$name);
        }
    }

    if (count($invalid) > 0) {
        xarResponseRedirect(xarModURL('blocks','admin', 'modify_instance', array('invalid'=>$invalid,'bid'=>$bid)));
        return;
    }
    if(!xarSecurityCheck('EditBlock',1,'Block',"{$blockinfo['module']}:{$blockinfo['type']}:{$name}")) return;

    $blockinfo['name'] = $name;
    $blockinfo['title'] = $title;
    $blockinfo['template'] = $block_template;
    $blockinfo['refresh'] = (int)$refresh;
    $blockinfo['state'] = (int)$state;

    if (isset($content)) {
        $blockinfo['content'] = $content;
    }

    // Pick up the block instance groups and templates.
    $groups = array();
    foreach($block_groups as $gid => $block_group) {
        // Set the block group so long as the 'remove' checkbox is not set.
        if (!isset($block_remove_groups[$gid]) || $block_remove_groups[$gid] == false) {
            $groups[] = array('gid' => $gid,'template' => $group_templates[$gid]);
        }
    }
    // The block was added to a new block group using the drop-down.
    if (!empty($block_new_group)) {
        $groups[] = array('gid' => $block_new_group,'template' => '');
    }
    $blockinfo['groups'] = $groups;

    // Load block
    if (!xarMod::apiFunc('blocks', 'admin', 'load',
            array('module' => $blockinfo['module'],
                  'type' => $blockinfo['type'],
                  'func' => 'modify'
                )
        )
    ) {return;}

    // Do block-specific update
    $usname = preg_replace('/ /', '_', $blockinfo['module']);
    $updatefunc = $usname . '_' . $blockinfo['type'] . 'block_update';

    if (function_exists($updatefunc)) {
        $blockinfo = $updatefunc($blockinfo);
    } else {
        $blockinfofunc = $usname . '_' . $blockinfo['type'] . 'block_info';
        $blockdesc = $blockinfofunc();
        if (!empty($blockdesc['func_update'])) {
            $updatefunc = $blockdesc['func_update'];
            if (function_exists($updatefunc)) {
                $blockinfo = $updatefunc($blockinfo);
            }
        }
    }

    // Pass to API - do generic updates.
    if (!xarMod::apiFunc('blocks', 'admin', 'update_instance', $blockinfo)) {return;}

    // Resequence blocks within groups.
    if (!xarMod::apiFunc('blocks', 'admin', 'resequence')) {return;}
        $msg = xarML('The block name "#(1)" has been successfully updated.',$blockinfo['name']);
        xarTplSetMessage($msg,'status');
        //jojo - TODO - return via xarTplM
   $returnurl = isset($returnurl) && !empty($returnurl)? $returnurl: xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $bid,'name'=>$blockinfo['name']));

    xarResponseRedirect($returnurl);

    return true;
}

?>