<?php
/**
 * Set a user variable
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * set a user variable (currently unused)
 * @access public
 * @author Gregor J. Rothfuss
 * @param args['uid'] user id
 * @param args['name'] variable name
 * @param args['value'] variable value
 * @return bool
 */
function authsystem_userapi_set_user_variable($args)
{
    extract($args);

    if (!isset($uid) || !isset($name) || !isset($value)) {
        throw new EmptyParameterException(array('uid or name or value','admin','set_user_variable','authsystem'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    return true;
}

?>