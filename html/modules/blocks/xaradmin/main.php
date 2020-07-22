<?php
/**
 * Block Functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Blocks Functions
 */
function blocks_admin_main()
{

    if(!xarSecurityCheck('EditBlock',0)) return xarResponseForbidden();

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));

    // success
    return true;
}

?>