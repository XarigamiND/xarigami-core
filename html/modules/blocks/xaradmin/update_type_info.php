<?php
/**
 * Regsiter a new block type
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Update Block Type
 */
function blocks_admin_update_type_info()
{
     // Get parameters
    if (!xarVarFetch('modulename', 'str:1:', $modulename, 'base', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('blocktype', 'str:1:', $blocktype, '', XARVAR_NOT_REQUIRED)) {return;}

   // Security Check
    // jojo - let's use the Block instances - as it relates to blocks. 
    if (!xarSecurityCheck('EditBlock',0,'Block',"{$modulename}:{$blocktype}:All")) return xarResponseForbidden();


    xarMod::apiFunc('blocks', 'admin', 'update_type_info',
        array('module' => $modulename, 'type' => $blocktype)
    );

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_types'));
}

?>