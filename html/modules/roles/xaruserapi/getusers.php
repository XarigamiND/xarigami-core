<?php
/**
 * View users in group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami
 */
/**
 * getUsers - view users in group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] group id
 * @return $users array containing uname, uid
 */
function roles_userapi_getUsers($args)
{
    extract($args);

    if(!isset($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_getusers.');
        throw new BadParameterException(null,$msg);
    }

// Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $users = $role->getUsers();

    $flatusers = array();
    foreach($users as $user) {
        $flatusers[] = array('uid' => $user->getID(),
                        'uname' => $user->getUser()
                        );
    }

    return $flatusers;
}

?>