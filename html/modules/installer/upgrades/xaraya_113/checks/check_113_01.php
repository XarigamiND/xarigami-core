<?php
//disallowed emails
function check_113_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking disallowed emails in roles var");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
     try {
        $prefix = xarDB::$prefix;
        $mods =  $prefix . '_modules';
        $vars =  $prefix . '_module_vars';

         $query = "SELECT xar_id, xar_name, xar_regid
                FROM $mods
                WHERE xar_name = 'registration'
                ";
        $resulta =  $dbconn->Execute($query);
        if ($resulta) {
            list ($sysid,$modname,$regid) = $resulta->fields;
            //check if the old installertheme is present
            $query2 = "SELECT xar_name
                    FROM $vars
                    WHERE xar_name = 'disallowedemails' and xar_modid = ?
                    ";
            $result =  $dbconn->Execute($query2, array($sysid));
            if (!$result->EOF) { //it  exists and we need to get rid of it
                $data['success'] = true;
                 $data['reply'] = xarML("Not done!");
                $data['test'] =$data['test'] && false;
            }
        } //else registration doesn't exist so don't worry about it
    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>