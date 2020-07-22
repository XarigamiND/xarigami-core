<?php

function fix_121_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Removing old Xaraya template tags");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $tagtable =  $prefix . '_template_tags';
        $tagarray = array('base-js-plugin','base-js-event','base-js-framework','base-js-plugin');
        //check for existence
        foreach ($tagarray as $tag) {
            //check does it exist
            $query = "SELECT xar_id, xar_name, xar_module
                        FROM $tagtable
                       WHERE xar_name = ? and xar_module = ?
                    ";
                    $bindvars = array($tag,'base');
                     $result =  $dbconn->Execute($query,$bindvars);

            if (!$result->EOF) { //it exists and we need to remove it
                list($tagid, $tagname, $module) = $result->fields;
                //now delete it
                $query = "DELETE FROM $tagtable
                          WHERE xar_name = ? and xar_id = ?";
                          $bindvars = array($tagname,$tagid);
                    $result =  $dbconn->Execute($query,$bindvars);
            }
        }
        //get rid of old framework vars

        $modvartable =  $prefix . '_module_vars';
        $query = "DELETE FROM $modvartable
                 WHERE (xar_name  = ? or xar_name = ?)
                ";
                $bindvars = array('DefaultFramework','AutoLoadDefaultFramework');
                $result =  $dbconn->Execute($query,$bindvars);

        $result->close();

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>