<?php
/* Create a new static field
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
 */

function dynamicdata_util_staticfieldnew()
{
    if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarVarFetch('table',      'str:1',  $table,    '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('confirm',    'bool',   $confirm, false,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('db',         'str:1',  $db,    '',        XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('returnurl',  'str:0:254',  $returnurl,'', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('fname',      'str:1',  $fname,    '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('ftype',      'str:1',  $ftype,    '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fattributes','str:1',  $fattributes, '',   XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fnull',      'str:1',  $fnull,     '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fdefault',   'str:1',  $fdefault,  '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fother',   'str:1',  $fother,  '',     XARVAR_GET_OR_POST)) return;
    $data = array();
    $data['table']= $table;
    $data['db']= $db;
    $data['authid'] = xarSecGenAuthKey();
    $data['returnurl'] = $returnurl;
    $data['cancelurl'] = xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$table));

    $data['fname']= $fname;
    $data['ftype']= $ftype;
    $data['fattributes']= $fattributes;
    $data['fnull']= empty($fnull)?'NULL':$fnull;
    $data['fdefault']= $fdefault;
    $data['fother']= $fother;
    //prepare field values and defaults
    $data['ftypelist'] = array(
                        'C(64)' => 'string',
                        'C(254)'=> 'long string',
                        'X'     => 'text',
                        'XL'    => 'large text',
                        'B'     => 'blob',
                        'T'     => 'datetime',
                        'I1'     =>'tinyint',
                        'I2'    => 'smallint',
                        'I4'    => 'integer',
                        'I8'    => 'bigint',
                        'F'     => 'float',
                        'N'     => 'numeric'
                        );
    $data['nulloptions'] = array('NULL'=>'Null','NOTNULL'=> 'Not Null');
    $data['attriboptions']= array(''=>'','UNSIGNED'=>'Unsigned');
    $data['otheroptions'] = array('' =>'', 'AUTO'=> 'Autoincrement');
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    if (isset($confirm)&& $confirm==TRUE) {
        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        // Get the data from the form
        $test = array('I','I2','I4','I6','I8');
        if (!in_array($ftype, $test)) {
            $fattributes = '';
        }
        //check for default
        $default = '';
        if (isset($fdefault) && trim($fdefault)!='') {
            $default = "DEFAULT '$fdefault'";
        }
        $defaultdb = xarDBGetName();
        if ($defaultdb !=$db) {
            $dbconn = xarDBNewConn(array('databaseName'=>$db));
        } else {
            $dbconn = xarDBNewConn();
        }
        $dbdata = explode('.',$table);

        if (count($dbdata) > 1) {
            $db = $dbdata[0];
            $table = $dbdata[1];
        }

        //create the new field
        $datadict = xarDBNewDataDict($dbconn, 'ALTERTABLE');
        $fieldstring = "$fname $ftype $fattributes $default $fnull $fother";

        $result = $datadict->addColumn($table,$fieldstring);
        if (!$result) {
            $msg = xarML('There was a problem creating column #(1)',$field);
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