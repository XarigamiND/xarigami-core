<?php
/* Delete a table field
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
 */

function dynamicdata_util_staticfielddelete()
{
     if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarVarFetch('table',      'str:1',  $table,    '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('field',      'str:1',  $field,    '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('confirm',    'bool',   $confirm, false,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('db',         'str:1',  $db,    '',        XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('returnurl',  'str:0:254',  $returnurl,'', XARVAR_NOT_REQUIRED)) return;


    $data = array();

    $data['table']= $table;
    $data['field']= $field;
    $data['db']= $db;
    $data['returnurl']= $returnurl;
    $data['authid'] = xarSecGenAuthKey();
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    $data['cancelurl'] = xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$table));
    $dbtype = xarDBGetType();
    if ($dbtype == 'sqlite') {
         $data['msg'] = xarML('There is no SQLITE support for dropping of Columns');
            return $data;

    }

    //this is not really valid grabbing the source, but for static tables ok for now
    //get rid of the database prefix if any
    $data['fielddisplay'] = str_replace($table.'.','',$field);

    if (isset($confirm)&& $confirm==TRUE) {
        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;


         $defaultdb = xarDBGetName();
        if ($defaultdb !=$db) {
            $dbconn = xarDB::getConn(array('databaseName'=>$db));
        } else {
            $dbconn = xarDB::$dbconn;
        }
        $datadict = xarDBNewDataDict($dbconn, 'ALTERTABLE');

        if (!empty($table)) {
            //let's see if the
            $dbdata = explode('.',$table);
            if (count($dbdata) > 1) {
                $db = $dbdata[0];
                $table = $dbdata[1];
            }
        }
        //get rid of the table prefix if any
        $data['fielddisplay'] = str_replace($table.'.','',$data['fielddisplay']);
        $result = $datadict->dropColumn($table, $data['fielddisplay'] );
        if (!$result) {
            $msg = xarML('There was a problem deleting column #(1)',$field);
            throw new BadParameterException(null,$msg);
        }
        $returnurl = !empty($returnurl) ? $returnurl: xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$db.'.'.$table));
        xarResponseRedirect($returnurl);
        xarDBSetDefault();
        return true;
    }
      xarTpl::setAdminTheme('dynamicdata');
    return $data;
}

?>