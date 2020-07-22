<?php
//Updating DD Select/dropwdown validations to new format

function fix_140_11()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating image and file list validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of 6
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                      WHERE xar_prop_type = 35 or xar_prop_type = 1200 or xar_prop_type = 13";
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
                //let's fix it
                if (strpos($propvalidation,';') !== false) {
                    $validations = explode(';',$propvalidation);
                    $dirvalidations = isset($validations[0]) ?$validations[0]:'';
                    $filevalidations = isset($validations[1]) ?$validations[1]:'';
                    $display = isset($validations[2]) ?$validations[2]:false;
                    if (strpos($dirvalidations,'|') !== false) {
                        $parts = explode('|',$dirvalidations);
                        $defaultval['xv_basedir'] = isset($parts[0])? $parts[0] :'';
                    } else {
                        $defaultval['xv_basedir']  = $dirvalidations;
                    }
                    //now the filetypes
                    if (isset( $filevalidations) && !empty( $filevalidations) && substr( $filevalidations,0,1) == '(' )
                    {
                         $filevalidations = trim( $filevalidations,'()');
                         $filevalidations = explode('|',  $filevalidations);

                        $defaultval['xv_file_ext'] = implode(',', $filevalidations);
                    } else {
                        $defaultval['xv_file_ext']  = '';
                    }
                    if (isset($validations[2]))  $defaultval['xv_max_file_size'] = trim($validations[2]);
                    $display = isset($validations[3]) ?$validations[3]:false;
                    $defaultval['xv_display'] = isset($display) ? $display : FALSE;
                    $defaultval['xv_display_width'] =100;

                } else {
                    $defaultval['xv_basedir'] = $propvalidation;
                    $defaultval['xv_display'] = 0;
                    $defaultval['xv_file_ext'] = '';
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