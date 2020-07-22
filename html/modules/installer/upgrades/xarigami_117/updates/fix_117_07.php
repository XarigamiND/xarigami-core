<?php

function fix_117_07()
{

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding Read Block Groups priv");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        //The user has CasualAccess or ReadNonCore priv and we need to create ReadBlockGroups priv to add to it
        $sitePrefix = xarDB::$prefix;
        $privtable=$sitePrefix . '_privileges';
        $privmembertable=$sitePrefix . '_privmembers';
        $seqId = $dbconn->GenId($privtable);
        $query = "INSERT INTO $privtable (
                    xar_pid, xar_name, xar_realm, xar_module, xar_component,
                    xar_instance, xar_level, xar_description)
                  VALUES (?,?,?,?,?,?,?,?)";
        $bindvars = array($seqId,
                          'ReadBlockGroups', 'All', 'blocks', 'Blockgroup',
                          'All', 200, 'View Block Groups');
        $result = $dbconn->Execute($query,$bindvars);

        $childpid = $seqId;

         //find the parent priv
         $query = "SELECT xar_pid, xar_name, xar_module
              FROM $privtable
              WHERE (xar_name = ? or xar_name = ?) and xar_module =?";
              $bindvars = array('ReadNonCore','CasualAccess','empty');
              $result = $dbconn->Execute($query,$bindvars);
        $privs = array();
        for (; !$result->EOF; $result->MoveNext()) {
           list($parentpid,$name,$module) = $result->fields;
            $privs[$parentpid] = array('parentpid'=>$parentpid,
                            'privname'=>$name,
                            'privmodule'=>$module);
        }
        //we must have one or both, eitherway they need an entry in the privmembers table
        if (!empty($privs)) {
            foreach ($privs as $parentpid=>$parentpriv) {
                //make an entry in the privs member table
                 $query = "INSERT INTO $privmembertable VALUES (?,?)";
                 $bindvars = array($childpid, $parentpid);
                $result = $dbconn->Execute($query,$bindvars);
            }
        }

        $result->Close();
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>