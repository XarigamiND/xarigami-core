<?php

function check_140_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking DD validation field is correct type (textarea)");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDBGetConn();
    $updaterequired = FALSE;
    try {
        $prefix = xarDBGetSiteTablePrefix();
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';

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
        if (empty($valtype) || $valtype == 'string') {
           //we have properties to update
           $updaterequired = TRUE;
        }

        //let's also check for the configuration property itself
          $query2 = "SELECT xar_prop_id, xar_prop_name, xar_prop_type
                   FROM $dynamicprops
                    WHERE xar_prop_name = 'validation' and xar_prop_objectid = 2 and xar_prop_moduleid = 182";
            $result2 = $dbconn->Execute($query2);
        if (!$result2) {
            $updaterequired = TRUE;
        } else {
            //should be one result
            list ($propid, $propname, $proptype) = $result2->fields;
                    if ($proptype != 4)   {
                        $updaterequired = TRUE;
                    }
        }

        if ($updaterequired === TRUE) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Test Failed!");
        $data['test'] =$data['test'] && false;
    }

    return $data;
}

?>