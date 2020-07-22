<?php
//Updating object configuration

function fix_140_18()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating object configurations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $props = $prefix.'_dynamic_properties';
        $objects = $prefix.'_dynamic_objects';
        //get base menu type
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_objectid, xar_prop_moduleid, xar_prop_itemtype, xar_prop_type,  xar_prop_validation
                    FROM $props
                   WHERE xar_prop_name = 'config' and xar_prop_objectid=1 and xar_prop_moduleid = 182 and xar_prop_itemtype = 0";
        $result = $dbconn->Execute($query);

        if (!$result) {
             $data['success'] = false;
             $data['reply'] = xarML("Test problem");
             $data['test'] =$data['test'] && false;
             return $data ;
        }


        list ($propid, $propname, $objectid, $moduleid, $itemtype, $proptype, $propvalidation) = $result->fields;

          if (($proptype != 999) || (substr($propvalidation,0,2) != 'a:')) {
            // update the property type in the db and the configuration
            //finally update the block instance
            $proptypevalue = 999;
            $configvalue = 'a:12:{s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";s:10:"xv_columns";s:1:"1";s:7:"xv_rows";s:1:"1";s:11:"xv_max_rows";s:1:"0";s:12:"xv_prop_type";s:1:"2";s:12:"xv_key_label";s:6:"Option";s:14:"xv_value_label";s:5:"Value";s:15:"xv_suffix_label";s:3:"Row";s:20:"xv_associative_array";s:1:"1";s:13:"xv_fixed_keys";s:1:"1";s:12:"xv_addremove";s:1:"2";}';

            $updatequery = "UPDATE $props
                             SET xar_prop_type = ?, xar_prop_validation= ?
                             WHERE xar_prop_name = 'config' and xar_prop_objectid=1 and xar_prop_moduleid = 182 and xar_prop_itemtype = 0";
                $bindvars = array((int)$proptypevalue,(string)$configvalue);
            try {
                $doupdate = $dbconn->Execute($updatequery,$bindvars);

            } catch (Exception$e) {
                $data['success'] = false;
                $data['reply'] = xarML("Test problem");
                $data['test'] = false;
                return $data ;
            }
        }
        //let's check all the object and property configuration validations while we are at it, now we know the key config is done
        //set an array of fields to do with prop name, objectid, module id, itemtype, and value
        $updatearray = array(
                            array('name',       1,  182,    0,  'a:5:{s:13:"xv_min_length";s:1:"1";s:13:"xv_max_length";s:2:"30";s:7:"xv_size";s:2:"50";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            array('label',       1,  182,    0,  'a:5:{s:13:"xv_min_length";s:1:"0";s:13:"xv_max_length";s:3:"254";s:7:"xv_size";s:2:"50";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            array('moduleid',   1,  182,    0,  'a:3:{s:7:"xv_size";s:1:"1";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            array('itemtype',   1,  182,    0,  'a:4:{s:11:"xv_itemtype";s:1:"0";s:7:"xv_size";s:1:"0";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            array('urlparam',   1,  182,    0,  'a:5:{s:13:"xv_min_length";s:1:"0";s:13:"xv_max_length";s:2:"30";s:7:"xv_size";s:2:"50";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            //properties now
                            array('name',       2,  182,    1,  'a:5:{s:13:"xv_min_length";s:1:"1";s:13:"xv_max_length";s:2:"30";s:7:"xv_size";s:2:"50";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            array('label',      2,  182,    1,  'a:5:{s:13:"xv_min_length";s:1:"0";s:13:"xv_max_length";s:3:"254";s:7:"xv_size";s:2:"50";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            array('itemtype',   2,  182,    1,  'a:4:{s:11:"xv_itemtype";s:1:"0";s:7:"xv_size";s:1:"0";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}'),
                            //validation should be done earlier
                            array('validation',  2,  182,    1,  'a:3:{s:7:"xv_size";s:2:"50";s:13:"xv_allowempty";s:1:"1";s:17:"xv_display_layout";s:7:"default";}')

                            );

       foreach($updatearray as $updateitem)
        {
             $fixquery = "UPDATE $props
                             SET xar_prop_validation= ?
                             WHERE xar_prop_name = ? and xar_prop_objectid=? and xar_prop_moduleid = ? and xar_prop_itemtype = ?";
                $bindvars = array((string)$updateitem[4], (string)$updateitem[0],(int)$updateitem[1], (int)$updateitem[2], (int)$updateitem[3]);
            try {
                $doupdate = $dbconn->Execute($fixquery,$bindvars);

            } catch (Exception$e) {
                $data['success'] = false;
                $data['reply'] = xarML("Update problem");
                return $data ;
            }
        }

    } catch (Exception $e) {
        $data['success'] = false;
        $data['reply'] = xarML("Update Failed!");
    }
    return $data;
}
?>