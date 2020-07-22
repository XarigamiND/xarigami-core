<?php
/**
 * Main admin function
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles module
 * @copyright (C) 2007-2010 2skies.com 
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * the main administration function
 */
function roles_admin_main()
{
    // Security Check, don't throw an exception
    if (!xarSecurityCheck('EditRole',0) && !xarSecurityCheck('ModerateGroupRoles',0)) {
        //handle it ourselves with a forbidden response
        return xarResponseForbidden();
    }
    xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
    // success
    return true;
}
?>