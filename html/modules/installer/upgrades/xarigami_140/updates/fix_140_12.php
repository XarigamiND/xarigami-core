<?php
//Updating user listing

function fix_140_12()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating user listing validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of 37
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                      WHERE  xar_prop_type = 37";
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
                $defaultval['xv_grouplist'] = array();
                $defaultval['xv_orderlist'] = array();
                $defaultval['xv_showfields'] =array();
                //let's fix it
                if (preg_match('/^xarModAPIFunc/i',$propvalidation)) {
                     $defaultval['xv_func'] = $propvalidation;
                } else {
                    foreach(preg_split('/(?<!\\\);/', $propvalidation) as $option) {
                        // Semi-colons can be escaped with a '\' prefix.
                        $option = str_replace('\;', ';', $option);
                        // An option comes in two parts: option-type:option-value
                        if (strchr($option, ':')) {
                            list($option_type, $option_value) = explode(':', $option, 2);
                            if ($option_type == 'state' && is_numeric($option_value)) {
                               $defaultval['xv_userstate']  = $option_value;
                            }

                            if ($option_type == 'group') {
                                 $defaultval['xv_grouplist'] = array_merge($defaultval['xv_grouplist'], explode(',', $option_value));
                            }
                            if ($option_type == 'show') {
                                 $defaultval['xv_showfields'] = array_merge( $defaultval['xv_showfields'], explode(',', $option_value));
                                // Remove invalid elements (fields that are not valid).
                                $showfilter = create_function(
                                    '$a', 'return preg_match(\'/^[-]?(name|uname|email|uid|state|date_reg)$/\', $a);'
                                );
                                 $defaultval['xv_showfields'] = array_filter( $defaultval['xv_showfields'], $showfilter);
                            }
                            if ($option_type == 'order') {
                                 $defaultval['xv_orderlist'] = array_merge($defaultval['xv_orderlist'], explode(',', $option_value));
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