<?php
//Updating user listing

function fix_140_14()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating file upload validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of 9
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                      WHERE  xar_prop_type = 9";
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
                                     'xv_display_layout' => 'default');
                //no uploads hooked
                //let's fix it
                if (!empty($propvalidation)) {
                    $fields = explode(';', $propvalidation);
                    if (isset($fields[0]) && trim($fields[0]) != '') {
                        $prop_path = rtrim(trim($fields[0]), '/');
                       $defaultval['xv_basedir'] = $prop_path;
                    } else {
                        // No base directory supplied, so default to '{var}/uploads', with no basedir.
                        $defaultval['xv_basedir'] = '{var}/uploads';
                    }

                    // TODO: allow descendant class to override filetype.
                    if (isset($fields[1]) && !empty($fields[1]) && substr($fields[1],0,1) == '(' )
                    {
                        $fields[1] = trim( $fields[1],'()');
                         $fields[1] = explode('|', $fields[1]);

                        $defaultval['xv_file_ext'] = implode(',',$fields[1]);
                    } else {
                        $defaultval['xv_file_ext']  = '';
                    }
                    if (isset($fields[2]))  $defaultval['xv_max_file_size'] = trim($fields[2]);
                    $display = isset($fields[3]) ?$fields[3]:false;
                    $defaultval['xv_display'] = isset($display) ? $display : FALSE;
                    $defaultval['xv_display_width'] =100;
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
        $data['reply'] = xarML("Update Failed!");
    }
    return $data;
}

?>