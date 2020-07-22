<?php
/**
 * Get ancestors of a role
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
 * getancestors - get ancestors of a role
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] role id
 * @return $ancestors array containing name, uid
 */
function roles_userapi_getancestors($args)
{
    extract($args);

    if(!isset($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_getancestors.');
          throw new BadParameterException(null,$msg);      
    }

    if(!xarSecurityCheck('ViewRoles')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $ancestors = $role->getAncestors();

    $flatancestors = array();
    foreach($ancestors as $ancestor) {
        $flatancestors[] = array('uid' => $ancestor->getID(),
                        'name' => $ancestor->getName()
                        );
    }
    return $flatancestors;
}
?>