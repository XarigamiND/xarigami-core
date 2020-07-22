<?php
/* Rename a static table
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */


function dynamicdata_util_statictablerename()
{
    if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarVarFetch('table',      'str:1',  $table,    '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('newtable',   'pre:trim:lower',      $newtable, '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('confirm',    'bool',   $confirm, false,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('db',         'str:1',  $db,    '',        XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('returnurl',  'str:0:254',  $returnurl,'', XARVAR_NOT_REQUIRED)) return;

    $data = array();

    $data['table']= $table;
    $data['newtable']= $newtable;



    $data['db']= $db;
    $data['returnurl']= $returnurl;
    $data['authid'] = xarSecGenAuthKey();
    $data['cancelurl'] = xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$table));
    //check to see if db is appended to original table name
    $shorttable= '';
    if (!empty($table)) {
       $dbdata = explode('.',$table);

        if (count($dbdata) > 1) {
            $db = $dbdata[0];
            $shorttable = $dbdata[1];
        }
    }
    $data['shorttable'] = $shorttable;
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    if (isset($confirm)&& $confirm==TRUE) {
        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        if (empty($newtable)) {
            xarResponseRedirect($data['cancelurl']);
            return;
        }
        $defaultdb = xarDBGetName();
        if (empty($db)) $db = $defaultdb;

        if ($defaultdb != $db) {
            $dbconn = xarDBNewConn(array('databaseName'=>$db));
        } else {
            $dbconn = xarDBNewConn();
        }
        $table = $shorttable;

        //check table name - lowercase, no spaces
        $newtable = strtolower($newtable);
        $newtable = str_replace(' ','_',$newtable);
        $datadict = xarDB::newDataDict($dbconn, 'ALTERTABLE');
        $result = $datadict->renameTable($table,$newtable);
        if (!$result) {
            $msg = xarML('There was a problem renaming table #(1) to #(2)',$table,$newtable);
            throw new BadParameterException(null,$msg);
        }
        $returnurl = !empty($returnurl) ? $returnurl: xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$db.'.'.$newtable));
        xarResponseRedirect($returnurl);
        xarDBSetDefault();
        return true;
    }
     xarTpl::setAdminTheme('dynamicdata');

    return $data;
}

?>