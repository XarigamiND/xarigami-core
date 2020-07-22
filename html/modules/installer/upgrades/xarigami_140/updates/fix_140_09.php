<?php
//Updating DD calendar validations to new format

function fix_140_09()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating DD Calendar and Extended Date validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
   try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of 8 or 47
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                   WHERE xar_prop_type = 8 or xar_prop_type = 47";
        $result = $dbconn->Execute($query);

        while (!$result->EOF) {
        //we have properties to possibly update validations
            list ($propid,$propname, $propsource, $propvalidation)  = $result->fields;

            try {
                $check = @unserialize($propvalidation);
            } catch (Exception $e) {
                //do nothing
            }
            $serialized =  ($check===false && $propvalidation != serialize(false)) ? false : true;

           if (!$serialized || empty($propvalidation)) {
                $defaultval = array ('xv_allowempty' => 1,
                                     'xv_display_layout' => 'default',
                    );
                //let's fix it
                $defaultval['xv_dbformat'] = isset($propvalidation) && !empty($propvalidation) ? $propvalidation : 'timestamp';


                $newval = serialize($defaultval);
                //now we need to update the table
                try {
                    $newquery = "UPDATE $dynamicprops
                                SET  xar_prop_validation = ? WHERE xar_prop_id = ?";
                    $result2 = $dbconn->Execute($newquery,array($newval,$propid));

                } catch (Exception $e) {
                    $data['success'] = false;
                    $data['reply'] = xarML("Problem");
                    break;
                }
            }
            $result->MoveNext();
        }

   } catch (Exception $e) {
        $data['success'] = false;
        $data['reply'] = xarML("Update Failed!");
    }
    return $data;
}

?>