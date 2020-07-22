<?php
/**
 * List modules and current settings
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * List modules and current settings
 * @param several params from the associated form in template
 */
function blocks_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('EditBlock',0)) return xarResponseForbidden();

    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('filter', 'str', $filter, "", XARVAR_NOT_REQUIRED)) {return;}

    xarModSetVar('blocks', 'selstyle', $selstyle);

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances',array('filter' => $filter)));

    return true;
}

?>