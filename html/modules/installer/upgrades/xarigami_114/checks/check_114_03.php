<?php

function check_114_03()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking Reminder and Admin privileges");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        //block privs have changed since 1.1.4
        $sitePrefix = xarDB::$prefix;
        $privtable=$sitePrefix . '_privileges';
        //only test for key ones
        $requiredprivs = array('DenyReminder','DenyAdminMenu');
        foreach ( $requiredprivs as $priv) {
            try {
                    $query = "SELECT xar_name, xar_module, xar_instance
                          FROM $privtable
                          WHERE xar_name = ? and xar_module =?";
                          $bindvars = array($priv,'blocks');
                          $result = $dbconn->Execute($query,$bindvars);
                          if ($result->EOF) {
                          //they do not have it and need it
                                $data['success'] = true;
                                $data['reply'] = xarML("Not done!");
                                $data['test'] = $data['test'] && false;
                          }
                          //check if right format
                          list($name,$module,$instance) = $result->fields;
                          if ($name == 'DenyReminder') {
                              $correctinstance = 'base:content:reminder';
                          } else {
                              $correctinstance = 'base:adminmenu:all';
                          }
                          if ($instance != $correctinstance) {
                                //they do  have it but not correct format
                                $data['success'] = true;
                                $data['reply'] = xarML("Not done!");
                                $data['test'] = $data['test'] && false;
                          }
                }  catch (Exception $e) {
                    $data['success'] = false;
                    $data['reply'] = xarML("Bad test!");
                    $data['test'] =$data['test'] && false;
                }
        }

    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>