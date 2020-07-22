<?php
/**
 * Log user out of system
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
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @return bool true on success of redirect
 */
function authsystem_user_logout()
{
    $redirect=xarServer::getBaseURL();

    // Get input parameters
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;

    // Defaults
    //if (preg_match('/$authmodule}/',$redirecturl)) {
    if (strstr($redirecturl, 'authsystem')) {
        $redirecturl = $redirect;
    }

    // Log user out
    if (!xarUserLogOut()) {
        throw new ForbiddenOperationException(array('authsystem', 'logout'),xarML('Problem Logging Out.  Module #(1) Function #(2)'));
    }
    xarResponseRedirect($redirecturl);
    return true;
}
?>