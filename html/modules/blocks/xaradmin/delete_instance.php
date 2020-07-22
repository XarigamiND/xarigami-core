<?php
/**
 * Block management - delete a block
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
 * delete a block instance
 */
function blocks_admin_delete_instance()
{
    // Get parameters
    if (!xarVarFetch('bid', 'id', $bid)) return;
    if (!xarVarFetch('name', 'str:1:', $name, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str:1:', $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cancel', 'str:1:', $cancel, '', XARVAR_NOT_REQUIRED)) return;
    // Get details on current block
    $blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('bid' =>(int)$bid));

    // Security Check
    if(!xarSecurityCheck('DeleteBlock',0,'Block',"{$blockinfo['module']}:{$blockinfo['type']}:{$blockinfo['name']}")) return xarResponseForbidden();
    //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');
    $returnurl = isset ($returnurl) && !empty($returnurl) ?$returnurl: xarModURL('blocks', 'admin', 'view_instances');
    // Check for confirmation
    if (!empty($cancel)) {
         xarResponseRedirect($returnurl);
    }elseif (empty($confirm)) {
        // No confirmation yet - get one
        return array(
            'instance' => $blockinfo,
            'authid' => xarSecGenAuthKey('blocks'),
            'deletelabel' => xarML('Delete'),
            'menulinks' =>$menulinks,
            'returnurl' => $returnurl
        );
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey('blocks')) {return;}
    // Pass to API
    xarMod::apiFunc('blocks', 'admin', 'delete_instance', array('bid' => (int)$bid,'name'=>$blockinfo['name']));

    xarResponseRedirect($returnurl);

    return true;
}

?>