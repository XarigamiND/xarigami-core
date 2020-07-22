<?php
//Updating DD Textarea validations to new format

function fix_140_02()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating DD Textarea validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
   try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of 2 (textbox)
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                   WHERE xar_prop_type = 3 or xar_prop_type = 4 or xar_prop_type = 5 or xar_prop_type = 205 or xar_prop_type = 46";
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

            if (!$serialized) {
                $defaultval = array ('xv_allowempty' => 1,
                                     'xv_display_layout' => 'default',
                    );
                //let's fix it
                $oldval = explode(':',$propvalidation);
                if (isset($oldval[0])) $defaultval['xv_rows']   = $oldval[0];
                if (isset($oldval[1])) $defaultval['xv_cols']       = $oldval[1];
                if (isset($oldval[2])) $defaultval['xv_classname'] = $oldval[2];

                if (isset($oldval[3])) {
                    if (substr($oldval[3],0,8) == 'maxlength') { //special case
                        $defaultval['xv_max_length']  = $oldval[3];
                    } else {
                        $defaultval['xv_other']     = $oldval[3];
                    }
                }
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
        $data['reply'] = xarML("Test Failed!");
    }
    return $data;
}

?>