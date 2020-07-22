<?php
/**
 * Generate all groups listing
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
function roles_adminapi_getallgroups()
{
// Security Check
    if(!xarSecurityCheck('ViewRoles',0)) return xarResponseForbidden();

    $groups = xarMod::apiFunc('roles','user','getallgroups');
    return $groups;
}


?>