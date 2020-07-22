<?php
/**
 * Delete a group & info
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
 * deletegroup - delete a group & info
 * @param $args['uid']
 * @return true on success, false otherwise
 */
function roles_adminapi_deletegroup($args)
{
    extract($args);

    if(!isset($uid)) {
        $msg = xarML('Wrong arguments to groups_adminapi_deletegroup');
        throw new BadParameterException(null,$msg);
    }

// Security Check
    if(!xarSecurityCheck('EditRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

   // Prohibit removal of any groups the system needs
   $defaultgroup=xarMod::apiFunc('roles', 'user', 'getdefaulgroup');

    if ($role->getName() == $defaultgroup) {
        $msg = xarML('The group #(1) is the default group for new users. If you want to remove it change the appropriate configuration setting first.', $role->getName());
        return xarTplModule('roles','user','errors',array('errortype' => 'remove_defaultusergroup','var1'=>$role->getName()));
    }
// OK, go ahead
    return $role->remove();
}

?>