<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function privileges_adminapi_getmenulinks()
{
    $menulinks = array();
    if (xarSecurityCheck('EditPrivilege',0)) {

        $menulinks[] = Array('url'   => xarModURL('privileges','admin','viewprivileges',array('phase' => 'active')),
                              'title' => xarML('View all privileges on the system'),
                              'label' => xarML('View Privileges'),
                              'active' => array('viewprivileges','modifyprivilege','deleteprivilege','displayprivilege','viewroles'),
                              'activelabels' => array('',xarML('Modify privilege'),xarML('Delete privilege'),xarML('Display privilege'),xarML('Display role')
                                    )
                              );
    }

    if (xarSecurityCheck('AssignPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges','admin','newprivilege'),
                              'title' => xarML('Add a new privilege to the system'),
                              'label' => xarML('Add Privilege'),
                              'active' => array('newprivilege')
                              );
    }

    if (xarSecurityCheck('EditRealm',0) && xarModGetVar('privileges','showrealms')) { 
        $menulinks[] = Array('url'   => xarModURL('privileges','admin','viewrealms'),
                              'title' => xarML('Add, change or delete realms'),
                              'label' => xarML('Manage Realms'),
                              'active'=> array('viewrealms','newrealm','modifyrealm','deleterealm'),
                              'activelabels' => array('',xarML('Add realm'),xarML('Modify realm'),xarML('Delete realm'))
                              );
    }

    if (xarSecurityCheck('AdminPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges', 'admin','modifyconfig'),
                              'title' => xarML('Modify the privileges module configuration'),
                              'label' => xarML('Modify Config'),
                              'active' => array('modifyconfig')
                              );
    }
    return $menulinks;
}

?>