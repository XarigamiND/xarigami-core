<?php
/**
 * Add a group
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
 * addGroup - add a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gname'] group name to add
 * @return true on success, false if group exists
 */
function roles_adminapi_addgroup($args)
{
    extract($args);

    if(!isset($gname)) {
        $msg = xarML('Wrong arguments to groups_adminapi_addgroup.');
        throw new BadParameterException(null,$msg);
    }

// Security Check
    if(!xarSecurityCheck('AddRole',0)) return xarResponseForbidden();

    return xarMakeGroup($gname);
}

?>