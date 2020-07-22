<?php

function fix_141_01()
{
    // Define parameters
    $prefix =  xarDB::getSiteTablePrefix() ;
    $themetable = $prefix. '_themes';
    $dbconn = xarDB::getConn();

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding Default theme to the theme table and activating");
    $data['reply'] = xarML("Done!");
    $rowcount = 0;
    try {
        //let us see if the theme is there
         $query = "SELECT xar_id,xar_name,xar_state
                  FROM $themetable   WHERE xar_name = ? ";

              $result = $dbconn->Execute($query,array('default' ));
            if (!$result) {
                $data['success'] = false;
                $data['reply'] = xarML("Bad test!");
                $data['test'] =$data['test'] && false;
            }

        $rowcount = $result-> RowCount();

        if ($rowcount > 0) { //the theme is there - check it is active
            //Update state in any case - Note this does not add the theme vars
             $query1 = "UPDATE $themetable
                      SET xar_state = ? WHERE xar_regid = ?";
             $result = $dbconn->Execute($query1, array(3,1105));

        } else { //the theme is not there
                //we have to add and activate it

            $seqId = $dbconn->GenId($themetable);
            $query = "INSERT INTO $themetable
                    (xar_id, xar_name, xar_regid, xar_directory, xar_mode,xar_author, xar_homepage, xar_email,
              xar_description, xar_contactinfo, xar_publishdate, xar_license, xar_version, xar_xaraya_version, xar_bl_version, xar_class, xar_state)
              VALUES (?, 'default', 1105, 'default', '1','Xarigami Team', 'http://xarigami.org','http://xarigami.com',
              'Default System Theme for Xarigami Framework','http://xarigami.com', '08/03/2012', 'GPL','2.0.0','1.0','1.0',0,3)";
              $result = $dbconn->Execute($query,array($seqId));
         }
    } catch (Exception $e) {
        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>