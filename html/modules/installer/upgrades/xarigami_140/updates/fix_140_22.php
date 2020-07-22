<?php
//Updating object configuration

function fix_140_22()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating object configuration");
    $data['reply'] = xarML("Done!");
     $dbconn = xarDB::$dbconn;
 try {
        $prefix = xarDB::$prefix;
        $props = $prefix.'_dynamic_properties';
        //get the object configuration prpo

        $sql = "SELECT  xar_prop_id, xar_prop_validation
                    FROM  $props
                WHERE (xar_prop_name = 'config') and (xar_prop_objectid = '1') and (xar_prop_moduleid = '182') and (xar_prop_type = '999') ";

            $result = $dbconn->Execute($sql);

        if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }

        list($propertyid,  $propertyconfig) = $result->fields;

        $result->close();

        $doupdate = FALSE;
        if (!is_array($propertyconfig) && substr($propertyconfig,0,2) == 'a:')
        {
                try {
                    $check = unserialize($propertyconfig);
                }catch (Exception $e) {
                        $doupdate = TRUE;
                }

                if ((isset($check['xv_columns']) && $check['xv_columns'] !='1') ||  (isset($check['xv_max_rows']) && $check['xv_max_rows'] != 0))
                {
                   $doupdate = TRUE;
                }
        }

        if ($doupdate === TRUE)
        {
            $newval = 'a:12:{s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";s:10:"xv_columns";s:1:"1";s:7:"xv_rows";s:1:"1";s:11:"xv_max_rows";s:1:"0";s:12:"xv_prop_type";s:1:"2";s:12:"xv_key_label";s:6:"Option";s:14:"xv_value_label";s:5:"Value";s:15:"xv_suffix_label";s:3:"Row";s:20:"xv_associative_array";s:1:"1";s:13:"xv_fixed_keys";s:1:"1";s:12:"xv_addremove";s:1:"2";}';
            $query2 = "UPDATE $props
                     SET xar_prop_validation = ?
                    WHERE xar_prop_id = ? ";
                    $bindvars2 = array($newval, $propertyid);
                    $result2 = $dbconn->Execute($query2,$bindvars2);
        }
        if (!$result2) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }
          $result->close();
   } catch (Exception $e) {

  //      $data['success'] = false;
 //      $data['reply'] = xarML("Update Failed!");

  }

    return $data;
}
?>