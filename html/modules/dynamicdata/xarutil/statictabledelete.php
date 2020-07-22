<?php
/* Delete a static table
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

function dynamicdata_util_statictabledelete($args)
{
    if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarVarFetch('table',      'str:1',  $table,    '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('confirm',    'bool',   $confirm, false,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('db',         'str:1',  $db,    '',        XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('returnurl',  'str:0:254',  $returnurl,'', XARVAR_NOT_REQUIRED)) return;

    extract($args);
    $data = array();

    $data['table']= $table;
    $data['db']= $db;
    $data['returnurl']= $returnurl;
    $data['authid'] = xarSecGenAuthKey();
    $data['cancelurl'] = xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$table));
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    if (isset($confirm)&& $confirm==TRUE) {
        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        $defaultdb = xarDBGetName();
        if ($defaultdb !=$db) {
            $dbconn = xarDBNewConn(array('databaseName'=>$db));
        } else {
            $dbconn = xarDBNewConn();
        }

        if (!empty($table)) {
           $dbdata = explode('.',$table);

            if (count($dbdata) > 1) {
                $db = $dbdata[0];
                $table = $dbdata[1];
            }
        }
        $datadict = xarDBNewDataDict($dbconn, 'ALTERTABLE');

        $result = $datadict->dropTable($table);

        $returnurl = !empty($returnurl) ? $returnurl: xarModURL('dynamicdata','util','meta',array('db'=>$db));
        xarResponseRedirect($returnurl);
        xarDBGetDefault();
        return true;
    }
    xarDBGetDefault();
     xarTpl::setAdminTheme('dynamicdata');
    return $data;
}

?>