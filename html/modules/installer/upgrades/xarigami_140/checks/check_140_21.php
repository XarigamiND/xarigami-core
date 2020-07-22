<?php
// Check debug group
function check_140_21()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking debug group is set");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $modvars = $prefix.'_module_vars';
        $mods = $prefix.'_modules';
        //get the priv module
         $query = "SELECT xar_id
                    FROM  $mods
                      WHERE xar_name = 'privileges' and xar_regid = 1098";

        $result = $dbconn->Execute($query);
        if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }

        list($systemid) = $result->fields;
        $vname = 'debuggroup';
        //get the debug group mod var
         $query2 = "SELECT xar_name, xar_value
                    FROM $modvars
                    WHERE xar_name = ? and xar_modid = ?";

        $result2 = $dbconn->Execute($query2,array((string)$vname,(int)$systemid));

         list($varname,$varvalue) = $result2->fields;
         if (!$result2) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }

        if (!isset($varname) || is_null($varname)) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
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