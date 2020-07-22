<?php

function fix_114_04()
{
     // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating Deny Block privileges'");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        //block privs have changed since 1.1.4
        $sitePrefix = xarDB::$prefix;
        $privtable=$sitePrefix . '_privileges';
        $privmembers=$sitePrefix . '_privmembers';

        $query = "SELECT xar_pid, xar_name, xar_module
                    FROM $privtable
                    WHERE xar_name = ? and xar_module =?";
        $bindvars = array('DenyBlocks','blocks');
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result->EOF) {
            //Have to delete it
            list($denypid, $pname,$pmodule)  = $result->fields;
            //delete from priv table
            $query = "DELETE FROM $privtable
                      WHERE xar_pid= ? ";
                      $result = $dbconn->Execute($query,array($denypid));
            //delete from parent table - all with this priv as it's gone
            $query = "DELETE FROM  $privmembers
                      WHERE xar_pid= ?";
                    $bindvars = array($denypid);
                    $result = $dbconn->Execute($query,$bindvars);

                //what about roles? ... leave for now, low probability?
        }
        $result->Close();
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }
    return $data;
}
?>