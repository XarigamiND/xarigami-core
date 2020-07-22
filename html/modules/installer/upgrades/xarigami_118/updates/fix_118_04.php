<?php

function fix_118_04()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating privs/masks for edit/moderate levels");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $maskstable = xarDB::$prefix . '_security_masks';
        //swap moderation and edit acl levels
        $levelnew = 431;
        $levelold = 500;
        $query = "UPDATE $maskstable
            SET xar_level =?
            WHERE xar_level= ?";
            $bindvars = array($levelnew, $levelold);
        $result = $dbconn->Execute($query,$bindvars);
        //now move moderate to edit
        $levelnew = 500;
        $levelold = 400;
        $query = "UPDATE $maskstable
            SET xar_level =?
            WHERE xar_level= ?";
            $bindvars = array($levelnew, $levelold);
        $result = $dbconn->Execute($query,$bindvars) ;
        //now move edit down to moderate
        $levelnew = 400;
        $levelold = 431;
        $query = "UPDATE $maskstable
            SET xar_level =?
            WHERE xar_level= ?";
            $bindvars = array($levelnew, $levelold);
        $result = $dbconn->Execute($query,$bindvars);

         //now what about all the privileges in the privileges table! Need to update them
         $privtable = xarDB::$prefix . '_privileges';
        //first move the 500 to 431; something odd and unlikely to be a manually input level
        $levelnew = 431;
        $levelold = 500;
        $query = "UPDATE $privtable
            SET xar_level =?
            WHERE xar_level= ?";
            $bindvars = array($levelnew, $levelold);
        $result = $dbconn->Execute($query,$bindvars);
        //now move moderate to edit
        $levelnew = 500;
        $levelold = 400;
        $query = "UPDATE $privtable
            SET xar_level =?
            WHERE xar_level= ?";
            $bindvars = array($levelnew, $levelold);
        $result = $dbconn->Execute($query,$bindvars) ;
        //now move edit down to moderate
        $levelnew = 400;
        $levelold = 431;
        $query = "UPDATE $privtable
            SET xar_level =?
            WHERE xar_level= ?";
            $bindvars = array($levelnew, $levelold);
        $result = $dbconn->Execute($query,$bindvars);
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>