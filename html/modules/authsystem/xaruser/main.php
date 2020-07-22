<?php
/**
 * Default user function
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  Function decides if user is logged in
 * and returns user to correct location.
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare<jojodee@xaraya.com>
 * @return bool true
 */
function authsystem_user_main()
{

       $hidemodurl = xarModGetVar('authsystem','hidemoduleurl');
    $useAliasName= xarModGetVar('authsystem','useModuleAlias');
    $currenturl= xarServerGetCurrentURL();
    $testurl = parse_url($currenturl);
    if (($hidemodurl == TRUE) && ($useAliasName == TRUE) ) {
        if (isset($testurl['query'])) {
            $isblocked =   stripos($testurl['query'], 'authsystem');
        } elseif (isset($testurl['path'])) {
            $isblocked =   stripos($testurl['path'], 'authsystem');
        }
        if ($isblocked == TRUE) {
             return xarResponseNotFound();
        }
    }
    //no registration here - just redirect to the login form
    xarResponseRedirect(xarModURL('authsystem','user','showloginform'));

    return true;
}

?>