<?php
/**
 * Main admin GUI function
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Main admin gui function, entry point
 * @author John Robeson
 * @author Greg Allan
 * @return bool true on success of return to sysinfo
 */
function base_admin_main()
{
// Security Check
    if(!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();

    xarResponseRedirect(xarModURL('base', 'admin', 'modifyconfig'));

    // success
    return true;
}

?>