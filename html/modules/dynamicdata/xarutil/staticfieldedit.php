<?php
/* Edit a static field
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
 */

function dynamicdata_util_staticfieldedit()
{
    if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarVarFetch('table',      'str:1:',  $table,    '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('confirm',    'bool',   $confirm, false,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('db',         'str:1:',  $db,    '',        XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('returnurl',  'str:0:254',  $returnurl,'', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('fname',      'str:1:',  $fname,    '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('oldcolumn',    'str:1:',  $oldcolumn,  '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('ftype',      'str:1:',  $ftype,    '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fattributes','str:0:',  $fattributes, '',   XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fnull',      'str:0:',  $fnull,     '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fdefault',   'str:0:',  $fdefault,  '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('fother',   'str:0:',  $fother,  '',     XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('field',      'str:1:',  $field,    '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('pkey',      'str:0:',  $pkey,    '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('scale',      'str:0:',  $scale,    '',      XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('maxlength',      'str:0:',  $maxlength,    '',      XARVAR_GET_OR_POST)) return;

    $data = array();

    if (!isset($field)) {
        return;
    }

    $data['table']= $table;

    $data['authid'] = xarSecGenAuthKey();
    $data['returnurl'] = $returnurl;
    $data['cancelurl'] = xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$table));
    $data['field']= $field;
    $data['fname'] = $fname;
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');

    $defaultdb = xarDBGetName();
    if ($defaultdb !=$db) {
        $dbconn = xarDB::getConn(array('databaseName'=>$db));
    } else {
        $dbconn = xarDB::$dbconn;
    }

    $dbdata = explode('.',$table);

    if (count($dbdata) > 1) {
        $db = $dbdata[0];
        $table = $dbdata[1];
    }
    $data['db']= $db;
    $dbtype = xarDB::getType();
    if ($dbtype == 'sqlite') {
         $data['msg'] = xarML('There is no SQLITE support for renaming of Columns');
            return $data;

    }
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

        $ftypeext = $ftype;
        if (($ftype == 'numeric' or $ftype =='N') &&  ($maxlength>0)){
            $ftypeext = empty($scale)?"$ftype($maxlength)":"$ftype($maxlength.$scale)";
        }
        $pkey = !empty($pkey)? $pkey :'';
               //now the rest

        if (!empty($table)) {
            $dbdata = explode('.',$table);
            if (count($dbdata) > 1) {
                $db = $dbdata[0];
                $table = $dbdata[1];
            }
        }
        //get rid of the table prefix if any
        $oldcolumn= str_replace($table.'.','',$oldcolumn);

        $datadict = xarDBNewDataDict($dbconn, 'ALTERTABLE');
        //change the field
        $fieldstring = "$oldcolumn $ftypeext $fattributes $fother $default $fnull";// $fother $pkey";
        $result = $datadict->alterColumn($table,$fieldstring);
        //what if name is changed
        if (trim($fname) != $oldcolumn) {
            //some dbs require field def
            $result = $datadict->renameColumn($table,$oldcolumn,$fname, $fieldstring);
        }
        if (!$result) {
            $msg = xarML('There was a problem updating column #(1)',$field);
            throw new BadParameterException(null,$msg);
        }
        $returnurl = !empty($returnurl) ? $returnurl: xarModURL('dynamicdata','util','meta',array('db'=>$db,'table'=>$db.'.'.$table));
        xarResponseRedirect($returnurl);
        xarDBSetDefault();
        return true;

    } else {
    //let's get the info we know about the field
        //check field name doesn't have the table name appended
        $fielddata = explode('.',$field);
        if (empty($fname)) {
            $fname = count($fielddata) >2?$fielddata[2]: (count($fielddata) >1?$fielddata[1]:$fielddata[0] );
        }

        $data['fname']= $fname;
        $data['oldcolumn'] = $fname;


        $datadict = xarDBNewDataDict($dbconn, 'ALTERTABLE');
        // Note: this only works if we use the same database connection
        $columns = $datadict->getColumns($table);

        $fielddata = array();
        foreach ($columns as $k=>$v) {
            if ($v->name == $fname) {
                $fielddata = $v;
            }
        }

        //jojo - cludgy, must be a better way. This handles some stuff
        $data['name']= $fname;
        $data['attributes']= isset($fielddata->unsigned) && $fielddata->unsigned ==1 ? 'UNSIGNED':'';
        $data['null']= isset($fielddata->not_null) && $fielddata->not_null ==1 ? 'NOTNULL':'NULL';

        $data['default']= isset($fielddata->has_default) && isset($fielddata->default_value) ? "$fielddata->default_value":'';
        $data['other']= isset($fielddata->auto_increment) && $fielddata->auto_increment ==1 ? 'AUTO':'';

        $data['maxlength']= isset($fielddata->max_length) && !empty($fielddata->max_length) ? $fielddata->max_length:'';
        $data['scale']= isset($fielddata->scale) && !empty($fielddata->scale) ? $fielddata->scale:'';
        $data['pkey']= isset($fielddata->primary_key) && !empty($fielddata->primary_key) ? $fielddata->primary_key:'';

        $nativetype = strtolower($fielddata->type);
         switch (trim($nativetype)) {
                    case 'string':
                    case 'tinyblob':
                    case 'tinytext':
                    case 'enum':
                    case 'set':
                        $type = 'C';break;
                    case 'char':
                        $type = 'C(64)';break;
                    case 'varchar':
                        $type = isset($data['maxlength']) && ($data['maxlength']>0) ? 'C('.$data['maxlength'].')': 'C(64)'; break;
                    case 'text':
                         $type = 'X';break;
                    case 'longtext':
                        $type = 'XL'; break;
                    case 'bytea':
                    case 'mediumblob':
                    case 'blob':
                    case 'longblob':
                        $type = 'B';   break;
                    case 'year':
                    case 'date':
                        $type = 'D'; break;
                    case 'datetime':
                    case 'time':
                    case 'timestamp':
                        $type = 'T'; break;
                    case 'tinyint':
                        $type='I1';break;
                    case 'smallint':
                         $type='I2';break;
                    case 'mediumint':
                    case 'int':
                    case 'int4':
                          $type='I4';break;
                    case 'int8':
                    case 'bigint':
                        $type='I8';break;
                    case 'float':
                    case 'double':
                        $type='F';break;
                    case 'decimal':
                        $type='N';break;
                   }

        $data['type']= $type;
        $data['nativetype'] = $nativetype;
        //prepare field values and defaults
        $data['ftypelist'] = array(
                        'C(64)' => 'string',
                        'C(254)'=> 'long string',
                        'C2'    => 'varchar',
                        'X'     => 'text',
                        'X2'    => 'longtext',
                        'XL'    => 'large text',
                        'B'     => 'longblob',
                        'D'     => 'date',
                        'T'     => 'datetime',
                        'I1'     =>'tinyint',
                        'I2'    => 'smallint',
                        'I4'    => 'integer',
                        'I8'    => 'bigint',
                        'F'     => 'float',
                        'N'     => 'numeric',
                        );
        $data['nulloptions'] = array('NULL'=>'Null','NOTNULL'=> 'Not Null');
        $data['attriboptions']= array(''=>'','UNSIGNED'=>'Unsigned');
        $data['otheroptions'] = array('' =>'', 'AUTO'=> 'Autoincrement');

    }
     xarTpl::setAdminTheme('dynamicdata');
    return $data;
}
?>