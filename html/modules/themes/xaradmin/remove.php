<?php
/**
 * Remove a theme
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Remove a theme
 *
 * Loads theme admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @author Xarigami Core Development Team
 * @access public
 * @param id $ the theme id
 * @return bool true on success
 */
function themes_admin_remove()
{
    // Security check

    if (!xarVarFetch('id', 'int:1:', $id)) return;
    if(!xarVarFetch('checkedforvars','int:0:1', $checkedforvars,0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('count','int', $count, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('confirm','checkbox', $confirm, false, XARVAR_NOT_REQUIRED)) {return;}

    if (($count>0) && ($checkedforvars==0)) {

        $data['themeInfo'] = xarThemeGetInfo($id);
        $data['count'] = $count;
        $data['returnurl'] =xarModURL('themes','admin','list');
        $data['authid'] = xarSecGenAuthKey();
        return $data;
    }

    if (!xarSecConfirmAuthKey()) return;
    // Remove theme
    $removed = xarMod::apiFunc('themes', 'admin', 'remove',
                  array('regid' => $id, 'checkedforvars'=>$checkedforvars,'confirm'=>$confirm));
        // throw back
    if (!isset($removed)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

   return true;
}

?>