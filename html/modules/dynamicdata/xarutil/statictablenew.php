<?php
/* Create a newstatic table
 *
 * @copyright (C) 2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

function dynamicdata_util_statictablenew($args)
{
    if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarVarFetch('table',      'str:1',  $table,    '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('newtable',   'pre:trim:lower',  $newtable, '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('confirm',    'bool',   $confirm, false,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('db',         'str:1',  $db,    '',        XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('returnurl',  'str:0:254',  $returnurl,'', XARVAR_NOT_REQUIRED)) return;

    $data = array();
     //is this demo mode? too dangerous to have this available in demo mode
    $opmode = xarSystemVars::get(sys::CONFIG, 'Operation.Mode',true);
    $data['opmode'] = $opmode;
    if ($opmode == 'demo') {
         $data['demomsg'] = xarML('DEMO MODE: The table operations functions are disabled in this demo operation mode.');
    } else {
        $data['demomsg'] = '';
    }
    $data['table']= $table;
    $data['newtable']= $newtable;
    $data['db']= $db;
    $data['authid'] = xarSecGenAuthKey();
    $data['returnurl'] = $returnurl;
    $data['cancelurl'] = xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$table));
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    if (isset($confirm) && $confirm==TRUE) {
        //check if default connection
        $defaultdb = xarDBGetName();
        if ($defaultdb !=$db) {
            $dbconn = xarDBNewConn(array('databaseName'=>$db));
        } else {
            $dbconn = xarDBNewConn();
        }

        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        if (empty($newtable)) {
            $msg = xarML('You must provide a valid table name');
            throw new BadParameterException(null,$msg);
        }

        //check table name - lowercase, no spaces
        $newtable = strtolower($newtable);
        $newtable = str_replace(' ','_',$newtable);
        $newtable = $newtable;
        $datadict = xarDB::newDataDict($dbconn, 'ALTERTABLE');
        $fields = "xar_id  I AUTO PRIMARY";
        $result = $datadict->createTable($newtable,$fields);
        //set the default back

        if (!$result) {
            $msg = xarML('There was a problem creating table #(1)',$newtable);
            throw new BadParameterException(null,$msg);
            xarDBGetDefault();
            return;
        }
         $msg = xarML('A new table with name "#(1)" was successfully created.', $newtable);
                 xarTplSetMessage($msg,'status');
        $returnurl = !empty($returnurl) ? $returnurl: xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$db.'.'.$newtable));
        xarResponseRedirect($returnurl);
        xarDBGetDefault();
        return true;

    }
     xarTpl::setAdminTheme('dynamicdata');
    return $data;
}
?>