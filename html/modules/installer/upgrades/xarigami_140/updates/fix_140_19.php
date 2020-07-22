<?php
//Updating objectref DD

function fix_140_19()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating Object Ref DD validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of  507
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                      WHERE  xar_prop_type =  507";
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

           if (!$serialized && !empty($propvalidation)) {
                $defaultval = array ('xv_refobject' =>'objects',
                                     'xv_store_prop' => 'name',
                                      'xv_display_prop' => 'name',
                                     );
                //let's fix it
                $sep = ':';
                if(is_string($propvalidation) && strchr($propvalidation,$sep)) {
                    list($refobject,$display_prop,$store_prop) = explode($sep,$propvalidation);
                    if ($refobject != '' && is_string($refobject)) $defaultval['xv_refobject'] = $refobject;
                    if ($display_prop != '' && is_string($display_prop)) $defaultval['xv_display_prop'] = $display_prop;
                    if ($store_prop != '' && is_string($store_prop)) $defaultval['xv_store_prop'] = $store_prop;
                }


                $newval = serialize($defaultval);
                //now we need to update the table
                try {
                    $newquery = "UPDATE $dynamicprops
                                SET  xar_prop_validation = ? WHERE xar_prop_id = ?";
                    $result2 = $dbconn->Execute($newquery,array($newval,$propid));

                } catch (Exception $e) {
                    $data['success'] = false;
                    $data['reply'] = xarML("Update Problem");
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