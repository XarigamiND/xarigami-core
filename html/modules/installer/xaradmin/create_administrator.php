<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Create default administrator and default blocks
 *
 * @access public
 * @param create
 * @return bool
 * @todo make confirm password work
 * @todo remove URL field from users table
 * @todo normalize user's table
 */
function installer_admin_create_administrator()
{
    //jojo - replace this check with a better one once we finish new installer
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarFetch('invalid','str::',$invalid, '', XARVAR_NOT_REQUIRED);
    xarCoreCache::setCached('installer','installing', true);

    xarTplSetThemeName('installtheme');

    if (!empty($invalid)) $invalid = unserialize($invalid);

    $data['invalid']['password'] = isset($invalid['password']) ?$invalid['password'] : array();
    $data['invalid']['username'] = isset($invalid['username']) ?$invalid['username'] : array();

    $data['language'] = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Create Administrator');

    //sys::import('modules.roles.xarclass.xarroles');
    $role = xarUFindRole('Admin');

    if (!xarVarFetch('create', 'isset', $create, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!$create) {
        // create a role from the data

        // assemble the template data
        $data['install_admin_username'] = $role->getUser();
        $data['install_admin_name']     = $role->getName();
        $data['install_admin_email']    = $role->getEmail();
        return $data;
    }

    if (!xarVarFetch('install_admin_username','str:1:100',$userName, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_admin_name','str:1:100',$name)) return;
    if (!xarVarFetch('install_admin_password','str:5:100',$pass, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_admin_password1','str:5:100',$pass1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_admin_email','str:1:100',$email, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_language','str:1:100',$install_language)) return;

    xarModSetVar('mail', 'adminname', $name);
    xarModSetVar('mail', 'adminmail', $email);
    xarModSetVar('themes', 'SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $name);

    $invalid = array();
    $pass = trim($pass);
    if (strlen($pass) <5) {
     $invalid['password'][] = xarML('Password must be a minimum of five (5) alpha-numeric characters');
    }
    if (empty($pass)) {
      $invalid['password'][] = xarML('You must supply a password');
    }
    if ($pass != $pass1) {
        $invalid['password'][] = xarML('The passwords do not match');
    }
    if (!preg_match("/([a-zA-Z]*)[0-9]+([a-zA-Z]*)/",$pass)) {

        $invalid['password'][] = xarML('Please enter a password with at least five (5) alpha-numeric characters, and include at least one number.');
    }
    $userName = trim($userName);
    if (strlen($userName)<2) {
         $invalid['username'][] = xarML('You must provide a preferred username to continue of at least 3 characters.');
    }
    // check for spaces in the username
    if (preg_match("/[[:space:]]/",$userName)) {
        $invalid['username'][] =  xarML('There is a space in the username.');
    }
    // check the length of the username
    if (strlen($userName) > 255) {
         $invalid['username'][] =  xarML('Your username is too long.');
    }
    // check for spaces in the username (again ?)
    if (strrpos($userName,' ') > 0) {
         $invalid['username'][] = xarML('There is a space in your username.');
    }
    // check for spaces in the username (again ?)
    if (strrpos($userName,':') > 0) {
         $invalid['username'][] = xarML('Colons are not permitted in usernames.');
    }
    if (count($invalid)>0) {
        $invalid = serialize($invalid);
       xarResponseRedirect(xarModURL('installer', 'admin', 'create_administrator',array('install_language' => $install_language, 'invalid'=>$invalid)));

    }

    // assemble the args into an array for the role constructor
    $pargs = array('uid'   => $role->getID(),
                   'name'  => $name,
                   'type'  => 0,
                   'uname' => $userName,
                   'email' => $email,
                   'pass'  => $pass,
                   'state' => 3);

    xarModSetVar('roles', 'lastuser', $userName);
    xarModSetVar('roles', 'adminpass', $pass);

    // create a role from the data
    $role = new xarRole($pargs);

    //Try to update the role to the repository and bail if an error was thrown
    $modifiedrole = $role->update();
    if (!$modifiedrole) {return;}

    // Register Block types from modules installed before block apis (base)
    $blocks = array('adminmenu','waitingcontent','finclude','menu','content');

    foreach ($blocks as $block) {

        if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type', array('modName'  => 'base', 'blockType'=> $block))) return;
    }

    if (xarCoreCache::isCached('Mod.BaseInfos', 'blocks')) xarCoreCache::delCached('Mod.BaseInfos', 'blocks');

    // Create default block groups/instances
    //                            name        template
    $default_blockgroups = array ('left'   => '',
                                  'right'  => 'right',
                                  'header' => 'header',
                                  'admin'  => '',
                                  'center' => 'center',
                                  'topnav' => 'topnav'
                                  );

    foreach ($default_blockgroups as $name => $template) {
        if(!xarMod::apiFunc('blocks','user','groupgetinfo', array('name' => $name))) {
            // Not there yet
            if(!xarMod::apiFunc('blocks','admin','create_group', array('name' => $name, 'template' => $template))) return;
        }
    }

    // Load up database
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = ?";

    $result = $dbconn->Execute($query,array('left'));
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        throw new BadParameterException(null,$msg);
    }

    list ($leftBlockGroup) = $result->fields;
    /* We don't need this for adminpanels now - done in Base module */
        $adminBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'base',
                                          'type'    => 'adminmenu'));

    if (empty($adminBlockType)) {
        throw new EmptyParameterException($adminBlockType,'Variable is empty "#(1)" ');
    }

    $adminBlockTypeId = $adminBlockType['tid'];

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'adminpanel'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Admin',
                                 'name'     => 'adminpanel',
                                 'type'     => $adminBlockTypeId,
                                 'groups'   => array(array('gid'      => $leftBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    =>  2))) {
            return;
        }
    }

    $now = time();

    $varshtml['content_text'] = '<p><strong>'.xarML('Please remember to delete install.php and upgrade.php from your webroot.
        Your site is insecure until they are removed!').'</strong><br />Delete this block from Blocks Admin.</p>';
    $varshtml['expire'] = $now + 24000;
    $varshtml['start_date'] = $now;
    $varshtml['end_date'] = '';
    $msg = serialize($varshtml);
    $varshtml['content_type']='html';
    $varshtml['custom_format']='';
    $htmlBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                 array('module'  => 'base',
                                       'type'    => 'content'));

    if (empty($htmlBlockType)) {
        throw new EmptyParameterException($htmlBlockType,'Variable is empty "#(1)" ');
    }
    $htmlBlockTypeId = $htmlBlockType['tid'];

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'reminder'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'SECURITY WARNING',
                                 'name'     => 'reminder',
                                 'content'  => $varshtml,
                                 'type'     => $htmlBlockTypeId,
                                 'groups'   => array(array('gid'      => $leftBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }

//Now add configuration here and create it

 //Get all modules in the filesystem
    $fileModules = xarMod::apiFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Make sure all the core modules are here
    // Remove them from the list if name and regid coincide
    $awol = array();
    include sys::code().'modules/installer/xarconfigurations/coremoduleslist.php';
    foreach ($coremodules as $coremodule) {
        if (in_array($coremodule['name'],array_keys($fileModules))) {
            if ($coremodule['regid'] == $fileModules[$coremodule['name']]['regid'])
                unset($fileModules[$coremodule['name']]);
        }
        else $awol[] = $coremodule['name'];
    }

    if (count($awol) != 0) {
        $msg = xarML("Xarigami cannot install because the following core modules are missing or corrupted: #(1)",implode(', ', $awol));
        throw new Exception($msg);
    }
    $basedir = realpath(sys::code().'modules/installer/xarconfigurations');

    xarModSetVar('installer','modulelist',serialize($fileModules));

    $configuration = sys::code().'modules/installer/xarconfigurations/core.conf.php';
    include $configuration;
    $fileModules = unserialize(xarModGetVar('installer','modulelist'));
    $func = "installer_" . basename(strval($configuration),'.conf.php') . "_moduleoptions";
    $modules = $func();
    $availablemodules = $awolmodules = $installedmodules = array();
    foreach ($modules as $module) {
        if (in_array($module['name'],array_keys($fileModules))) {
            if ($module['regid'] == $fileModules[$module['name']]['regid']) {
                if (xarMod::getState($module['regid']) == XARMOD_STATE_ACTIVE ||
                xarMod::getState($module['regid']) == XARMOD_STATE_INACTIVE) {
                    $installedmodules[] = ucfirst($module['name']);
                }
                else {
                    $availablemodules[] = $module;
                }
                unset($fileModules[$module['name']]);
            }
        }
        else $awolmodules[] = ucfirst($module['name']);
    }
    $options2 = $options3 = array();
    foreach ($availablemodules as $availablemodule) {
        $options2[] = array(
                   'item' => $availablemodule['regid'],
                   'option' => 'true',
                   'comment' => xarML('Install the #(1) module.',ucfirst($availablemodule['name']))
                   );
    }

    /*********************************************************************
    * Empty the privilege tables
    *********************************************************************/
    $dbconn = xarDB::$dbconn;
    $sitePrefix = xarDB::$prefix;
    $query = "DELETE FROM " . $sitePrefix . '_privileges';
    if (!$dbconn->Execute($query)) return;
    $query = "DELETE FROM " . $sitePrefix . '_privmembers';
    if (!$dbconn->Execute($query)) return;
    $query = "DELETE FROM " . $sitePrefix . '_security_acl';
    if (!$dbconn->Execute($query)) return;

    /*********************************************************************
    * Enter some default privileges
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/
    $anonid = xarConfigGetVar('Site.User.AnonymousUID');
    $adminid = xarModGetVar('Roles','admin');
    $everybodyrole = xarFindRole('Everybody');
    $myselfrole = xarFindRole('Myself');
    $administratorsrole = xarFindRole('Administrators');
    xarRegisterPrivilege('Administration','All','All','All','All','ACCESS_ADMIN',xarML('Admin access to all modules'));
    xarRegisterPrivilege('AdminLock','All','empty','All','All','ACCESS_EDIT',xarML('A container privilege for denying delete access to certain roles'));
    xarRegisterPrivilege('GeneralLock','All','empty','All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));

    xarRegisterPrivilege('LockMyself','All','roles','Roles',$myselfrole->uid,'ACCESS_NONE',xarML('Deny access to Myself role'));
    xarRegisterPrivilege('LockEverybody','All','roles','Roles',$everybodyrole->uid,'ACCESS_NONE',xarML('Deny access to Everybody role'));
    xarRegisterPrivilege('LockAnonymous','All','roles','Roles',$anonid,'ACCESS_NONE',xarML('Deny access to Anonymous role'));
    xarRegisterPrivilege('LockAdministrators','All','roles','Roles',$administratorsrole->uid,'ACCESS_NONE',xarML('Deny access to Administrators role'));
    xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
    xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));

    xarRegisterPrivilege('AdminMyself','All','roles','Roles',$myselfrole->uid,'ACCESS_EDIT',xarML('Edit access to Myself role'));
    xarRegisterPrivilege('AdminEverybody','All','roles','Roles',$everybodyrole->uid,'ACCESS_EDIT',xarML('Edit access to Everybody role'));
    xarRegisterPrivilege('AdminAnonymous','All','roles','Roles',$anonid,'ACCESS_EDIT',xarML('Edit access to Anonymous role'));
    xarRegisterPrivilege('AdminAdministrators','All','roles','Roles',$administratorsrole->uid,'ACCESS_EDIT',xarML('Edit access to Administrators role'));
    xarRegisterPrivilege('LockAdminLock','All','privileges','Privileges','AdminLock','ACCESS_NONE',xarML('Deny access to AdminLock privilege'));


    xarRegisterPrivilege('DenyReminder','All','blocks','Block',"base:content:reminder",'ACCESS_NONE',xarML('A privilege to deny viewing of reminder block'));
    xarRegisterPrivilege('DenyAdminMenu','All','blocks','Block','base:adminmenu:all','ACCESS_NONE','Deny access to the base admin menu');
    /*********************************************************************
    * Arrange the  privileges in a hierarchy
    * Format is
    * makeEntry(Privilege)
    * makeMember(Child,Parent)
    *********************************************************************/

    xarMakePrivilegeRoot('Administration');
    xarMakePrivilegeRoot('GeneralLock');
    xarMakePrivilegeRoot('AdminLock');
    xarMakePrivilegeMember('LockMyself','GeneralLock');
    xarMakePrivilegeMember('LockEverybody','GeneralLock');
    xarMakePrivilegeMember('LockAnonymous','GeneralLock');
    xarMakePrivilegeMember('LockAdministrators','GeneralLock');
    xarMakePrivilegeMember('LockAdministration','GeneralLock');
    xarMakePrivilegeMember('LockGeneralLock','GeneralLock');

     xarMakePrivilegeMember('AdminMyself','AdminLock');
    xarMakePrivilegeMember('AdminEverybody','AdminLock');
    xarMakePrivilegeMember('AdminAnonymous','AdminLock');
    xarMakePrivilegeMember('AdminAdministrators','AdminLock');
    xarMakePrivilegeMember('LockAdministration','AdminLock');
    xarMakePrivilegeMember('LockAdminLock','AdminLock');
    xarMakePrivilegeMember('LockGeneralLock','AdminLock');
    xarMakePrivilegeMember('DenyReminder','GeneralLock');
    xarMakePrivilegeMember('DenyAdminMenu','GeneralLock');
    /*********************************************************************
    * Assign the default privileges to groups/users
    * Format is
    * assign(Privilege,Role)
    *********************************************************************/

    xarAssignPrivilege('Administration','Administrators');
    xarAssignPrivilege('GeneralLock','Everybody');
    xarAssignPrivilege('AdminLock','Administrators');
    xarAssignPrivilege('GeneralLock','Users');
    xarAssignPrivilege('GeneralLock','Editors');
    $GLOBALS['xarMod_noCacheState'] = true;
    xarMod::apiFunc('modules','admin','regenerate');

    // load the modules from the configuration
        foreach ($options2 as $module) {
            if(in_array($module['item'],$chosen)) {
               $dependents = xarMod::apiFunc('modules','admin','getalldependencies',array('regid'=>$module['item']));
               if (count($dependents['unsatisfiable']) > 0) {
                    $msg = xarML("Cannot load because of unsatisfied dependencies. One or more of the following modules is missing: ");
                    foreach ($dependents['unsatisfiable'] as $dependent) {
                        $modname = isset($dependent['name']) ? $dependent['name'] : "Unknown";
                        $modid = isset($dependent['id']) ? $dependent['id'] : $dependent;
                        $msg .= $modname . " (ID: " . $modid . "), ";
                    }
                    $msg = trim($msg,', ') . ". " . xarML("Please check the listings at xarigami.com to identify any modules flagged as 'Unknown'.");
                    $msg .= " " . xarML('Add the missing module(s) to the modules directory and run the installer again.');
                    throw new ModuleNotFoundException($msg);
                    return;
               }
               xarMod::apiFunc('modules','admin','installwithdependencies',array('regid'=>$module['item']));
//                    xarMod::apiFunc('modules','admin','activate',array('regid'=>$module['item']));
            }
        }
        $chosen = array('p1','p2');
    $func = "installer_" . basename(strval($configuration),'.conf.php') . "_configuration_load";
    $func($chosen);
    $content['marker'] = '[x]';                                           // create the user menu
    $content['allmods'] = true;
    $content['showlogout'] = true;
    $content['content'] = '';

    // Load up database
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = ?";

    $result = $dbconn->Execute($query,array('left'));
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        throw new BadParameterException(null,$msg);
    }

    list ($leftBlockGroup) = $result->fields;

    $menuBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                 array('module'  => 'base',
                                       'type'=> 'menu'));
    if (empty($menuBlockType)) {
        throw new EmptyParameterException($menuBlockType,'Variable is empty "#(1)" ');
    }
    $menuBlockTypeId = $menuBlockType['tid'];

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'mainmenu'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                      array('title' => 'Main Menu',
                            'name'  => 'mainmenu',
                            'type'  => $menuBlockTypeId,
                            'groups' => array(array('gid' => $leftBlockGroup,
                                                    'template' => '',)),
                            'template' => '',
                            'content' => serialize($content),
                            'state' => 2))) {
            return;
        }
    }
     xarResponseRedirect(xarModURL('installer', 'admin', 'cleanup'));
}


?>