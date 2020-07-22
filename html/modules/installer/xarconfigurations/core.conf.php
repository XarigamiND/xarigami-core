<?php
/**
 * Core configuration
 *
 * @package Installer
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

$configuration_name = xarML('Core Xarigami install - minimal modules needed to run Xarigami');

function installer_core_moduleoptions()
{
    return array();
}

function installer_core_privilegeoptions()
{
    return array(
        array(
            'item' => 'p1',
            'option' => 'true',
            'comment' => xarML('Registered users have read access to all modules of the site.')
        ),
        array(
            'item' => 'p2',
            'option' => 'true',
            'comment' => xarML('Unregistered users have read access to the non-core modules of the site.
                               If this option is not chosen unregistered users only see the front page and Login.')
        ),
    );
}

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_core_configuration_load($args)
{
// load the privileges chosen

    if (!in_array('p1',$args) || !in_array('p2',$args)) {
        //create the casual access privileges as one of them is going to use it
        installer_core_casualaccess($args);
    }
    if (in_array('p1',$args) && in_array('p2',$args)) {
        //Registered and anon get read access to everything
        installer_core_readaccess($args);
        xarAssignPrivilege('UserReadAccess','Everybody');
    } elseif (in_array('p1',$args) && !in_array('p2',$args)) {
        //Registered users have  access to everything, anon has restricted access
         installer_core_readaccess($args);
        xarAssignPrivilege('UserReadAccess','Users');
        xarAssignPrivilege('VisitorCasualAccess','Everybody');
    } elseif (!in_array('p1',$args) &&in_array('p2',$args)) {
        //Registered users have  restricted access, anon users have all ready - is this an option???
    } else {
      //everyone has restricted access
      xarAssignPrivilege('VisitorCasualAccess','Everybody');
    }
    //Editor setup
    installer_core_editoraccess($args);
    xarAssignPrivilege('SiteEditorAccess','Editors');

    return true;
}

function installer_core_casualaccess($args)
{
    xarRegisterPrivilege('VisitorCasualAccess','All','blocks','Block','themes:All:All','ACCESS_OVERVIEW','Minimal access to a site');
    xarRegisterPrivilege('ViewLogin','All','blocks','Block','authsystem:login:All','ACCESS_OVERVIEW','View the Login block');
    xarRegisterPrivilege('ViewBaseBlocks','All','blocks','Block','base:All:All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarRegisterPrivilege('ViewBase','All','base','All','All','ACCESS_OVERVIEW','View Base module');
    xarRegisterPrivilege('ReadBlockGroups','All','blocks','Blockgroup','All','ACCESS_READ','View Block Groups');
    xarRegisterPrivilege('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
    xarMakePrivilegeRoot('VisitorCasualAccess');
    xarMakePrivilegeRoot('ViewLogin');
    xarMakePrivilegeRoot('ViewBaseBlocks');
    xarMakePrivilegeRoot('ViewLoginItems');
    xarMakePrivilegeMember('ViewLogin','VisitorCasualAccess');
    xarMakePrivilegeMember('ViewBase','VisitorCasualAccess');
    xarMakePrivilegeMember('ViewBaseBlocks','VisitorCasualAccess');
    xarMakePrivilegeMember('ReadBlockGroups','VisitorCasualAccess');
    xarMakePrivilegeMember('ViewLoginItems','VisitorCasualAccess');
}
function installer_core_editoraccess($args)
{
        $administratorsrole = xarFindRole('Administrators');
        xarRegisterPrivilege('SiteEditorAccess','All','All','All','All','ACCESS_MODERATE','Moderator access to all modules');
        xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny Access to privileges');
        xarRegisterPrivilege('DenyAdminRole','All','roles','Group',$administratorsrole->uid,'ACCESS_NONE','Deny Access to Admin group');
        xarMakePrivilegeRoot('SiteEditorAccess');
        xarMakePrivilegeRoot('DenyAdminRole');
        xarMakePrivilegeRoot('DenyPrivileges');
        xarMakePrivilegeMember('DenyAdminRole','SiteEditorAccess');
        xarMakePrivilegeMember('DenyPrivileges','SiteEditorAccess');
}
function installer_core_readaccess($args)
{
        xarRegisterPrivilege('UserReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
        xarMakePrivilegeRoot('UserReadAccess');
}
?>