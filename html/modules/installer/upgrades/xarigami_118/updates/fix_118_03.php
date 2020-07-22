<?php

function fix_118_03()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating ACLs for correct Moderate levels");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        //swap moderation and edit acl levels
        $levelstable = xarDB::$prefix . '_security_levels';
        $leveltext = 'ACCESS_MODERATE';
        $level = 500;
        $query = "UPDATE $levelstable
            SET xar_level =?
            WHERE xar_leveltext= ?";
            $bindvars = array($level, $leveltext);
        $result = $dbconn->Execute($query,$bindvars);
        //update edit
        $leveltext = 'ACCESS_EDIT';
        $level = 400;
        $query = "UPDATE $levelstable
            SET xar_level =?
            WHERE xar_leveltext= ?";
            $bindvars = array($level, $leveltext);
        $result = $dbconn->Execute($query,$bindvars);

        //drop our experiment to use access_submit instead of access_comment - too much trouble
        $query = "DELETE FROM $levelstable  WHERE xar_leveltext = ?";
        $bindvars = array('ACCESS_SUBMIT');
        $result = $dbconn->Execute($query,$bindvars);

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>