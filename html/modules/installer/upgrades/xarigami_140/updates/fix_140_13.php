<?php
//Updating grouplist listing

function fix_140_13()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating group list validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of 45 grouplist
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                      WHERE  xar_prop_type = 45";
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
                $defaultval['xv_ancestorgrouplist'] = '';
                $defaultval['xv_parentgrouplist'] ='';
                $defaultval['xv_grouplist'] = '';
                //let's fix it
                if (!empty($propvalidation)) {
                    foreach(preg_split('/(?<!\\\);/', $propvalidation) as $option) {
                        // Semi-colons can be escaped with a '\' prefix.
                        $option = str_replace('\;', ';', $option);
                        // An option comes in two parts: option-type:option-value
                        if (strchr($option, ':')) {
                            list($option_type, $option_value) = explode(':', $option, 2);
                            $option_type = trim($option_type);
                            if ($option_type == 'ancestor') {
                                $defaultval['xv_ancestorgrouplist'] = array_merge($defaultval['xv_ancestorgrouplist'], explode(',', $option_value));
                            }
                            if ($option_type == 'parent') {
                                 $defaultval['xv_parentgrouplist'] = array_merge($defaultval['xv_parentgrouplist'], explode(',', $option_value));
                            }
                            if ($option_type == 'group') {
                                 $defaultval['xv_grouplist'] = array_merge( $defaultval['xv_grouplist'], explode(',', $option_value));
                            }
                        }
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
        $data['reply'] = xarML("Update Failed!");
    }
    return $data;
}

?>