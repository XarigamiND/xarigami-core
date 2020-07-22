<?php
/**
 * Return static table information
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return static table information (test only)
 */
function dynamicdata_util_static($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if(!xarVarFetch('module',   'isset', $module,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('db', 'notempty', $db, '', XARVAR_NOT_REQUIRED)) {return;}

    if (!xarVarFetch('export', 'isset', $export,  NULL, XARVAR_DONT_SET)) {return;}

    extract($args);

    $data = array();
    if (empty($export)) {
        $export = 0;
    }
    if (!isset($db)) {
        $db = xarDBGetName();
    }
    $prefix = xarDB::$prefix;
    if (!isset($table)) $table = $prefix.'_dynamic_objects';

    $datadict = xarDBNewDataDict($dbconn);

    $meta = $datadict->getTables();

    $data['tablelist'] = array();
    foreach ($meta as $id=>$name) {
        $data['tablelist'][$name] = $name;
    }

    $defs = $datadict->getColumns($table);


    $data['menutitle'] = xarML('Dynamic Data Utilities');
    $data['table'] = $table;
    $static = xarMod::apiFunc('dynamicdata','util','getstatic',
                            array('module'   => $module,
                                  'modid'    => $modid,
                                  'itemtype' => $itemtype,
                                  'table'    => $table));

    if (!isset($static) || $static == false) {
        $data['tables'] = array();
    } else {
        $data['tables'] = array();
        foreach ($static as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tables'][$table][$field['name']] = $field;
            }
        }
    }

    $data['export'] = $export;
    $data['modid'] = $modid;
    if(!isset($modid) || $modid == 0) $modid = 182;
    $modInfo = xarMod::getInfo($modid);
    $data['module'] = $modInfo['name'];
    $data['itemtype'] = $itemtype;
    $data['authid'] = xarSecGenAuthKey();

    xarTpl::setAdminTheme('dynamicdata');
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    return $data;
}

?>