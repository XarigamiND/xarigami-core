<?php
/**
 * Return meta data
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return meta data (test only)
 */
function dynamicdata_util_meta($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();
    if (!xarVarFetch('export', 'notempty', $export, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('table', 'notempty', $table, '', XARVAR_GET_OR_POST)) {return;}
    if (!xarVarFetch('showdb', 'notempty', $showdb, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('db', 'notempty', $db, '', XARVAR_GET_OR_POST)) {return;}

    extract($args);
    $data = array();

    //is this demo mode? too dangerous to have this available in demo mode
    $opmode = xarSystemVars::get(sys::CONFIG, 'Operation.Mode',true);
    $data['opmode'] = $opmode;
    if ($opmode == 'demo') {
         $data['demomsg'] = xarML('DEMO MODE: The table operations functions are disabled in this demo operation mode.');
    } else {
        $data['demomsg'] = '';
    }
    $defaultdb = xarDBGetDefault()->databaseName;
    if (empty($db)) $db = $defaultdb;

    if ($defaultdb != $db) {
        $dbconn = xarDBNewConn(array('databaseName'=>$db));
    } else {
        $dbconn = xarDB::getConn();
    }

    //check to see if we have a table with db name prefixed
    $shorttable= '';
    if (!empty($table)) {
        //let's see if the
        $dbdata = explode('.',$table);

        if (count($dbdata) > 1) {
            $db = $dbdata[0];
            $shorttable = $dbdata[1];
        }
    }
    $data['shorttable'] = $shorttable;


    $data['db'] = $db;
    //be explicit and sure we have the full table name
    $table = !empty($shorttable) ? $db.'.'.$shorttable: $table;

    // Note: this only works if we use the same database connection
    $data['databases'] = $dbconn->MetaDatabases();

    //get the databases and a drop down list
    if (empty($data['databases'])) {
        $data['databases'] = array($db);
    }
    $data['dblist'] = array();

    foreach ($data['databases'] as $id=>$dbname) {
        if ($dbname =='information_schema') continue;
        if ($dbname == $defaultdb) {
            $data['dblist'][$dbname] = $dbname.xarML(' (default)');
        } else {
            $data['dblist'][$dbname] = $dbname;
        }
    }

    //get the tables for the database
    $meta= xarMod::apiFunc('dynamicdata','util','getmeta',
                                    array('db' => $db));

    $data['tablelist']=array();

    foreach ($meta as $name=>$tabledata) {
        $name = ($db == $defaultdb)? $name:$name;//$db.'.'.$name; //default always retains name without db prefix
        $data['tablelist'][$name] = str_replace($db.'.','',$name);
    }

    //or just the table information if table name is provided
    $data['tables'] = array();
    //put fields in same format as default
    if (!empty($table)) {
        $tablefields= isset($meta[$table])? $meta[$table]: (isset($meta[$shorttable])? $meta[$shorttable]:'');
        foreach($tablefields as $fieldname=>$fielddata) {
            $test = explode('.',$fielddata['source']);
            if (is_array($test) && count($test) >2) {
                $fielddata['source'] =$test[1].'.'.$test[2];
            }
        }

        $data['tables'][$table] = $tablefields;
    }


    $data['table'] = $table;
    $data['export'] = $export;
    $data['prop'] = xarMod::apiFunc('dynamicdata','user','getproperty',array('type' => 'fieldtype', 'name' => 'dummy'));

     xarTpl::setAdminTheme('dynamicdata');
    xarCoreCache::setCached('dd.tableops','tabledata',array('db'=>$db,'table'=>$table));
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    //common images and text
    $data['dummyimage'] = xarTplGetImage('blank.gif','base');
    $data['deletetext'] = xarML('Delete field');
    $data['edittext'] = xarML('Edit field');

    return $data;
}

?>