<?php
//Updating DD Email validations to new format

function fix_140_04()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating DD Email validations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
   try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of 26 (email)
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                   WHERE xar_prop_type = 26";
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
                $oldval = explode(';',$propvalidation);
                if (isset($oldval[0])) {
                    $min = explode(':',$oldval[0]);
                    if (isset($min[1])) $defaultval['xv_min_length']   = $min[1];
                }
                if (isset($oldval[1])) {
                    $max = explode(':',$oldval[1]);
                    if (isset($max[1])) $defaultval['xv_max_length']   = $max[1];
                }
                if (isset($oldval[2])) {
                    $reg = explode(':',$oldval[2]);
                    if (isset($reg[1])) $defaultval['xv_pattern']   = $reg[1];
                }
                if (isset($oldval[3])) {
                    $ob = explode(':',$oldval[3]);
                    if (isset($ob[1])) $defaultval['xv_obfuscate']   = $ob[1];
                }
                if (isset($oldval[4])) {
                    $txt = explode(':',$oldval[4]);
                    if (isset($txt[1])) $defaultval['xv_linktext']   = $txt[1];
                }
                if (isset($oldval[5])) {
                    $img = explode(':',$oldval[5]);
                    if (isset($img[1])) $defaultval['xv_useimage']   = $img[1];
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