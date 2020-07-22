<?php
/**
 * View block groups
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
 * view block groups
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_view_groups()
{
    // Security Check
    //if(!xarSecurityCheck('EditBlockGroup', 1)) {return;}

    $data=array();
    
    $block_grouplist = xarMod::apiFunc(
        'blocks', 'user', 'getallgroups', array('order' => 'name')
    );
    $authid = xarSecGenAuthKey('blocks');
    $viewcounter = 0;
    // Load up groups array
    $block_groups= array();
    foreach($block_grouplist as $index => $block_group) {
        if(xarSecurityCheck('EditBlockGroup', 0, 'Blockgroup',"{$block_group['name']}")) {

            $block_groups[$index] = xarMod::apiFunc('blocks', 'admin', 'groupgetinfo',
                array('blockGroupId' => $block_grouplist[$index]['gid']));
            $viewcounter=$viewcounter+1;
            //only display a blockgroup if the user can edit it
            $block_groups[$index]['id'] = $block_group['gid']; // Legacy
          // Get details on current group
            $block_groups[$index]['membercount'] = count($block_groups[$index]['instances']);
            if(xarSecurityCheck('DeleteBlockGroup', 0, 'Blockgroup',"{$block_group['name']}")) {
                $block_groups[$index]['deleteconfirm'] = xarML('Delete group #(1)?', "{$block_group['name']}");
                $block_groups[$index]['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_group', array('gid' => $block_group['gid'],'authid' => $authid));
            } else {
                $block_groups[$index]['deleteconfirm'] = '';
                $block_groups[$index]['deleteurl'] ='';
            }
            $block_groups[$index]['editurl']   = xarModUrl('blocks', 'admin', 'modify_group', array('gid' => $block_group['gid']));
        }
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('blocks','admin','getmenulinks');     
    $data['block_groups'] = $block_groups;
    $data['viewcounter']= $viewcounter;
    $data['authid'] =  $authid ;
    return $data;
}

?>