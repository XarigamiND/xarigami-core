<?php
/**
 * Check for module capability
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
 * check whether this module has a certain capability
 * @access public
 * @param string args['capability'] the capability to check for
 * @author Marco Canini
 * @return bool
 */
function authsystem_userapi_has_capability($args)
{
    extract($args);

    assert(isset($capability));

    switch($capability) {
        case XARUSER_AUTH_AUTHENTICATION:
            return true;
            break;
        case XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER:
        case XARUSER_AUTH_USER_ENUMERABLE:
        case XARUSER_AUTH_PERMISSIONS_OVERRIDER:
        case XARUSER_AUTH_USER_CREATEABLE:
        case XARUSER_AUTH_USER_DELETEABLE:
            return false;
            break;
    }
    $msg = xarML('Unknown capability.');
    throw new BadParameterException(null,$msg);
}

?>
