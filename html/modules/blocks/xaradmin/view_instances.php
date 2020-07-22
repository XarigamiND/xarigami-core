<?php
/**
 * View block instances
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
 * view block instances
 */
function blocks_admin_view_instances()
{
    if (!xarVarFetch('filter',   'str',    $filter,   "", XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startat',  'int',    $startat,  1,  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum, 1,  XARVAR_NOT_REQUIRED)) return;

    $authid = xarSecGenAuthKey('blocks');
    // Get all block instances (whether they have group membership or not.

    $rowstodo = xarModGetVar('blocks','itemsperpage');
    // Need to find a better way to do this without breaking the API
    $instances = xarMod::apiFunc('blocks', 'user', 'getall',
                                         array('filter' => $filter,
                                               'order' => 'name',
                                               'startnum' => $startnum,
                                               'numitems' => $rowstodo));

    $total = count($instances);
    //array to hold list of allowed viewable blocks

    //initialize number of rows viewable for this admin/editor
    $viewablerows = 0;
    $blockinstances = array();
    // Create extra links and confirmation text.
    foreach ($instances as $index => $instance) {
        if(xarSecurityCheck('EditBlock',0,'Block',"{$instance['module']}:{$instance['type']}:{$instance['name']}")){
            $instance['editurl'] = xarModUrl('blocks', 'admin', 'modify_instance', array('bid' => $instance['bid'], 'authid' => $authid));
            if(xarSecurityCheck('DeleteBlock',0,'Block',"{$instance['module']}:{$instance['type']}:{$instance['name']}")){
                $instance['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_instance',
                    array('bid' => $instance['bid'], 'authid' => $authid));
                $instance['deleteconfirm'] = xarML('Delete instance "#(1)"', addslashes($instance['name']));
            } else {
                $instance['deleteurl'] ='';
                $instance['deleteconfirm'] ='';
            }

            if(xarSecurityCheck('AdminBlock',0,'Block',"{$instance['module']}:{$instance['type']}:{$instance['name']}")){
                $instance['typeurl'] = xarModUrl('blocks', 'admin', 'view_types',
                     array('tid' => $instance['tid']));
            } else {
                $instance['typeurl'] = '';
            }

            $viewablerows = $viewablerows +1; //+=1;
            $blockinstances[$index]= $instance;
        } else {
            $instance['editurl'] ='';
        }

    }


    $data['authid'] = $authid;
    // Item filter and pager
    $data['filter'] = $filter;

    $data['pager'] = xarTplGetPager($startnum,
                            xarMod::apiFunc('blocks', 'user', 'countblocks',array('filter'=>$filter, 'privcheck'=>true)),
                            xarModURL('blocks', 'admin', 'view_instances',array('startnum' => '%%')),
                            xarModGetVar('blocks', 'itemsperpage'));

    // Select vars for drop-down menus.
    $data['viewablerows'] = $viewablerows;
    // State descriptions.
    $data['state_desc'][0] = xarML('Inactive');
    $data['state_desc'][1] = xarML('Hidden');
    $data['state_desc'][2] = xarML('Visible');
    $data['blocks'] = $instances; //leave this for backward compatibility
    $data['blockinstances'] = $blockinstances; //new list of viewable blocks
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('blocks','admin','getmenulinks');

    return $data;
}

?>