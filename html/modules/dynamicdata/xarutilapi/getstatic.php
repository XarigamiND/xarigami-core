<?php
/**
 * Get the "static" properties
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * (try to) get the "static" properties, corresponding to fields in dedicated
 * tables for this module + item type
// TODO: allow modules to specify their own properties
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of table you're looking for, or
 * @param $args['modid'] module id of table you're looking for
 * @param $args['itemtype'] item type of table you're looking for
 * @param $args['table']  table name of table you're looking for (better)
 * @return mixed value of the field, or false on failure
 * @throws BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_utilapi_getstatic($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarMod::getId($module);
    }
    if (empty($modid)) {
        $modid = xarMod::getId(xarMod::getName());
    }
    $modinfo = xarMod::getInfo($modid);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVarPrepForDisplay($modid);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'util', 'getstatic', 'DynamicData');
        throw new BadParameterException(null,$msg);
    }
    if (empty($table)) {
        $table = '';
    }
    if (isset($propertybag["$modid:$itemtype:$table"])) {
        return $propertybag["$modid:$itemtype:$table"];
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

// TODO: support site tables as well
    $systemPrefix = xarDB::$sysprefix;
    $metaTable = $systemPrefix . '_tables';

    if ($modinfo['name'] == 'dynamicdata') {
        // let's cheat a little for DD, because otherwise it won't find any tables :)
        if ($itemtype == 0) {
            $modinfo['name'] = 'dynamic_objects';
        } elseif ($itemtype == 1) {
            $modinfo['name'] = 'dynamic_properties';
        } elseif ($itemtype == 2) {
            $modinfo['name'] = 'dynamic_data';
        }
    }

    $bindvars = array();
    $query = "SELECT xar_tableid,
                     xar_table,
                     xar_field,
                     xar_type,
                     xar_size,
                     xar_default,
                     xar_increment,
                     xar_primary_key
              FROM $metaTable ";

    // it's easy if the table name is known
    if (!empty($table)) {
        $query .= " WHERE xar_table = ?";
        $bindvars[] =  $table;
    // otherwise try to get any table that starts with prefix_modulename
    } else {
        $query .= " WHERE xar_table LIKE ?";
        $bindvars[] = $systemPrefix . '_' . $modinfo['name'] . '%';
    }
    $query .= " ORDER BY xar_tableid ASC";

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    $static = array();

    // add the list of table + field
    $order = 1;
    while (!$result->EOF) {
        list($id,$table, $field, $datatype, $size, $default,$increment,$primary_key) = $result->fields;
    // TODO: what kind of security checks do we want/need here ?
        //if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
        //}

        // assign some default label for now, by removing the first part (xar_)
// TODO: let modules define this
        $name = preg_replace('/^.+?_/','',$field);
        $label = strtr($name,'_',' ');
        $label = ucwords($label);
        if (isset($static[$name])) {
            $i = 1;
            while (isset($static[$name . '_' . $i])) {
                $i++;
            }
            $name = $name . '_' . $i;
            $label = $label . '_' . $i;
        }

        $status = 1;

        // assign some default validation for now
// TODO: improve this based on property type validations
        $validation = $datatype;
        $validation .= empty($size) ? '' : ' (' . $size . ')';

        // (try to) assign some default property type for now
        // = obviously limited to basic data types in this case
        switch ($datatype) {
            case 'char':
            case 'varchar':
                $proptype = 2; // Text Box
                if (!empty($size)) {
                    $validation = "0:$size";
                }
                break;
            case 'integer':
            case 'smallint':
            case 'tinyint':
            case 'bigint':
            case 'integer':
            case 'year':            
                $proptype = 15; // Number Box
                break;
            case 'numeric':
            case 'decimal':
            case 'double':
            case 'float':
                $proptype = 17; // Number Box (float)
                break;
            case 'boolean':
                $proptype = 14; // Checkbox
                break;
            case 'date':
            case 'datetime':
            case 'timestamp':
                $proptype = 8; // Calendar
                break;
            case 'longvarchar':
            case 'text':
            case 'clob':
                $proptype = 4; // Medium Text Area
                $status = 2;
                break;
            case 'longvarbinary':
            case 'varbinary':
            case 'binary':
            case 'blob':       // caution, could be binary too !
                $proptype = 4; // Medium Text Area
                $status = 2;
                break;
            default:
                $proptype = 1; // Static Text
                break;
        }

        // try to figure out if it's the item id
// TODO: let modules define this
        if (!empty($increment) || !empty($primary_key)) {
            // not allowed to modify primary key !
            $proptype = 21; // Item ID
        }

        $static[$name] = array('name' => $name,
                               'label' => $label,
                               'type' => $proptype,
                               'id' => $id,
                               'default' => $default,
                               'source' => $table . '.' . $field,
                               'status' => $status,
                               'order' => $order,
                               'validation' => $validation);
        $order++;
        $result->MoveNext();
    }

    $result->Close();

    $propertybag["$modid:$itemtype:$table"] = $static;
    return $static;
}

?>