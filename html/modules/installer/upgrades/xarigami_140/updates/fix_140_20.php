<?php
//Updating Upload configuration

function fix_140_20()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating upload property configurations");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get all properties defined with this proptype/format of  105 (Uploads)
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                      WHERE  xar_prop_type =  105";
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
                $defaultval = array ('xv_basedir'   =>'var/uploads',
                                     'xv_importdir' => '',
                                      'xv_display'  => FALSE,
                                      'xv_methods'  => array('upload'),
                                      'xv_multiple' => 1,
                                     );
                //initialize
                $basedir = '';
                $display = 0;
                $multiple = 1;
                $importdir = '';
                $methods = array();
                $sep = ';';
                if(is_string($propvalidation) && strchr($propvalidation,$sep)) {
                    $conf = explode($sep,$propvalidation);
                    foreach ($conf as $item) {
                        $item = trim($item);
                        $check = strtolower(substr($item, 0, 6));

                        if ('single' == $check) {
                            $multiple = 0;
                        } elseif ('basedi' == $check) {
                            if (preg_match('/^basedir\((.+)\)$/i', $item, $matches)) {
                                $basedir = $matches[1];
                            }
                        } elseif ('import' == $check) {
                            if (preg_match('/^importdir\((.+)\)$/i', $item, $matches)) {
                                $importdir = $matches[1];
                            }
                        } elseif ('method' == $check) {
                            $item = strtolower($item);
                            if (stristr($item, 'methods')) {
                                // if it's the methods, then let's set them up
                                preg_match('/^methods\(([^)]*)\)$/i', $item, $parts);

                                // if any methods were specified, then we should have at -least-
                                // two parts here - otherwise, there will be just the whole item
                                // if no methods were specified, use the defaults.
                                if (count($parts) <= 1) {
                                    continue;
                                } elseif (count($parts) == 2) {
                                    // reset the methods to nothing
                                    // and add only the ones specified
                                    $list = explode(',', $parts[1]);
                                    foreach ($list as $method) {
                                        $method = trim(strtolower($method));

                                        // grab the modifier if there was one
                                        preg_match('/^(\-|\+)?([a-z0-9_-]*)/i', $method, $matches);
                                        list($full, $modifier, $method) = $matches;
                                        // If modifier == '-' then we are specifically
                                        // turning off this file import method,
                                        // otherwise, leave it as on
                                        if (!empty($modifier) && $modifier == '-') {
                                            $modifier = (int) FALSE;
                                        } else {
                                            $modifier = (int) TRUE;
                                        }

                                        switch ($method) {
                                            case 'upload':
                                            case 'uploads':
                                                $methods['upload'] = $modifier;
                                                break;
                                            case 'external':
                                            case 'extern':
                                                $methods['external'] = $modifier;
                                                break;
                                            case 'trusted':
                                            case 'trust':
                                                $methods['trusted'] = $modifier;
                                                break;
                                            case 'stored':
                                            case 'store':
                                                $methods['stored'] = $modifier;
                                                break;
                                            default:

                                        }
                                    }
                                }

                            }
                        } elseif ('1' == $check) {
                            $item = (int)$item;
                            $display = ($item ==1) ? TRUE :FALSE;
                        }
                    }//end for
                    $defaultval['xv_basedir'] = $basedir;
                    $defaultval['xv_methods'] = $methods;
                    $defaultval['xv_importdir'] = $importdir;
                    $defaultval['xv_multiple'] = $multiple;
                    $defaultval['xv_display'] = $display;

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