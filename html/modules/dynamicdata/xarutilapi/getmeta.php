<?php
/**
 * (try to) get the "meta" properties of tables
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * (try to) get the "meta" properties of tables via PHP ADODB
 *
 * @author the DynamicData module development team
 * @param $args['table']  optional table you're looking for
 * @return mixed array of field definitions, or null on failure
 * @throws BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_utilapi_getmeta($args)
{
    static $propertybag = array();

    extract($args);
    if (empty($table)) {
        $table = '';
    } elseif (isset($propertybag[$table])) {
        return array($table => $propertybag[$table]);
    }

    $dbtype = xarDBGetDefault()->databaseType;
    $dbname =  xarDBGetDefault()->databaseName; //default database!! not current
    if (!empty($table)) {
       $tabledata = explode('.',$table);
       if (isset($tabledata[0]) && isset($tabledata[1])) {
            $db = $tabledata[0];
       }
    }
   // Note: this only works if we use the same database connection
     if (!empty($db) && $db != $dbname) {
        $dbconn=xarDBNewConn(array('databaseName'=>$db));
        $dbconn->SelectDB($db);
        $prefix = $db . '.';
    } else {
        $dbconn = xarDB::getConn();
        $prefix = '';
    }

    if (!empty($table)) {
        $tables = array($table);
    } else {
        $tables = $dbconn->MetaTables();
    }

    if (!isset($tables)) {
        return;
    }

    $metadata = array();
    foreach ($tables as $curtable) {
        //jojo - make sure some db driers do not pass the curtable with prefix already (eg old mysqli)
        if (!empty($prefix)) {
            $hasprefix = (substr_count($curtable,$prefix));
            if ($hasprefix == 0) {
                $curtable = $prefix . $curtable;
            }
        }
        if (isset($propertybag[$curtable])) {
             $metadata[$curtable] = $propertybag[$curtable];
             continue;
        }
        $fields = $dbconn->MetaColumns($curtable);
        $keys = $dbconn->MetaPrimaryKeys($curtable);

        $id = 1;
        $columns = array();
        foreach ($fields as $field) {
            $fieldname = $field->name;
            $datatype = $field->type;
            $size = $field->max_length;

            // assign some default label for now, by removing the first part (xar_)
            //$name = preg_replace('/^.+?_/','',$fieldname);
            //jojo - get rid of this totally - just use the field name
            //need to review implications first
            $name = preg_replace('/^xar_/','',$fieldname);
            $label = strtr($name,'_',' ');
            $label = ucwords($label);
            if (isset($columns[$name])) {
                $i = 1;
                while (isset($columns[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }
            $status = 1;
            // assign some default validation for now
            $validation = $datatype;
            $validation .= (empty($size) || $size < 0) ? '' : ' (' . $size . ')';

            // (try to) assign some default property type for now
            // = obviously limited to basic data types in this case
            $dtype = $datatype;
            // skip special definitions (unsigned etc.)
            $dtype = preg_replace('/\(.*$/','',$dtype);
            switch ($dtype) {
                case 'char':
                case 'varchar':
                    $proptype = 2; // Text Box
                    if (!empty($size) && $size > 0) {
                        $validation = "0:$size";
                    }
                    break;
                case 'int':
                case 'integer':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'bigint':
                case 'year':
                    if ($size == 1) {
                        $proptype = 14; // Checkbox
                    } else {
                        $proptype = 15; // Number Box
                    }
                    break;
               case 'numeric':
                case 'float':
                case 'decimal':
                case 'double':
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
                case 'longtext':
                    $proptype = 5; // Large Text Area
                    $status = 2;
                    break;
                case 'longvarbinary':
                case 'varbinary':
                case 'binary':
                case 'blob':        // caution, could be binary too !
                    $proptype = 4; // Medium Text Area
                    $status = 2;
                    break;
                case 'enum':
                    $proptype = 6; // Dropdown
                    $validation = strtr($validation,array('enum(' => '', ')' => '', "'" => '', ',' => ';'));
                    break;
                default:
                    $proptype = 1; // Static Text
                    break;
            }

            // try to figure out if it's the item id
            if (!empty($keys) && in_array($fieldname,$keys)) {
                // not allowed to modify primary key !
                $proptype = 21; // Item ID
            }

            // JDJ: added 'primary' and 'autoincrement' fields.
            // If this causes a problem, it could be made optional.
            // It is used by the FlatTable datastore to determine the primary key.
            // Jojodee: is causing probs with sqlite at least in installer
            // made some changes - please review

            $hasdefault = isset($field->has_default)?$field->has_default:false;
            $default = '';
            if ($hasdefault) $default=$field->default_value;
            $columns[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $proptype,
                                   'id' => $id,
                                   'default' => $default,// could be unknown here
                                   'source' => $curtable . '.' . $fieldname,
                                   'status' => $status,
                                   'order' => $id,
                                   'validation' => $validation
                                   //'primary' => isset($field->primary_key)?$field->primary_key : '',
                                   //'autoincrement' => isset($field->auto_increment))? $field->auto_increment : ''
                                   );
            if (isset($field->primary_key)) {
               $newelement=array('primary'=>$field->primary_key);
               array_merge($columns[$name],$newelement);
            }
            if (isset($field->auto_increment)) {
               $newelement=array('autoincrement'=>$field->auto_increment);
               array_merge($columns[$name],$newelement);

            }
            $id++;
        }
        $metadata[$curtable] = $columns;
        $propertybag[$curtable] = $columns;
    }
    // Note: this only works if we use the same database connection
    if (!empty($db) && $db != xarDBGetName()) {
        $dbconn->SelectDB(xarDBGetName());
    }

    return $metadata;
}

?>