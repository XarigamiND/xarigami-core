<?php

function fix_131_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating state listing property items");
    $data['reply'] = xarML("Done!");
      $dbconn = xarDB::$dbconn;
   try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';
        //get statelist property type id
        $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_format
                   FROM $dynamicpropdefs
                   WHERE xar_prop_name = 'statelisting'
                   ";

        $result = $dbconn->Execute($query);
        $proplist = array();
        if (!$result->EOF) {
            list($propid, $propname, $propformat) = $result->fields;

            //get all properties defined with this propid
             $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source
                         FROM $dynamicprops
                       WHERE xar_prop_type = ?";
            $result = $dbconn->Execute($query,array($propid));

            $proplist = array();
            for (; !$result->EOF; $result->MoveNext()) {
            //we have properties to possibly update
                list ($propid,$propname, $propsource)  = $result->fields;
                    $proplist[] = array(
                                        'propid' => $propid,
                                        'propname' => $propname,
                                        'propsource' => $propsource
                                        );
            }
        }
        if (count($proplist) > 0) {
            foreach ($proplist as $prop=>$propdata) {
                try {
                    //get the value from the right place
                    if ($propdata['propsource'] =='dynamic_data') {
                        $query = "SELECT xar_dd_id, xar_dd_value
                                FROM $dynamicdata
                                WHERE xar_dd_propid = ?";
                        $resultset1 = $dbconn->Execute($query,$propdata['propid']);
                        while (!$resultset1->EOF) {
                            list($ddid, $oldcode) = $resultset1->fields;
                            //update it
                            $newcode = getcode($oldcode);
                            $pref = substr($oldcode,0,3);
                            if (in_array($pref,array('CA_','AU_','US_'))) {
                                //this has already been updated
                                $data['success'] = true;
                                $data['reply'] = xarML("Already done!");
                                break 2;
                            }
                            if ($newcode!='') {
                                $query = "UPDATE $dynamicdata
                                          SET xar_dd_value = ?
                                          WHERE xar_dd_id = ?";
                                $bindvars = array($newcode,  $ddid);
                                $result = $dbconn->Execute($query,$bindvars);
                            }
                            $resultset1->MoveNext();
                        }
                    } else {
                        $fielddata = explode('.',$propdata['propsource']);
                        if (count($fielddata)==2) {
                            $table = $fielddata[0];
                            $fieldname = $fielddata[1];
                                $query = "SELECT $fieldname
                                    FROM  $table";
                                    $resultset = $dbconn->Execute($query);
                             //get the field values
                             $items = array();
                             while (!$resultset->EOF) {
                             list($oldvalue) = $resultset->fields;
                                //update it
                                //problem is we don't know the id of the field
                                //if we update we update the whole table for that value 0.o
                                //so - we will eventually get to new values
                                $newcode = getcode($oldvalue);
                                $pref = substr($oldcode,0,3);
                                if (in_array($pref,array('CA_','AU_','US_'))) {
                                    //this has already been updated
                                    $data['success'] = true;
                                    $data['reply'] = xarML("Already done!");
                                    break 2;
                                }
                                if (!empty($newcode)) {
                                    $query = "UPDATE $table
                                              SET  $fieldname = ?
                                              WHERE $fieldname = ?";
                                    $bindvars = array($newcode, $oldvalue);
                                    $result = $dbconn->Execute($query,$bindvars);
                                }
                                $resultset->MoveNext();
                            }
                        }
                    }
                } catch (Exception $e) {
                    $data['success'] = false;
                    $data['reply'] = xarML("Failed!").$e->getMessage();
                }
            }

        }
    $result->close();
   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }
    return $data;
}
function getcode($oldcode)
{
    $newvalue = '';
    //xaraya 1x is not using abbreviations, xarigami is
    $soptions['US-AL'] = array('id' =>'AL', 'name' =>'Alabama');
    $soptions['US-AK'] = array('id' =>'AK', 'name' =>'Alaska');
    $soptions['US-AS'] = array('id' =>'AS', 'name' =>'American Samoa');
    $soptions['US-AZ'] = array('id' =>'AZ', 'name' =>'Arizona');
    $soptions['US-AR'] = array('id' =>'AR', 'name' =>'Arkansas');
    $soptions['US-CA'] = array('id' =>'CA', 'name' =>'California');
    $soptions['US-CO'] = array('id' =>'CO', 'name' =>'Colorado');
    $soptions['US-CT'] = array('id' =>'CT', 'name' =>'Connecticut');
    $soptions['US-DE'] = array('id' =>'DE', 'name' =>'Delaware');
    $soptions['US-DC'] = array('id' =>'DC', 'name' =>'District of Columbia');
    $soptions['US-FL'] = array('id' =>'FL', 'name' =>'Florida');
    $soptions['US-GA'] = array('id' =>'GA', 'name' =>'Georgia');
    $soptions['US-GU'] = array('id' =>'GU', 'name' =>'Guam');
    $soptions['US-HI'] = array('id' =>'HI', 'name' =>'Hawaii');
    $soptions['US-ID'] = array('id' =>'ID', 'name' =>'Idaho');
    $soptions['US-IL'] = array('id' =>'IL', 'name' =>'Illinois');
    $soptions['US-IN'] = array('id' =>'IN', 'name' =>'Indiana');
    $soptions['US-IA'] = array('id' =>'IA', 'name' =>'Iowa');
    $soptions['US-KS'] = array('id' =>'KS', 'name' =>'Kansas');
    $soptions['US-KY'] = array('id' =>'KY', 'name' =>'Kentucky');
    $soptions['US-LA'] = array('id' =>'LA', 'name' =>'Louisiana');
    $soptions['US-ME'] = array('id' =>'ME', 'name' =>'Maine');
    $soptions['US-MH'] = array('id' =>'MH', 'name' =>'Marshall Islands');
    $soptions['US-MD'] = array('id' =>'MD', 'name' =>'Maryland');
    $soptions['US-MA'] = array('id' =>'MA', 'name' =>'Massachusetts');
    $soptions['US-MI'] = array('id' =>'MI', 'name' =>'Michigan');
    $soptions['US-MN'] = array('id' =>'MN', 'name' =>'Minnesota');
    $soptions['US-MS'] = array('id' =>'MS', 'name' =>'Mississippi');
    $soptions['US-MO'] = array('id' =>'MO', 'name' =>'Missouri');
    $soptions['US-MT'] = array('id' =>'MT', 'name' =>'Montana');
    $soptions['US-NE'] = array('id' =>'NE', 'name' =>'Nebraska');
    $soptions['US-NV'] = array('id' =>'NV', 'name' =>'Nevada');
    $soptions['US-NH'] = array('id' =>'NH', 'name' =>'New Hampshire');
    $soptions['US-NJ'] = array('id' =>'NJ', 'name' =>'New Jersey');
    $soptions['US-NM'] = array('id' =>'NM', 'name' =>'New Mexico');
    $soptions['US-NY'] = array('id' =>'NY', 'name' =>'New York');
    $soptions['US-NC'] = array('id' =>'NC', 'name' =>'North Carolina');
    $soptions['US-ND'] = array('id' =>'ND', 'name' =>'North Dakota');
    $soptions['US-MP'] = array('id' =>'MP', 'name' =>'Nortnern Mariana Islands');
    $soptions['US-OH'] = array('id' =>'OH', 'name' =>'Ohio');
    $soptions['US-OK'] = array('id' =>'OK', 'name' =>'Oklahoma');
    $soptions['US-OR'] = array('id' =>'OR', 'name' =>'Oregon');
    $soptions['US-PA'] = array('id' =>'PA', 'name' =>'Pennsylvania');
    $soptions['US-PR'] = array('id' =>'PR', 'name' =>'Puerto Rico');
    $soptions['US-RI'] = array('id' =>'RI', 'name' =>'Rhode Island');
    $soptions['US-SC'] = array('id' =>'SC', 'name' =>'South Carolina');
    $soptions['US-SD'] = array('id' =>'SD', 'name' =>'South Dakota');
    $soptions['US-TN'] = array('id' =>'TN', 'name' =>'Tennessee');
    $soptions['US-TX'] = array('id' =>'TX', 'name' =>'Texas');
    $soptions['US-UT'] = array('id' =>'UT', 'name' =>'Utah');
    $soptions['US-VT'] = array('id' =>'VT', 'name' =>'Vermont');
    $soptions['US-VI'] = array('id' =>'VI', 'name' =>'Virginia');
    $soptions['US-WA'] = array('id' =>'WA', 'name' =>'Washington');
    $soptions['US-WV'] = array('id' =>'WV', 'name' =>'West Virginia');
    $soptions['US-WI'] = array('id' =>'WI', 'name' =>'Wisconsin');
    $soptions['US-WY'] = array('id' =>'WY', 'name' =>'Wyoming');
    $soptions['CA-AB'] = array('id' =>'AB', 'name' =>'Alberta');
    $soptions['CA-BC'] = array('id' =>'BC', 'name' =>'British Columbia');
    $soptions['CA-MB'] = array('id' =>'MB', 'name' =>'Manitoba');
    $soptions['CA-NB'] = array('id' =>'NB', 'name' =>'New Brunswick');
    $soptions['CA-NL'] = array('id' =>'NL', 'name' =>'Newfoundland and Labrador');
    $soptions['CA-NT'] = array('id' =>'NT', 'name' =>'Northwest Territories');
    $soptions['CA-NS'] = array('id' =>'NS', 'name' =>'Nova Scotia');
    $soptions['CA-NU'] = array('id' =>'NU', 'name' =>'Nunavut');
    $soptions['CA-ON'] = array('id' =>'ON', 'name' =>'Ontario');
    $soptions['CA-PE'] = array('id' =>'PE', 'name' =>'Prince Edward Island');
    $soptions['CA-QC'] = array('id' =>'QC', 'name' =>'Quebec');
    $soptions['CA-SK'] = array('id' =>'SK', 'name' =>'Saskatchewan');
    $soptions['CA-YT'] = array('id' =>'YT', 'name' =>'Yukon Territory');
    $soptions['AU-ACT'] = array('id' =>'ACT', 'name' =>'Australian Capital Territory');
    $soptions['AU-NSW'] = array('id' =>'NSW', 'name' =>'New South Wales');
    $soptions['AU-NT'] = array('id' =>'NT', 'name' =>'Northern Territory');
    $soptions['AU-QLD'] = array('id' =>'QLD', 'name' =>'Queensland');
    $soptions['AU-SA'] = array('id' =>'SA', 'name' =>'South Australia');
    $soptions['AU-TAS'] = array('id' =>'TAS', 'name' =>'Tasmania');
    $soptions['AU-WVIC'] = array('id' =>'VIC', 'name' =>'Victoria');
    $soptions['AU-WA'] = array('id' =>'WA ', 'name' =>'Western Australia');
    $soptions['Other'] = array('id' =>'Other', 'name' =>'Other');

    foreach ($soptions as $newoption=>$oldvalues) {
        if (($oldcode == $oldvalues['id'] || $oldcode ==$oldvalues['name'])) {
            $newvalue = $newoption;
            break;
        }
    }
    return $newvalue;

}

?>