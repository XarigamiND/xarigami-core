<?php
/**
 * View an error with a module
 *
 * @package modules
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

function modules_admin_activatehook()
{

    if (!xarVarFetch('callerModName', 'str', $callerModName, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('callerItemType', 'int', $callerItemType, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('hookModName', 'str', $hookModName, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('modMask', 'str', $modMask, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('returnurl', 'str:254', $returnurl, '', XARVAR_NOT_REQUIRED)) {return;}

    if (empty($callerModName) || empty($hookModName) || empty($modMask)) return; //we can't do it
    // Check we have minimum privs to edit this page
    if (!xarSecurityCheck("$modMask", 0)) {
            $msg = xarML('You have no permission to carry out this hook activation function');
            return xarResponseForbidden($msg);
    }

    $sethook = xarModAPIFunc('modules','admin','enablehooks',array('callerModName'=>$callerModName,'callerItemType'=>$callerItemType,'hookModName'=>$hookModName));
    if (!isset($callerItemType) || $callerItemType == 0) {
        $itemtext = xarML('All itemtypes');
    } else {
        $itemtype = xarML('itemtype #(1)',$callerItemType);
    }
    $msg = xarML('"#(1)" has been successfully hooked to "#(2)" for #(3).',$hookModName,$callerModName,$itemtype );
    xarTplSetMessage($msg,'status');
    if (empty($returnurl) && xarRequestIsLocalReferer()) {
        $returnurl = xarServerGetVar('HTTP_REFERER');
    }
    xarResponseRedirect($returnurl);
}

?>