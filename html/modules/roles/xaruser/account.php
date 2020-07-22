<?php
/**
 * Displays the dynamic user menu.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Xarigami Roles module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Displays the dynamic user menu.
 *
 * The menu is formed by data from the roles module, hooked Dynamic Data
 * and other hooked modules. Hooked modules should provide a hook called 'usermenu'
 * to show a submenu in this function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @param string moduleload The current module. This can be a hooked menu for which the menu is activated.
 * @todo    Finish this function.
 */
function roles_user_account()
{
    if(!xarVarFetch('moduleload','str', $data['moduleload'], '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('readytoreset', 'int', $data['readytoreset'], 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('template','str:1:100',$template,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarUserIsLoggedIn()){
        xarResponseRedirect(xarModURL('authsystem','user','showloginform'));
    }

    $uid         = xarUserGetVar('uid');
    $data['uid']     = $uid;
    $data['name']         = xarUserGetVar('name');
    $data['logoutmodule'] = 'authsystem'; // backward compat
    $data['loginmodule']  = 'authsystem'; // backward compat
    $data['authmodule']   = 'authsystem';
    $data['usersendemails'] = xarModUserVars::get('roles', 'usersendemails');
    $data['allowemail'] = xarModUserVars::get('roles', 'allowemail',$uid);
    $data['setpasswordupdate'] = xarModGetVar('roles','setpasswordupdate');
    $data['invalid'] = isset($invalid)?$invalid:array();
    $data['avatar_type']= xarUserGetVar('avatar_type',$uid);
    if ($data['uid'] == XARUSER_LAST_RESORT) {
        $data['message'] = xarML('You are logged in as the last resort administrator.');
    } else  {
        $data['current'] = xarModURL('roles', 'user', 'display', array('uid' => xarUserGetVar('uid')));

        $output = array();
        $output = xarMod::callHooks('item', 'usermenu', '', array('module' => 'roles'));

        if (empty($output)){
            $message = xarML('There are no account options configured.');
        }
        $data['output'] = $output;

        if (empty($message)){
            $data['message'] = '';
        }
    }

    return xarTplModule('roles','user','account',$data,$template);
}

?>