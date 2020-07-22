<?php
/**
 * Modify blocks configuration
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007=2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 * @author John Robeson
 * @author Greg Allan
 */
/**
 * Modify blocks configuration
 *
 * @return array of template values
 */
function blocks_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminBlock',0)) return xarResponseForbidden();
    if (!xarVarFetch('update', 'isset', $update, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemsperpage', 'int', $data['itemsperpage'], xarModGetVar('blocks', 'itemsperpage'), XARVAR_NOT_REQUIRED)) return;

    if($update) {
        if (!xarSecConfirmAuthKey()) return;
        xarModSetVar('blocks', 'itemsperpage',$data['itemsperpage']);
        $msg = xarML('Block configuration update was successful.');
        xarTplSetMessage($msg,'status');
    }
    $data['itemsperpage'] = xarModGetVar('blocks', 'itemsperpage');
    $data['authid'] = xarSecGenAuthKey('blocks');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('blocks','admin','getmenulinks');
    return $data;
}
?>