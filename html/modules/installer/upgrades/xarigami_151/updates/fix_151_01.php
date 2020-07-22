<?php

function fix_151_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating session table for IPV6 and hashed sessions.");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDBGetConn();
        xarDBLoadTableMaintenanceAPI();

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
        //it's going to be easier to create a new table and read in the old data
        $sessiontemptable = $prefix  . '_session_info_temp';
        $fields = array(
            'xar_sessid'       => array('type'=>'varchar','size'=>254,'null'=>false,'primary_key'=>true),
            'xar_ipaddr'       => array('type'=>'varchar','size'=>40,'null'=>false),
            'xar_firstused'    => array('type'=>'integer','null'=>false,'default'=>'0'),
            'xar_lastused'     => array('type'=>'integer','null'=>false,'default'=>'0'),
            'xar_uid'          => array('type'=>'integer','null'=>false,'default'=>'0'),
            'xar_vars'         => array('type'=>'blob'),
            'xar_remembersess' => array('type'=>'integer','size'=>'tiny','default'=>'0')
            );
            $query = xarDBCreateTable($sessiontemptable,$fields);

            $result = $dbconn->Execute($query);

            //create the new indexes
             $index = array('name'   => 'i_'.$prefix .'_session_uid',
                   'fields' => array('xar_uid'),
                   'unique' => false);
                    $query = xarDBCreateIndex( $sessiontemptable,$index);
                    $result = $dbconn->Execute($query);
                    if(!$result) return;

             $index = array('name'   => 'i_'.$prefix .'_session_lastused',
                           'fields' => array('xar_lastused'),
                           'unique' => false);
                   $query = xarDBCreateIndex( $sessiontemptable,$index);
                    $result = $dbconn->Execute($query);

            //now we need to read in all the info from the old table
            $copyquery= "SELECT *
                        FROM $sessionsTable";
            $resultlist = $dbconn->Execute($copyquery);
            for (; !$resultlist->EOF; $resultlist->MoveNext()) {
               $bindvars =   list($sessid, $ipaddr, $firstused, $lastused, $uid, $vars, $remember) = $resultlist->fields;
                    $bindvars = array($sessid, $ipaddr, $firstused, $lastused,(int)$uid, $vars, (int) $remember);
                    $docopy = "INSERT INTO $sessiontemptable (
                                      xar_sessid,
                                      xar_ipaddr,
                                      xar_firstused,
                                      xar_lastused,
                                      xar_uid,
                                      xar_vars,
                                      xar_remembersess)
                                    VALUES (?,?,?,?,?,?,?)";
                    $doupdate = $dbconn->Execute($docopy,$bindvars);
            }

            //now drop the old session table
            $dropsession= xarDBDropTable($sessionsTable);
            $result = $dbconn->Execute($dropsession);
            //now rename the temp table
            $renamequery = xarDBAlterTable( $sessiontemptable,
                                  array('command' => 'rename',
                                        'new_name'   => $prefix  . '_session_info'
                                        ));
            $result = $dbconn->Execute($renamequery);
   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }
    return $data;
}
?>