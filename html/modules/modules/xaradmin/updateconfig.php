<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function modules_admin_updateconfig()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;

    // enable or disable overviews
    if(!xarVarFetch('itemsperpage','int:0:', $itemsperpage, 30, XARVAR_DONT_SET)) return;
    if(!xarVarFetch('auto3ptupgrade','checkbox', $auto3ptupgrade, false, XARVAR_DONT_SET)) return;

    xarModSetVar('modules', 'itemsperpage', $itemsperpage);
    xarModSetVar('modules', 'auto3ptupgrade', $auto3ptupgrade);

    $msg = xarML('Module configuration has been successfully updated.');
    xarTplSetMessage($msg,'status');
      xarLogMessage('MODULES: Module configuration was updated by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
     // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('modules', 'admin', 'modifyconfig'));

    // Return
    return true;
}

?>