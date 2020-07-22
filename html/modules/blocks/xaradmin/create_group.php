<?php
/**
 * Block group management - create a new block group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */

/**
 * create a new block group
 *
 * @param string group_name Group name to create
 * @param string group_template Name of the template to use for the group
 * @author Jim McDonald, Paul Rosania
 * @return  bool true on success
 */
function blocks_admin_create_group()
{
    // Get parameters
    if (!xarVarFetch('group_name', 'pre:lower:ftoken:passthru:str:1:', $name)) {return;}
    if (!xarVarFetch('group_template', 'str:1:', $template, '', XARVAR_NOT_REQUIRED)) {return;}

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey('blocks')) {return;}

   // Security Check
   if(!xarSecurityCheck('AddBlockGroup', 0, 'Blockgroup','All')) {return xarResponseForbidden();}
    // Check the group name has not already been used.
    $checkname = xarModAPIfunc('blocks', 'user', 'groupgetinfo', array('name' => $name));
    if (!empty($checkname)) {
        $msg = xarML('Block group name "#(1)" already exists', $name);
        throw new BadParameterException(null,$msg);
    }
    
    // Pass to API
    if (!xarMod::apiFunc(
        'blocks', 'admin', 'create_group', array('name' => $name, 'template' => $template))
    ) {return;}

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>