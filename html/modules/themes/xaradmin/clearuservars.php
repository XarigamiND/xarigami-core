<?php
/**
 * Confirm clearing of uservars for theme
 *
 * @package modules
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Clear uservars for theme
 *
 * Loads theme admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @access public
 * @return bool true on success
 */
function themes_admin_clearuservars()
{
    // Security check

    if(!xarVarFetch('confirm','checkbox', $confirm, false, XARVAR_NOT_REQUIRED)) {return;}

    $returnurl =xarModURL('themes','admin','modifyconfig');
    if (!$confirm) {
        xarSession::delVar('statusmsg');
        $data['returnurl']= $returnurl;
        $data['authid'] = xarSecGenAuthKey();
        return $data;

    } else {
       if (!xarSecConfirmAuthKey()) return;
        // Remove theme vars
        $clearvars = xarMod::apiFunc('themes', 'admin', 'clearuservars');
        if ($clearvars ) {
            $msg = xarML('User vars for personal themes have been successfully cleared.');
            xarTplSetMessage($msg,'status');
        } else {
            $msg = xarML('User vars for personal themes could not be cleared. Please notify the System Administrator');
              xarTplSetMessage($msg,'error');
        }
        xarResponseRedirect($returnurl);
    }
}
?>