<?php
/**
 * Modify the configuration parameters
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
 * standard function to modify the configuration parameters
 *
 * @access  public
 * @param   no parameters
 * @return  the data for template
 * @todo    remove at some stage if not used. Created for the move of mod overview var
 *          and never in a release, but this var is not used now due to help system.
*/
function modules_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules',0)) return xarResponseForbidden();

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('modules','admin','getmenulinks');
    // Disable the overview pages?
    //$data['disableoverview'] = xarModGetVar('modules', 'disableoverview');
    $data['itemsperpage'] = xarModGetVar('modules', 'itemsperpage');
    $data['auto3ptupgrade'] = xarModGetVar('modules', 'auto3ptupgrade');
    // everything else happens in Template for now
    return $data;
}
?>
