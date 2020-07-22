<?php
/**
 * Default user function
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments. Function decides if user is logged in
 * and returns user to correct location.
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
*/
function roles_user_main()
{

    if (xarUserIsLoggedIn()) {
        xarResponseRedirect(xarModURL('roles', 'user', 'account'));
    } else {

        //check for specified redirect url
        $anonurl = xarModGetVar('roles','anonurl')?xarModGetVar('roles','anonurl'):'';

        //setup the redirect url
        if (!empty($anonurl)) {
            $redirecturl = $anonurl;
        } else {
            $redirecturl = xarModURL('authsystem', 'user', 'main');
        }
        xarResponseRedirect($redirecturl);
    }
}

?>