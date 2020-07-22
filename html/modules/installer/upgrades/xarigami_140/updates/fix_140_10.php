<?php
//Updating DD Select/dropwdown validations to new format

function fix_140_10()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating DD Select and Dropdown validations");
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
                  WHERE xar_prop_type = 6 or xar_prop_type = 16 or xar_prop_type = 34 or xar_prop_type = 1114
                        or xar_prop_type = 1115 or xar_prop_type = 506 or xar_prop_type = 39 or xar_prop_type = 36
                        or xar_prop_type = 32 or xar_prop_type = 43";
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
                if (is_array($propvalidation)) {
                    $optionlist = '';
                    foreach($validation as $id => $name) {
                         $optionlist .= $id.','.$name.';';
                    }
                    $defaultval['xv_optionlist'] = $optionlist;

                // if the validation field starts with xarModAPIFunc
                } elseif (preg_match('/^xarModAPIFunc/i',$propvalidation)) {
                      $defaultval['xv_func'] = $propvalidation;

                } elseif (strchr($propvalidation,';') || strchr($propvalidation,',')) {
                    $defaultval['xv_optionlist'] = $propvalidation;

                // or if it contains a data file path
                } elseif (preg_match('/^{file:(.*)}/',$propvalidation, $fileMatch)) {
                     $defaultval['xv_file'] =  $fileMatch[1];

                } elseif (!empty($propvalidation)) {
                    $defaultval['xv_other'] = $propvalidation;
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