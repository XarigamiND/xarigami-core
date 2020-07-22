<?php
/**
 * Modify Block group
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * modify block group
 */
function blocks_admin_modify_group()
{
    if (!xarVarFetch('gid', 'int:1:', $gid)) {return;}

    // Get details on current group
    $group = xarMod::apiFunc('blocks', 'user', 'groupgetinfo', array('gid' => $gid));
    $data = array();
    
    // Security Check
    if(!xarSecurityCheck('EditBlockGroup', 0, 'Blockgroup',"{$group['name']}")) {return xarResponseForbidden();}
    
    //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');  

    //create options for dropdown
    $instanceoptions = array();
    foreach ($group['instances'] as $k=>$v) {
        $title = isset($v['title']) ? ' ('.$v['title'].')':'';
        $name = $v['name'];
        $instanceoptions[$v['id']] = $name.$title;
    }

    $data = array(
        'group'            => $group,
        'instance_count'   => count($group['instances']),
        'authid'           => xarSecGenAuthKey('blocks'),
        'moveuplabel'      => xarML('Move selected instance up'),
        'movedownlabel'    => xarML('Move selected instance down'),
        'updatelabel'      => xarML('Update'),
        'instanceoptions'  => $instanceoptions,
        'menulinks'         => $menulinks
    );
    return $data;
}

?>