<?php

function fix_140_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Changing DD prop_validation field to type text");
    $data['reply'] = xarML("Done!");
    xarDBLoadTableMaintenanceAPI();
   $dbconn = xarDBGetConn();
   try {
        $prefix = xarDBGetSiteTablePrefix();
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
          $dynamicprops = $prefix.'_dynamic_properties';

        //first ensure we update the validation property itself
        $dopropupdate = "UPDATE $dynamicprops
                        SET xar_prop_type  = 4, xar_prop_validation = 'a:2:{s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'
                        WHERE xar_prop_name = 'validation' and xar_prop_objectid = 2 and xar_prop_moduleid = 182";
        $updateresult = $dbconn->Execute($dopropupdate);

        //now the messy bit - add a temporary column to the table

         //let's check again first we need this
         $query = "SELECT *
                   FROM $dynamicpropdefs";

        $result = $dbconn->Execute($query);
        $fieldinfo = $result->FieldTypesArray();
        $valtype = '';
        foreach ($fieldinfo as $fieldtype) {
            if ($fieldtype->name == 'xar_prop_validation') {
                $valtype = $fieldtype->type;
                break;
            }
        }

      if (empty($valtype) || $valtype == 'string')
        {
           //we have properties to update
            //here we go - we can't just 'alter' the table for cross db type compatibility

            //create temp table column
            $query = xarDBAlterTable( $dynamicpropdefs,
                                  array('command' => 'add',
                                        'field'   => 'xar_temp_propdef',
                                        'type'    => 'text',
                                        'default' => ''));
              $result = $dbconn->Execute($query);

            //copy data from old to temp column
            $query = "SELECT xar_prop_id, xar_prop_validation
                         FROM $dynamicpropdefs";
             $result = $dbconn->Execute($query);

            for (; !$result->EOF; $result->MoveNext()) {
              list($propid, $prop_validation) = $result->fields;
              // Covert the first field data
                 //Copy to temp fields
              $query1 = "UPDATE $dynamicpropdefs
                          SET xar_temp_propdef = '".$prop_validation."'
                         WHERE xar_prop_id   = '".$propid."'";
                $result2 = $dbconn->Execute($query1);
            }
            //get rid of the original column
            $dropquery="ALTER TABLE $dynamicpropdefs DROP xar_prop_validation";
            $result = $dbconn->Execute($dropquery);

            //add back the column with correct definition
            $addquery = xarDBAlterTable($dynamicpropdefs,
                                  array('command' => 'add',
                                        'field'   => 'xar_prop_validation',
                                        'type'    => 'text',
                                        'default' => ''));
                $result = $dbconn->Execute($addquery);
                if (!$result) return;

            //copy from temp to final table
            $copyquery= "SELECT COUNT(1)
                        FROM $dynamicpropdefs";
            $result = $dbconn->Execute($copyquery);
            for (; !$result->EOF; $result->MoveNext()) {

               $docopy = "UPDATE $dynamicpropdefs
                          SET xar_prop_validation  = xar_temp_propdef";
               $doupdate = $dbconn->Execute($docopy);
               if (!$doupdate) return;
            }
            //drop the temp table column
            $query="ALTER TABLE $dynamicpropdefs DROP xar_temp_propdef";
            // Pass to ADODB, and send exception if the result isn't valid.
            $result = $dbconn->Execute($query);
            $result->close();
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }
    return $data;
}


?>