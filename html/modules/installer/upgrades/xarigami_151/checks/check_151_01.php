<?php

function check_151_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking session table is updated for IPV6 and hashed sessions.");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDBGetConn();
    $updaterequired = FALSE;
    try {
        $prefix = xarDBGetSiteTablePrefix();
        $sessionsTable = $prefix.'_session_info';
        $indexname = 'xar_sessid';
        $colcheck  = array('xar_sessid' => 254,
                           'xar_ipaddr' => 40
                            );
        //check for session index the correct length
        $hasindex = $dbconn->MetaIndexes($sessionsTable);
        $metacolumns = $dbconn->MetaColumns($sessionsTable,TRUE);

        if (!empty($metacolumns)) {
           //check to see if the size is correct
            foreach ($colcheck  as $checkname=>$length) { //for session and ip cols
                foreach ($metacolumns as $metacol) {
                    if (strtolower($metacol->name) == strtolower($checkname))  {

                        if ((int)$metacol->max_length != (int)$length) { //test for length
                            //incorrect length
                            $updaterequired = TRUE;
                            break 2; //we have to do an update
                        }
                    }
                }

            }
        }
        //what about the index - is it the correct length?
        //probably not - we can remove and remake it in any case

        if ($updaterequired === TRUE) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Test Failed!").$e->getMessage();;
        $data['test'] =$data['test'] && false;
    }
    return $data;
}

?>