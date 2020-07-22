<?php
/**
 * Block group management - delete a block group
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * delete a block group
 * @author Xarigami Core Development Team
 */
function blocks_admin_delete_group()
{
    if (!xarVarFetch('gid', 'int:1:', $gid)) {return;}
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('returnurl', 'str:1:', $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cancel', 'str:1:', $cancel, '', XARVAR_NOT_REQUIRED)) return;
    // Get details on current group
     $group = xarMod::apiFunc('blocks', 'admin', 'groupgetinfo',array('blockGroupId' => $gid));

    // Security Check
    if(!xarSecurityCheck('DeleteBlockGroup',0, 'Blockgroup',"{$group['name']}")) {return xarResponseForbidden();}
    //common admin menu
    $menulinks= xarMod::apiFunc('blocks','admin','getmenulinks');

    // Check for confirmation
    if (!empty($cancel)) {
          $returnurl = !empty($returnurl)?$returnurl:xarModURL('blocks', 'admin', 'view_groups');
         xarResponseRedirect($returnurl);
    }elseif (empty($confirm)) {
        // No confirmation yet - get one
        if ($group == NULL) {return;}
        $data = array('group' => $group,
                     'authid' => xarSecGenAuthKey('blocks'),
                     'deletelabel' => xarML('Delete'),
                     'menulinks' => $menulinks,
                     'returnurl' => $returnurl
        );
        return $data;
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey('blocks')) {return;}

    // Pass to API
    xarMod::apiFunc('blocks', 'admin', 'delete_group', array('gid' => $gid,'name'=> $group['name']));

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>