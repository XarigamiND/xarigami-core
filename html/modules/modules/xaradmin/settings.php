<?php
/**
 * List modules and current settings
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * List modules and current settings
 * @param several params from the associated form in template
 *
 * @author Xarigami Development Team
 */
function modules_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules',0)) return xarResponseForbidden();

    if (!xarVarFetch('hidecore', 'str:1:', $hidecore, '0', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selfilter', 'str:1:', $selfilter, 'XARMOD_STATE_ANY', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selsort', 'str:1:', $selsort, 'namedesc', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('regen', 'str:1:', $regen, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('useicons',  'checkbox', $useicons, FALSE, XARVAR_NOT_REQUIRED)) return;

    xarModSetUserVar('modules', 'hidecore', $hidecore);
    xarModSetUserVar('modules', 'selstyle', $selstyle);
    xarModSetUserVar('modules', 'selfilter', $selfilter);
    xarModSetUserVar('modules', 'selsort', $selsort);
    xarModSetUserVar('modules', 'useicons', $useicons);

    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('regen' => $regen)));
}

?>