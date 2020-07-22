<?php
//Updating debug group

function fix_140_21()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating upload property configurations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $modvars = $prefix.'_module_vars';
        $mods = $prefix.'_modules';
        $rolestable = $prefix.'_roles';
        //get the admin role
         $query = "SELECT xar_uid
                    FROM  $rolestable
                      WHERE xar_name = 'Administrators'";

        $result = $dbconn->Execute($query);

         if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }
        list($debugggroup) = $result->fields;

        //get the priv module
         $query = "SELECT xar_id
                    FROM  $mods
                      WHERE xar_name = 'privileges' and xar_regid = 1098";

        $result1 = $dbconn->Execute($query);
        if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }
        list($systemid) = $result1->fields;

        $nextId = $dbconn->GenId($modvars);
        $name = 'debuggroup';
        //set the debug group mod var
          $query = "INSERT INTO $modvars (
                          xar_id,
                          xar_modid,
                          xar_name,
                          xar_value)
                        VALUES (?,?,?,?)";
        $result2 = $dbconn->Execute($query,array($nextId,$systemid,$name,$debugggroup));
        if (!$result2) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Update Failed!");

    }
    return $data;
}
?>