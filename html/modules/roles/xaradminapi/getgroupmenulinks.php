<?php
/**
 * Utility function pass individual menu items to the main menu
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
 * utility function pass individual menu items to the main menu
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function roles_adminapi_getgroupmenulinks()
{

// Security Check
    if (xarSecurityCheck('AddRole',0)) {

        $menulinks[] = Array('url'   => xarModURL('roles',
                                                   'admin',
                                                   'newgroup'),
                              'title' => xarML('Add a new user group'),
                              'label' => xarML('Add'));
    }

// Security Check
    if (xarSecurityCheck('EditRole',0)) {

        $menulinks[] = Array('url'   => xarModURL('roles',
                                                   'admin',
                                                   'viewallgroups'),
                              'title' => xarML('View and edit user groups'),
                              'label' => xarML('View'));
    }


    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}
?>