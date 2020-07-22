<?php
/**
 * Log a user in
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * log a user in
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uname'] user name of user
 * @param $args['pass'] password of user
 * @param $args['rememberme'] remember this user (optional)
 * @return true on success, false on failure
 */
function authsystem_userapi_login($args)
{
    extract($args);

    // FIXME: this should be removed as far as possible
    if (isset($passwd) && !isset($pass)) {
        die("authsystem_userapi_login: authsystem_userapi_login prototype has changed, " .
            "you should use pass instead of passwd to " .
            "avoid this message being displayed");
    }

    if (!isset($rememberme)) {
        $rememberme = 0;
    }

    if ((!isset($uname)) ||
        (!isset($pass))) {
        throw new BadParameterException(null,xarML('Wrong arguments to authsystem_userapi_login.'));
    }
    //Values are true, or a string with authsystem tpl error name
    return xarUserLogIn($uname, $pass, $rememberme);
}

?>