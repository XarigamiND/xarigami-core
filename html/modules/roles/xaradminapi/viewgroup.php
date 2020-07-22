<?php
/**
 * View users in a group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * viewgroup - view users in group
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['pid'] group id
 * @return $users array containing uname, pid
 */
function roles_adminapi_viewgroup($args)
{
    return xarMod::apiFunc('roles','user','getusers',$args);

}

?>
