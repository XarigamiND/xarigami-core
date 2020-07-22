<?php

function fix_114_03()
{
     // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating Reminder and Admin Menu privileges'");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $privtable=$prefix . '_privileges';
        $privmembertable=$prefix  . '_privmembers';
        $requiredprivs = array(
            array('DenyReminder','All','blocks','Block','base:content:reminder',0,'Deny reminder block'),
            array('DenyAdminMenu','All','blocks','Block','base:adminmenu:all',0,'Deny Admin menu'),
            array('ViewBaseBlocks','All','blocks','Block','base:all:all',100,'View all base blocks'),
            array('ViewLogin','All','blocks','Block','authsystem:login:login',200,'View login block'),

            );
        //We need to add two privs and attach to General Lock
        //and add the other 2 privs to all of the other top levels
        //we need to check the Admin menu is correct format
        //find the parent privs
        $parentpids= array();
        $query = "SELECT xar_pid, xar_name, xar_module
              FROM $privtable
              WHERE xar_name = ? and xar_module =?";
              $bindvars = array('GeneralLock','empty');
              $result = $dbconn->Execute($query,$bindvars);
        if (!$result->EOF) {
           list($generallockpid,$name,$module) = $result->fields;
           $parentpids['GeneralLock'] = $generallockpid;
        }else {
              $data['success'] = false;
              $data['reply'] = xarML("Failed!");
        }
        //now try and find the Casual Access or ReadNonCore - we don't have these now but they might have them
        $query = "SELECT xar_pid, xar_name, xar_module
                  FROM $privtable
                  WHERE (xar_name = ? or xar_name = ? or xar_name = ? or xar_name = ?) and xar_module =?";
                  $bindvars = array('ReadNonCore','CasualAccess','CommentNonCore','ModerateNonCore','empty');
                  $result = $dbconn->Execute($query,$bindvars);

        for (; !$result->EOF; $result->MoveNext()) {
           list($anonpid,$name,$module) = $result->fields;
           $parentpids[$name] = $anonpid;
        }

        if (!empty($parentpids)) {
            foreach ($requiredprivs as $priv=>$p) {
                try {   //check to see if therequired  priv exists or not
                    $query = "SELECT xar_name, xar_module, xar_instance
                            FROM $privtable
                            WHERE xar_name = ? and xar_module =?";
                    $bindvars = array($p[0],'blocks');
                    $result = $dbconn->Execute($query,$bindvars);
                    if (!$result->EOF) {
                        xarLogMessage('UPGRADE: Found priv '. $p[0]);
                        //found it is it correct format?
                        list($name, $module,$instance) = $result->fields;
                        if (($name == $p[0]) && ($instance == $p[4])) {
                            continue; //we found it and it's ok, move to next
                        } elseif (($name == $p[0]) && ($instance != $p[4])) {
                            //the instance is wrong - update it
                            $query2 = "UPDATE $privtable
                                       SET xar_instance = ?
                                       WHERE  xar_name = ? and xar_module =?";
                            $bindvars = array($p[4],$p[0],'blocks');
                            $result = $dbconn->Execute($query2,$bindvars);
                            continue; //we found it and it's ok, move to next

                        }
                    }

                    //we didn't find it - we have to insert it
                    $seqId = $dbconn->GenId($privtable);
                    $query = "INSERT INTO $privtable (
                              xar_pid, xar_name, xar_realm, xar_module, xar_component, xar_instance, xar_level, xar_description)
                              VALUES (?,?,?,?,?,?,?,?)";

                    $bindvars = array($seqId,$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]);
                    $result = $dbconn->Execute($query,$bindvars);
                    //check
                    $query = "SELECT xar_pid, xar_name, xar_module
                            FROM $privtable
                             WHERE xar_name = ?";
                              $result = $dbconn->Execute($query,array($p[0]));
                    xarLogMessage('UPGRADE: PRiv just inserted for '. $p[0]);
                    if (!$result) {
                         $data['success'] = false;
                         $data['reply'] = xarML("Failed!");
                    } else {
                        list($pid,$name,$module)= $result->fields;
                        //make an entry in the privs member table for General Lock
                        if ($p[0] == 'DenyReminder' || $p[0] == 'DenyAdminMenu') {
                            $query = "INSERT INTO $privmembertable VALUES (?,?)";
                             $bindvars = array($pid, $parentpids['GeneralLock']);
                            $result = $dbconn->Execute($query,$bindvars);
                        } else {
                        //now test and add ViewLogin and ViewBase blocks to any of the other ParentPrivs
                            foreach ($parentpids as $pidname => $ppid) {
                                if ($pidname != 'GeneralLock') {
                                    $query = "INSERT INTO $privmembertable VALUES (?,?)";
                                    $bindvars = array($pid, $ppid);
                                    $result = $dbconn->Execute($query,$bindvars);
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $data['success'] = false;
                    $data['reply'] = xarML("Failed!");
                }

            }
        }
        $result->Close();
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }

    return $data;
}
?>