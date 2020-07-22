<?php
/**
 * Check privilege
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param   string privilege name of the privilege to check for
 * @param   string role ID uid. Defaults to current user OPTIONAL
 * @return  bool
 */
function roles_userapi_checkprivilege($args)
{
    extract($args);

    if(!isset($privilege)) {
        $msg = xarML('roles_userapi_checkprivilege');
        throw new BadParameterException(null,$msg);      
    }

    if (empty($uid)) $uid = xarSessionGetVar('uid');
    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    return $role->hasPrivilege($privilege);
}
?>