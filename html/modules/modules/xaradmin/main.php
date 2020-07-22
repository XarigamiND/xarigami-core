<?php
/**
 * Main modules module function
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * main modules module function
 * @return modules_admin_main
 *
 * @author Xarigami Development Team
 */
function modules_admin_main()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules',0)) return xarResponseForbidden();

    xarResponseRedirect(xarModURL('modules', 'admin', 'list'));

    // success
    return true;
}

?>