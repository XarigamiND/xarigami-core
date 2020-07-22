<?php
/**
 * Get a user variable
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
 * get a user variable (currently unused)
 * @access public
 * @author Marco Canini
 * @param args['uid'] user id
 * @param args['name'] variable name
 * @return string
 */
function authsystem_userapi_get_user_variable($args)
{
    // Second level cache
    static $vars = array();

    extract($args);

    if (!isset($uid) || !isset($name)) {
        throw new EmptyParameterException(array('uid or name','admin','get_user_variable','authsystem'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    if (!isset($vars[$uid])) {
        $vars[$uid] = array();
    }

    if (!isset($vars[$uid][$name])) {
        $vars[$uid][$name] = false;
    }

    // Return the variable
    if (isset($vars[$uid][$name])) {
        return $vars[$uid][$name];
    } else {
        return false;
    }
}

?>