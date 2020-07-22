<?php

function check_131_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for state updated listing property items");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get statelist property type id
        $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_format
                   FROM $dynamicpropdefs
                   WHERE xar_prop_name = 'statelisting'";

        $result = $dbconn->Execute($query);
        $proplist = array();
        if (!$result->EOF) {
            list($propid, $propname, $propformat) = $result->fields;
            //get all properties defined with this propid
             $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                        FROM $dynamicprops
                       WHERE xar_prop_type = ?";
            $result = $dbconn->Execute($query,array($propid));

            $proplist = array();
            for (; !$result->EOF; $result->MoveNext()) {
            //we have properties to possibly update
                list ($propid,$propname, $propsource, $propvalidation)  = $result->fields;
                    $proplist[] = array(
                                        'propid' => $propid,
                                        'propname' => $propname,
                                        'propsource' => $propsource,
                                        'propvalidation' => $propvalidation
                                        );
                //from verison 1.4.0 we have serialized validations for state property listings
                //if there are serialized values here, then we are done - the update is already completed
                try {
                    $check = @unserialize($propvalidation);
                } catch (Exception $e) {
                    //do nothing
                }

                $serialized =  ($check===false && $propvalidation != serialize(false)) ? false : true;
                if ($serialized) {
                    //this is a recent install and no upgrade required
                     $data['success'] = true;
                      $data['reply'] = xarML("Tested OK!");
                    $data['test'] =true;
                    return $data;
                }
            }

            if (count($proplist) > 0) {
                  //we may have properties to update  - need to check for data
                foreach ($proplist as $prop=>$propdata) {
                    try {
                        //let's check to see if we can get some data
                        if ($propdata['propsource'] =='dynamic_data') {
                            $query = "SELECT xar_dd_id, xar_dd_value
                                    FROM $dynamicdata
                                    WHERE xar_dd_propid = ?";
                            $resultset = $dbconn->Execute($query,$propdata['propid']);
                            while (!$resultset->EOF) {
                                list($ddid, $oldcode) = $resultset->fields;
                                //check if it is old values or new values
                                //old values have no hypen in it (except for 'other' value).
                                if ($oldcode != 'other') {
                                    $alreadyupdated = strpos($oldcode, '-');

                                }
                                //doesnt matter if there is one or more - if there is one we need updating
                                if (!$alreadyupdated) {
                                    $data['success'] = true;
                                    $data['reply'] = xarML("Not done");
                                    $data['test'] =$data['test'] && false;
                                    break 2;
                                }
                                $resultset->MoveNext();
                            }
                        } else {
                            //check other souces
                            $fielddata = explode('.',$propdata['propsource']);
                            if (count($fielddata)==2) {
                                $table = $fielddata[0];
                                $fieldname = $fielddata[1];
                                    $query = "SELECT $fieldname
                                        FROM  $table";
                                        $resultset2 = $dbconn->Execute($query);
                                 //get the field values
                                 $items = array();
                                 while (!$resultset2->EOF) {
                                    list($oldvalue) = $resultset2->fields;
                                    //check if it is old values or new values
                                   //old values have no hypen in it (except for 'other' value).
                                    if ($oldvalue != 'other') {
                                        $alreadyupdated = strpos($oldvalue, '-');
                                    }
                                    //doesnt matter if there is one or more - if there is one we need updating
                                    if (!$alreadyupdated) {
                                        $data['success'] = true;
                                        $data['reply'] = xarML("Not done");
                                        $data['test'] =$data['test'] && false;
                                        break 2;
                                    }
                                    $resultset2->MoveNext();
                               }
                            }
                        }
                    } catch (Exception $e) {
                        $data['success'] = false;
                       $data['reply'] = xarML("Test failed");
                        $data['test'] =$data['test'] && false;
                    }
                } //foreach in proplist
            } //end if proplist > 0
        }


   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Test Failed!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}

?>