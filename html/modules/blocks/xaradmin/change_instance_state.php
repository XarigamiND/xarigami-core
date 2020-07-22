<?php
/**
 * Change state of a block instance
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * changea block instance state
 */

function blocks_admin_change_instance_state()
{
    // Get parameters
    if (!xarVarFetch('bid', 'int:1:', $bid)) {return;}
    xarVarFetch('blockstate', 'int:0:2',     $blockstate, 0, XARVAR_NOT_REQUIRED);
     xarVarFetch('returnurl', 'str:0:254',     $returnurl, '', XARVAR_NOT_REQUIRED);
    // Get the instance details.
    $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));

    // Security Check
    if(!xarSecurityCheck('EditBlock',0,'Block',"{$instance['module']}:{$instance['type']}:{$instance['name']}")) {
        return xarResponse::Forbidden();
    }

    $updatestate = xarModAPIFunc('blocks','admin','update_state_info',array('bid'=>$bid,'blockstate'=>$blockstate));

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    $hooks = array();
    $hooks = xarMod::callHooks('item', 'modify', $bid, $args);

    if (empty($returnurl)) $returnurl = xarModURL('blocks','admin','modify_instance',array('bid'=>$bid));
    xarResponseRedirect($returnurl);
}

?>