<?php
/**
 * Add a user to a group
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
 * insertuser - add a user to a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] user id
 * @param $args['gid'] group id
 * @return true on succes, false on failure
 */
function roles_adminapi_addmember($args)
{
    return xarMod::apiFunc('roles','user','addmember',$args);
}

?>