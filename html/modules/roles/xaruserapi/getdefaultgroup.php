<?php
/**
 * Get the group that users are members of by default
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * getdefaultgroup - get the group that users are members of by default
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return string groupname
 */
function roles_userapi_getdefaultgroup()
{
    $defaultgroup = xarModGetVar('roles', 'defaultgroup');
    if (!isset($defaultgroup) || empty($defaultrole)) {
        $defaultgroup = 'Users';
    }
    return $defaultgroup;
}

?>