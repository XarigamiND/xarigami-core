<?php
/**
 * Update configuration Xarigami core CSS
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
* Module admin function to update configuration Xarigami core CSS
*
* @return bool TRUE
*/
function themes_admin_corecssupdate()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;
    // Security Check
    if (!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    xarResponseRedirect(xarModURL('themes','admin','cssconfig',array('component'=>'core')));
    // Return
    return TRUE;
}

?>