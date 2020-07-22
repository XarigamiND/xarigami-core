<?php
/**
 * Authenticate a user
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
 * authenticate a user
 * @public
 * @author Marco Canini
 * @param args['uname'] user name of user
 * @param args['pass'] password of user
 * @todo use roles api, not direct db
 * @return int uid on successful authentication, XARUSER_AUTH_FAILED otherwise
 */
function authsystem_userapi_authenticate_user($args)
{
    extract($args);

    if (!isset($uname) || !isset($pass) || $pass == "") {
        throw new BadParameterException(array('uname or pass','admin','authenticate_user','authsystem'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    // Get user information from roles
    $userRole = xarMod::apiFunc('roles', 'user', 'get', array('uname' => $uname));

    if (!$userRole) {
        return XARUSER_AUTH_FAILED;
    }

    $uid =  $userRole['uid'];
    $realpass = $userRole['pass'];

    // Confirm that passwords match
    if (!xarUserComparePasswords($pass, $realpass, $uname, substr($realpass, 0, 2))) {
        return XARUSER_AUTH_FAILED;
    }

    return (int)$uid;
}

?>
