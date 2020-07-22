<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function roles_adminapi_getmenulinks()
{
    $menulinks = array();
    $sessionguid = xarSessionGetVar('roles.groupuid')? xarSessionGetVar('roles.groupuid'):0;

    if (!xarVarFetch('pparentid',  'str:1:', $pparentid,  $sessionguid, XARVAR_NOT_REQUIRED)) return;

    if  (xarSecurityCheck('EditGroupRoles',0) || xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles','admin','showusers',array('pparentid'=>$pparentid)),
                             'title' => xarML('View and edit all groups/users on the system'),
                             'label' => xarML('Manage Groups and Users'),
                             'active' => array('showusers','modifyrole','deleterole','showprivileges','testprivileges'),
                             'activelabels' => array('',xarML('Modify role'),xarML('Delete role'),xarML('Show privileges'),xarML('Test privileges'))
                              );
    }

    if  (xarSecurityCheck('AddGroupRoles',0,'Group',$pparentid) || xarSecurityCheck('AddRole',0)) {

        $menulinks[] = Array('url'   => xarModURL('roles','admin','newrole',array('pparentid'=>$pparentid)),
                             'title' => xarML('Add a new user or group to the system'),
                             'label' => xarML('Add Group/User'),
                             'active'  => array('newrole')
                             );
    }

    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles','admin','createmail',array('pparentid'=>$pparentid)),
                              'title' => xarML('Manage system emails'),
                              'label' => xarML('Email Messaging'),
                              'active'=>array('createmail','modifyemail','modifynotice','asknotification'),
                              'activelabels' => array('',xarML('Edit mail templates'),xarML('Configure notifications'),xarML('Send Password Notification'))
                              );
    }


    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles','admin','purge'),
                              'title' => xarML('Undelete or permanently remove users/groups'),
                              'label' => xarML('Recall/Purge'),
                              'active' => array('purge')
                              );
    }
    /* Moved to Base Restrictons
    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles','admin','sitelock'),
                              'title' => xarML('Lock the site to all but selected users'),
                              'label' => xarML('Site Lock'),
                              'active'=>array('sitelock')

                              );
    }*/

    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles','admin','modifyconfig'),
                              'title' => xarML('Modify the roles module configuration'),
                              'label' => xarML('Modify Config'),
                              'active' => array('modifyconfig')
                              );
    }
    return $menulinks;
}

?>
