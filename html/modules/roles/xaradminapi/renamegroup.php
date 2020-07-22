<?php
/**
 * Rename a group
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
/**
 * renamegroup - rename a group
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['pid'] group id
 * @param $args['gname'] group name
 * @return true on success, false on failure.
 */
function roles_adminapi_renamegroup($args)
{
    extract($args);

    if((!isset($pid)) || (!isset($gname))) {
        $msg = xarML('groups_adminapi_renamegroup');
        throw new EmptyParameterException(null,$msg);
    }

// Security Check
    if(!xarSecurityCheck('EditRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    $role->setName($gname);

    return $role->update();
}

?>