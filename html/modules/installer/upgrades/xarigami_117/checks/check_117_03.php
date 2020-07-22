<?php
 //Check required modvars are set
function check_117_03()
{

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for UID instead of Name in Roles privs");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
   $dbconn = xarDB::$dbconn;
   try {
        //check for Name or UID in roles privileges
        $privstable = xarDB::$prefix.'_privileges';

        $knownrolepriv = 'LockEverybody';
        $knowninstance = 'Everybody';

        $query = "SELECT xar_name, xar_instance
                  FROM  $privstable
                  WHERE xar_name = ? and xar_instance =?";
                  $bindvars = array($knownrolepriv,$knowninstance);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) { //it is probably upgraded
        //we should really test for something else here
        } else { //we did find something
            $data['success'] = true;
            $data['reply'] = xarML("Tested OK");
            $data['test'] =$data['test'] && false;
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>