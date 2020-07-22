<?php

function fix_117_08()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding Admin Lock privs and members");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        //let's try and use existing functions first where possible
         //hmm surely ....but just in case
        $prefix =  xarDB::$prefix;
        $tables['privileges'] = $prefix . '_privileges';
        $tables['security_masks'] = $prefix . '_security_masks';
        $tables['privmembers'] = $prefix . '_privmembers';
        $tables['modules'] = $prefix . '_modules';
        $tables['security_instances'] = $prefix . '_security_instances';
        $tables['security_acl'] = $prefix . '_security_acl';
        $tables['security_realms'] = $prefix . '_security_realms';
        $tables['security_levels'] = $prefix . '_security_levels';
        $tables['roles'] = $prefix . '_roles';
        $tables['rolemembers'] = $prefix . '_rolemembers';
        xarDB::importTables($tables);
        if (class_exists('sys')) {
            sys::import('xarigami.xarSecurity');
            sys::import('modules.privileges.xarclass.xarprivileges');
        } else {
              include_once('lib/xarigami/xarSecurity.php');
             include_once('modules/privileges/xarclass/xarprivileges.php');
        }

        $levels = array(0=>'ACCESS_NONE',
                        100=>'ACCESS_OVERVIEW',
                        200=>'ACCESS_READ',
                        300=>'ACCESS_COMMENT',
                        400=>'ACCESS_EDIT',
                        500=>'ACCESS_MODERATE',
                        600=>'ACCESS_ADD',
                        700=>'ACCESS_DELETE',
                        800=>'ACCESS_ADMIN');
        //we need to delete anything that exists and recreate so we don't get errors
        //can't use anything with xarMod* with safety, including anything of xarRoles
        $privset = array('AdminLock','AdminMyself','AdminEverybody','AdminAnonymous','AdminAdministrators','LockAdminLock');
        //check this ...may not run
        foreach ($privset as $privname) {
            $thispriv= new xarPrivileges();
            $blockpriv = $thispriv->findPrivilege($privname);
            if ($blockpriv) {
               $blockpriv->remove();
            }
        }
        $testpriv = xarPrivExists('AdminLock');
        $anonid = xarUpgradeGetVar('Site.User.AnonymousUID');
        $adminid = xarUpgradeGetModVar('Roles','admin');
        //find Everybody
        $query = "SELECT xar_uid, xar_name
                  FROM  ".$tables['roles']."
                  WHERE xar_name = ? ";
                  $bindvars = array('Everybody');
                  $result = $dbconn->Execute($query,$bindvars);
                  list($uid,$name) = $result->fields;
        $everybodyuid = $uid;
        //find Myself
        $query = "SELECT xar_uid, xar_name
                  FROM  ".$tables['roles']."
                  WHERE xar_name = ? ";
                  $bindvars = array('Myself');
                  $result = $dbconn->Execute($query,$bindvars);
                  list($uid,$name) = $result->fields;
        $myselfuid = $uid;
        //find Administrators
        $query = "SELECT xar_uid, xar_name
                  FROM  ".$tables['roles']."
                  WHERE xar_name = ? ";
                  $bindvars = array('Administrators');
                  $result = $dbconn->Execute($query,$bindvars);
                  list($uid,$name) = $result->fields;
        $administratoruid = $uid;

         //find the adminpid
        $name = 'Administration';
        $query = "SELECT * FROM ".$tables['privileges']." WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $dbconn->Execute($query,array($name));
        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $adminpid = $pid;
        } else {
            $data['success'] = false;
            $data['reply'] = xarML("Failed!");
        }
        //find the GeneralLock pid
        $name = 'GeneralLock';
        $query = "SELECT * FROM ".$tables['privileges']." WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result =  $dbconn->Execute($query,array($name));
        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $lockpid = $pid;
        } else {
            $data['success'] = false;
            $data['reply'] = xarML("Failed!");
        }

        //manually remove the priv from the parent
        $query = "DELETE FROM ".$tables['privmembers']."
                  WHERE xar_pid= ? AND xar_parentid= ?";

        $bindvars = array($lockpid, $adminpid);
        $result = $dbconn->Execute($query,$bindvars);

        //have remove role from priv manually
        $query = "DELETE FROM ".$tables['security_acl']."
                  WHERE xar_partid= ? AND xar_permid= ?";
        $bindvars = array($administratoruid, $lockpid);
         $result = $dbconn->Execute($query,$bindvars);

        //sigh, have to do each one manually
        $levelvalues =array_flip($levels);
        $privarray = array (
           array('AdminLock','All','empty','Roles','All',$levelvalues['ACCESS_EDIT'],xarML('A container privilege for denying delete access to certain roles')),
           array('AdminMyself','All','roles','Roles',$myselfuid,$levelvalues['ACCESS_EDIT'],xarML('Edit access to Myself role')),
           array('AdminEverybody','All','roles','Roles',$everybodyuid,$levelvalues['ACCESS_EDIT'],xarML('Edit access to Everybody role')),
           array('AdminAnonymous','All','roles','Roles',$anonid,$levelvalues['ACCESS_EDIT'],xarML('Edit access to Anonymous role')),
           array('AdminAdministrators','All','roles','Roles',$administratoruid,$levelvalues['ACCESS_EDIT'],xarML('Edit access to Administrators role')),
           array('LockAdminLock','All','privileges','Privileges','AdminLock',$levelvalues['ACCESS_NONE'],xarML('Deny access to AdminLock privilege'))
       );
       $pidarray = array();
        foreach ($privarray as $privdata) {
            $nextid =($dbconn->genID($tables['privileges']));
            $query = "INSERT INTO ".$tables['privileges']."
                  (xar_pid, xar_name, xar_realm, xar_module, xar_component,xar_instance, xar_level, xar_description)
                  VALUES (?,?,?,?,?,?,?,?)";
            $bindvars = array($nextid,$privdata[0],$privdata[1],$privdata[2], $privdata[3],$privdata[4], $privdata[5], $privdata[6]);
            $result = $dbconn->Execute($query,$bindvars);
            $pidarray[$privdata[0]] = $nextid;
        }

       // xarMakePrivilegeRoot('AdminLock');
        //double check the pid for AdminLock
        $name = 'AdminLock';
        $query = "SELECT * FROM ".$tables['privileges']." WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $dbconn->Execute($query,array($name));
        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $adminlockpid = $pid;
        }
        $query = "INSERT INTO ".$tables['privmembers']." VALUES (?,0)";
        $result = $dbconn->Execute($query,array($adminlockpid));

        $privnames = array('AdminMyself','AdminEverybody','AdminAnonymous','AdminAdministrators','LockAdministration','LockAdminLock','LockGeneralLock');
        foreach($privnames as $priv) {
            //get the pid
            $name = $priv;
            $query = "SELECT * FROM ".$tables['privileges']." WHERE xar_name = ?";
            //Execute the query, bail if an exception was thrown
            $result = $dbconn->Execute($query,array($name));
            if (!$result->EOF) {
                list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            } else {
                break;
            }
            //now make member
            $query = "INSERT INTO ".$tables['privmembers']."  VALUES (?,?)";
            $bindvars = array($pid, $adminlockpid);
            $result = $dbconn->Execute($query,$bindvars);
        }

        //xarAssignPrivilege('AdminLock','Administrators');
         $query = "INSERT INTO ".$tables['security_acl']." VALUES (?,?)";
         $bindvars = array($administratoruid,$adminlockpid);
         $result = $dbconn->Execute($query,$bindvars);


    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>