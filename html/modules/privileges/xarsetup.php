<?php
/**
 * Default setup for roles and privileges
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Purpose of file:  Default setup for roles and privileges
*/

function initializeSetup()
{
    /*********************************************************************
    * Enter some default groups and users
    *********************************************************************/
    xarMakeGroup('Everybody');
    xarMakeUser('Anonymous','anonymous','anonymous@xarigami.com');
    xarMakeUser('Admin','Admin','admin@xarigami.com','password');
    xarMakeGroup('Administrators');
    xarMakeGroup('Users');
    xarMakeGroup('Editors');
    xarMakeUser('Myself','myself','myself@xarigami.com','password');

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

    xarMakeRoleRoot('Everybody');
    xarMakeRoleMemberByName('Administrators','Everybody');
    xarMakeRoleMemberByName('Admin','Administrators');
    xarMakeRoleMemberByName('Users','Everybody');
    xarMakeRoleMemberByName('Editors','Everybody');
    xarMakeRoleMemberByName('Anonymous','Everybody');
    xarMakeRoleMemberByName('Myself','Everybody');

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/

    $systemPrefix = xarDB::$sysprefix;

    $blockGroupsTable    = $systemPrefix . '_block_groups';
    $blockTypesTable     = $systemPrefix . '_block_types';
    $blockInstancesTable = $systemPrefix . '_block_instances';
    $modulesTable        = $systemPrefix . '_modules';
    $rolesTable          = $systemPrefix . '_roles';
    $roleMembersTable    = $systemPrefix . '_rolemembers';
    $privilegesTable     = $systemPrefix . '_privileges';
    $privMembersTable    = $systemPrefix . '_privmembers';
    $themesTable         = $systemPrefix . '_themes';

    /*-------------------------------- Blocks Module  see blocks module for instances ----*/

   //--------------------------------- Roles Module

    $query = "SELECT DISTINCT xar_uid, xar_uname FROM $rolesTable";
    $instances = array(array('header' => 'Users and Groups',
                             'query' => $query,
                             'limit' => 60));
    xarDefineInstance('roles','Roles',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','User and Group Instances of the roles module, including multilevel nesting');

    $instances = array(array('header' => 'Parent:',
                             'query' => $query,
                             'limit' => 60),
                       array('header' => 'Child:',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');
    //setup the Roles Group instances
    $query = "SELECT DISTINCT xar_uid, xar_name FROM $rolesTable WHERE xar_type =1";
    $instances = array(array('header' => 'Groups',
                             'query' => $query,
                             'limit' => 60));
            xarDefineInstance('roles','Group',$instances,0,$roleMembersTable,'xar_uid','xar_parentid',xarML('Group instance of the roles module, including multilevel nesting'));

    //setup the Roles Mail instances
    $instances = array(array('header' => 'Mail Group',
                             'query' => $query,
                             'limit' => 60));
            xarDefineInstance('roles','Mail',$instances,0,$roleMembersTable,'xar_uid','xar_parentid',xarML('Group Mail instance of the roles module'));

   // ----------------------------- Privileges Module
    $query = "SELECT DISTINCT xar_name FROM $privilegesTable";
    $instances = array(array('header' => 'Privileges',
                             'query' => $query,
                             'limit' => 60));
    xarDefineInstance('privileges','Privileges',$instances,0,$privMembersTable,'xar_pid','xar_parentid','Instances of the privileges module, including multilevel nesting');


   /* ------------------------------- Themes Module - move to themes module install */
    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * xarregisterMask(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('AdminAll','All','All','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewBase','All','base','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadBase','All','base','All','All','ACCESS_READ');
    xarRegisterMask('AdminBase','All','base','All','All','ACCESS_ADMIN');
    xarRegisterMask('ModerateBase','All','base','All','All','ACCESS_MODERATE');
    /* This AdminPanel mask is added to replace the adminpanel module equivalent
     *   - since adminpanel module is removed as of 1.1.0
     * At some stage we should remove this but practice has been to use this mask in xarSecurityCheck
     * frequently in module code and templates - left here for now for ease in backward compatibiilty
     */
    xarRegisterMask('AdminPanel','All','base','All','All','ACCESS_ADMIN');

    xarRegisterMask('AdminInstaller','All','installer','All','All','ACCESS_ADMIN');

    /* legacy - blocks security moved to blockds module */
    xarRegisterMask('ViewRolesBlocks','All','blocks','Block','roles:All:All','ACCESS_OVERVIEW');

    xarRegisterMask('ViewRoles', 'All','roles','Roles','All','ACCESS_OVERVIEW',xarML('View limited user information'));
    xarRegisterMask('ReadRole',  'All','roles','Roles','All','ACCESS_READ',xarML('Read all profile information on users'));
    xarRegisterMask('EditRole',  'All','roles','Roles','All','ACCESS_EDIT',xarML('Edit user and group roles'));
    xarRegisterMask('ModerateRole', 'All','roles','Roles','All','ACCESS_MODERATE',xarML('Add or Remove users in a group, edit groups and users'));
    xarRegisterMask('AddRole',   'All','roles','Roles','All','ACCESS_ADD',xarML('Add user or group roles'));
    xarRegisterMask('DeleteRole','All','roles','Roles','All','ACCESS_DELETE',xarML('Delete user or group roles'));
    xarRegisterMask('AdminRole', 'All','roles','Roles','All','ACCESS_ADMIN',xarML('Admin user or group roles'));
    xarRegisterMask('MailRoles', 'All','roles','Mail',' All','ACCESS_ADMIN',xarML('Mail users in a give group role'));
    xarRegisterMask('AttachRole','All','roles','Relation','All','ACCESS_ADD',xarML('Assign a user to a group'));
    xarRegisterMask('RemoveRole','All','roles','Relation','All','ACCESS_DELETE',xarML('Remove a user from a group'));
    //
    xarRegisterMask('ViewGroupRoles', 'All','roles','Group','All','ACCESS_OVERVIEW',xarML('View limited information for users in a group'));
    xarRegisterMask('ReadGroupRoles',  'All','roles','Group','All','ACCESS_READ',xarML('Read all profile information on all users in a group'));
    xarRegisterMask('SubmitGroupRoles',  'All','roles','Group','All','ACCESS_COMMENT',xarML('Submit a user to a group'));
    xarRegisterMask('ModerateGroupRoles','All','roles','Group','All','ACCESS_MODERATE',xarML('Add or Remove users in a group, edit these users'));
    xarRegisterMask('EditGroupRoles',  'All','roles','Group','All','ACCESS_EDIT',xarML('Edit users in a group'));
    xarRegisterMask('AddGroupRoles',   'All','roles','Group','All','ACCESS_ADD',xarML('Add users in a group'));
    xarRegisterMask('DeleteGroupRoles','All','roles','Group','All','ACCESS_DELETE',xarML('Delete users in a group'));
    xarRegisterMask('AdminGroupRoles', 'All','roles','Group','All','ACCESS_ADMIN',xarML('Admin users in a group'));

    xarRegisterMask('AssignPrivilege','All','privileges','All','All','ACCESS_ADD');
    xarRegisterMask('DeassignPrivilege','All','privileges','All','All','ACCESS_DELETE');
    xarRegisterMask('ViewPrivileges','All','privileges','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadPrivilege','All','privileges','All','All','ACCESS_READ');
    xarRegisterMask('EditPrivilege','All','privileges','All','All','ACCESS_EDIT');
    xarRegisterMask('AddPrivilege','All','privileges','All','All','ACCESS_ADD');
    xarRegisterMask('DeletePrivilege','All','privileges','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewRealm','All','privileges','Realm','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadRealm','All','privileges','Realm','All','ACCESS_READ');
    xarRegisterMask('EditRealm','All','privileges','Realm','All','ACCESS_EDIT');
    xarRegisterMask('AddRealm','All','privileges','Realm','All','ACCESS_ADD');
    xarRegisterMask('DeleteRealm','All','privileges','Realm','All','ACCESS_DELETE');
    xarRegisterMask('AdminRealm','All','privileges','Realm','All','ACCESS_ADMIN');

    xarRegisterMask('EditModules','All','modules','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminModules','All','modules','All','All','ACCESS_ADMIN');

    // save the uids of the default roles for later
    $role = xarFindRole('Everybody');
    xarModSetVar('roles', 'everybody', $role->getID());
    $role = xarFindRole('Anonymous');
    xarConfigSetVar('Site.User.AnonymousUID', $role->getID());
    // set the current session information to the right anonymous uid
    xarSession_setUserInfo($role->getID(), 0);
    $role = xarFindRole('Admin');
    if (!isset($role)) {
        $role=xarUFindRole('Admin');
    }
    xarModSetVar('roles', 'admin', $role->getID());
    xarModSetVar('roles', 'defaultgroup', 'Users');
    $arole = xarFindRole('Administrators');
    if (!isset($arole)) {
        $arole=xarUFindRole('Administrators');
    }
    xarModSetVar('privileges', 'debuggroup', $arole->getID());
    // Initialisation successful
    return true;
}

?>