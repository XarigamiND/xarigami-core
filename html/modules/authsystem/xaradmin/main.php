<?php
/**
 * Main admin function
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * the main administration function
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @return bool true
 */
function authsystem_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('AdminAuthsystem',0)) return xarResponseForbidden();

    xarResponseRedirect(xarModURL('authsystem', 'admin', 'modifyconfig'));

    // success
    return true;
}
?>