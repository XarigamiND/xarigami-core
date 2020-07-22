<?php
/**
 * Table Maintenance API
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Core
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
 * @subpackage database
 * @author Gary Mitchell
 * @todo Check functions!
 *       Check FIXMEs
 *       Document functions
 * Public Functions:
 *
 * xarDBCreateDatabase($databaseName, $databaseType = NULL)
 * xarDBCreateTable($tableName, $fields, $databaseType = NULL)
 * xarDBDropTable($tableName, $databaseType = NULL)
 * xarDBAlterTable($tableName, $args, $databaseType = NULL)
 * xarDBCreateIndex($tableName, $index, $databaseType = NULL)
 * xarDBDropIndex($tableName, $databaseType = NULL)
 *
 */
/**
 * Generate the SQL to create a database
 *
 * @access public
 * @param databaseName
 * @param databaseType
 * @return string sql statement for database creation
 * @throws BAD_PARAM
 */
function xarDBCreateDatabase($databaseName, $databaseType=NULL, $databaseCharset='utf8', $databaseCollate = 'utf8_general_ci')
{
    // perform validations on input arguments
    if (empty($databaseName)) throw new EmptyParameterException('databaseName');
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    switch($databaseType) {
        case 'mysql':
        case 'mysqli':
        case 'oci8':
        case 'oci8po':
            $sql = 'CREATE DATABASE '. $databaseName . ' DEFAULT CHARACTER SET ' . $databaseCharset . ' DEFAULT COLLATE ' . $databaseCollate;
            break;
        case 'postgres':
            $sql = 'CREATE DATABASE "'.$databaseName .'" ';
            break;
        case 'sqlite':
        case 'pdosqlite':
            // No such thing, its created automatically when it doesnt exist
            $sql ='';
            break;
        case 'mssql':
        case 'datadict':
            sys::import('xarigami.tableddl.datadict');
            $sql = xarDB__datadictCreateDatabase($databaseName);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;

}

/**
 * Generate the SQL to create a table
 *
 * @access public
 * @param tableName the table to alter
 * @param args['command'] command to perform on table(add,modify,drop,rename)
 * @param args['field'] name of column to alter
 * @param args['type'] column type
 * @param args['size'] size of column if varying data
 * @param args['default'] default value of data
 * @param args['null'] null or not null (true/false)
 * @param args['unsigned'] allow unsigned data (true/false)
 * @param args['increment'] auto incrementing files
 * @param args['primary_key'] primary key
 * @param databaseType the database type (optional)
 * @return string generated sql
 * @throws EmptyParameterException, BadParameterException
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBCreateTable($tableName, $fields, $databaseType="",$charset="utf8")
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($fields)) throw new BadParameterException('fields','The #(1) parameter is not an array');
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    // save table definition
    $systemPrefix = xarDB::$sysprefix;
    $metaTable = $systemPrefix . '_tables';
    if ($tableName != $metaTable) {
        $dbconn = xarDB::$dbconn;
        while (list($field_name, $parameters) = each($fields)) {
            $nextId = $dbconn->GenId($metaTable);
            $query = "INSERT INTO $metaTable (
                      xar_tableid, xar_table, xar_field,  xar_type,
                      xar_size,  xar_default, xar_null, xar_unsigned,
                      xar_increment, xar_primary_key)
                    VALUES (?,?,?,?,?,?,?,?,?,?)";
            if (!isset($parameters['default'])) {
                $defaultval = '';
            } elseif (is_string($parameters['default'])) {
                $defaultval = $parameters['default'];
            } else {
                $defaultval = serialize($parameters['default']);
            }
            $bindvars = array($nextId,$tableName,$field_name,
                              (empty($parameters['type']) ? '' : $parameters['type']),
                              (empty($parameters['size']) ? '' : $parameters['size']),
                              $defaultval,
                              (empty($parameters['null']) ? '0' : '1'),
                              (empty($parameters['unsigned']) ? '0' : '1'),
                              (empty($parameters['increment']) ? '0' : '1'),
                              (empty($parameters['primary_key']) ? '0' : '1'),

                              );
                  //    xar_width,
                  //    xar_decimals,
            $result = $dbconn->Execute($query,$bindvars);
        }
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            sys::import('xarigami.tableddl.mysql');
            $sql = xarDB__mysqlCreateTable($tableName, $fields, $charset);
            break;
        case 'mysqli':
            sys::import('xarigami.tableddl.mysqli');
            $sql = xarDB__mysqliCreateTable($tableName, $fields, $charset);
            break;
        case 'postgres':
            sys::import('xarigami.tableddl.postgres');
            $sql = xarDB__postgresqlCreateTable($tableName, $fields, $charset);
            break;
        case 'oci8':
        case 'oci8po':
            sys::import('xarigami.tableddl.oracle');
            $sql = xarDB__oracleCreateTable($tableName, $fields, $charset);
            break;
        case 'sqlite':
        case 'pdosqlite':
            sys::import('xarigami.tableddl.sqlite');
            $sql = xarDB__sqliteCreateTable($tableName, $fields, $charset);
            break;
        case 'mssql':
        case 'datadict':
            sys::import('xarigami.tableddl.datadict');
            $sql = xarDB__datadictCreateTable($tableName, $fields, $charset);

            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

/**
 * Alter database table
 *
 * @access public
 * @param tableName the table to alter
 * @param args['command'] command to perform on table(add,modify,drop,rename)
 * @param args['field'] name of column to alter
 * @param args['type'] column type
 * @param args['size'] size of column if varying data
 * @param args['default'] default value of data
 * @param args['null'] null or not null (true/false)
 * @param args['unsigned'] allow unsigned data (true/false)
 * @param args['increment'] auto incrementing files
 * @param args['primary_key'] primary key
 * @param databaseType the database type (optional)
 * @throws EmptyParameterException, BadParameterException
 * @return string generated sql
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBAlterTable($tableName, $args, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($args) || !isset($args['command'])) {
        throw new BadParameterException('args','Invalid parameter "args", it must be an array, and the "command" key must be set');
    }

    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    // save table definition
    if (isset($args['command']) && $args['command'] == 'add') {
        $systemPrefix = xarDB::$sysprefix;
        $metaTable = $systemPrefix . '_tables';

        $dbconn = xarDB::$dbconn;
        $nextId = $dbconn->GenId($metaTable);
        $query = "INSERT INTO $metaTable (
                      xar_tableid, xar_table, xar_field, xar_type,
                      xar_size,  xar_default, xar_null,  xar_unsigned,
                      xar_increment, xar_primary_key)
                    VALUES (?,?,?,?,?,?,?,?,?,?)";
        if (!isset($parameters['default'])) {
            $defaultval = '';
        } elseif (is_string($parameters['default'])) {
            $defaultval = $parameters['default'];
        } else {
            $defaultval = serialize($parameters['default']);
        }
        $bindvars = array($nextId,$tableName,$args['field'],
                          (empty($args['type']) ? '' : $args['type']),
                          (empty($args['size']) ? '' : $args['size']),
                          $defaultval,
                          (empty($args['null']) ? '0' : '1'),
                          (empty($args['unsigned']) ? '0' : '1'),
                          (empty($args['increment']) ? '0' : '1'),
                          (empty($args['primary_key']) ? '0' : '1'));
                  //    xar_width,
                  //    xar_decimals,
        $result = $dbconn->Execute($query,$bindvars);

    } elseif (isset($args['command']) && $args['command'] == 'rename') {

        $systemPrefix = xarDB::$sysprefix;
        $metaTable = $systemPrefix . '_tables';

        $dbconn = xarDB::$dbconn;
        $nextId = $dbconn->GenId($metaTable);
        $query = "UPDATE $metaTable SET xar_table = ? WHERE xar_table = ?";
        $bindvars = array((string) $args['new_name'], (string) $tableName);
        $result = $dbconn->Execute($query,$bindvars);
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
            sys::import('xarigami.tableddl.mysql');
            $sql = xarDB__mysqlAlterTable($tableName, $args);
            break;
         case 'mysqli':
            sys::import('xarigami.tableddl.mysqli');
            $sql = xarDB__mysqliAlterTable($tableName, $args);
            break;
        case 'postgres':
            sys::import('xarigami.tableddl.postgres');
            $sql = xarDB__postgresqlAlterTable($tableName, $args);
            break;
        case 'oci8':
        case 'oci8po':
            sys::import('xarigami.tableddl.oracle');
            $sql = xarDB__oracleAlterTable($tableName, $args);
            break;
        case 'sqlite':
        case 'pdosqlite':
            sys::import('xarigami.tableddl.sqlite');
            $sql = xarDB__sqliteAlterTable($tableName, $args);
            break;
        case 'mssql':
        case 'datadict':
            sys::import('xarigami.tableddl.datadict');
            $sql = xarDB__datadictAlterTable($tableName, $args);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

/**
 * Generate the SQL to delete a table
 *
 * @access public
 * @param tableName the physical table name
 * @param index an array containing the index name, type and fields array
 * @return data|false the generated SQL statement, or false on failure
 * @todo DID YOU READ THE NOTE AT THE TOP OF THIS FILE?
 */
function xarDBDropTable($tableName, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    // remove table definition
    $systemPrefix = xarDB::$sysprefix;
    $metaTable = $systemPrefix . '_tables';
    if ($tableName != $metaTable) {
        $dbconn = xarDB::$dbconn;
        $query = "DELETE FROM $metaTable WHERE xar_table=?";
        $result = @$dbconn->Execute($query,array($tableName));
    }

    switch($databaseType) {
       // case 'postgres':
       // jojo - why the inconsistency? Just prepare the SQL like for others and pass it back
       // use the datadict here
            // Also drop the related sequence
         //   $seqSQL = "DROP SEQUENCE seq".$tableName;
         //   $dbconn = xarDB::$dbconn;
        //    $result = $dbconn->Execute($seqSQL);
            // ignore exception for now
        case 'mysql':
        case 'mysqli':
        case 'oci8':
        case 'oci8po':
        case 'sqlite':
        case 'pdosqlite':
            $sql = 'DROP TABLE '.$tableName;
            break;
        case 'mssql':
        case 'postgres':
        case 'datadict':
            sys::import('xarigami.tableddl.datadict');
            $sql = xarDB__datadictDropTable($tableName);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;

}

/**
 * Generate the SQL to create a table index
 *
 * @param tableName the physical table name
 * @param index an array containing the index name, type and fields array
 * @param databaseType is an optional parameter to specify the database type
 * @return string|false the generated SQL statement, or false on failure
 * @throws EmptyParameterException, BadParameterException
 */
function xarDBCreateIndex($tableName, $index, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($index) || !is_array($index['fields']) || empty($index['name'])) {
        throw new BadParameterException('index','The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
    }
    // default for unique
    if (!isset($index['unique'])) {
        $index['unique'] = false;
    }

    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
        case 'mysqli':
            if ($index['unique'] == true) {
                $sql = 'ALTER TABLE '.$tableName.' ADD UNIQUE '.$index['name'];
            } else {
                $sql = 'ALTER TABLE '.$tableName.' ADD INDEX '.$index['name'];
            }
            $sql .= ' ('.join(',', $index['fields']).')';
            break;
        case 'postgres':
            //get rid of the index qualifier if any for postgres then continue
            foreach($index['fields'] as $k=>$value) {
                $index['fields'][$k] = preg_replace('/\([0-9]*?\)/','',$value);
            }
        case 'oci8':
        case 'oci8po':
        case 'sqlite':
        case 'pdosqlite':
            //get rid of the index qualifier if any for postgres then continue
            foreach($index['fields'] as $k=>$value) {
                $index['fields'][$k] = preg_replace('/\([0-9]*?\)/','',$value);
            }
            if ($index['unique'] == true) {
                $sql = 'CREATE UNIQUE INDEX '.$index['name'].' ON '.$tableName;
            } else {
                $sql = 'CREATE INDEX '.$index['name'].' ON '.$tableName;
            }
            $sql .= ' ('.join(',', $index['fields']).')';
            break;

        case 'mssql':
        case 'datadict':
            sys::import('xarigami.tableddl.datadict');
            $sql = xarDB__datadictCreateIndex($tableName, $index);
            break;

        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}
/**
 * Generate the SQL to drop an index
 *
 * @access public
 * @param tableName
 * @param name a db index name
 * @param databaseType
 * @return string|false generated sql to drop an index
 * @throws EmptyParameterException, BadParameterException
 */
function xarDBDropIndex($tableName, $index, $databaseType = NULL)
{
    // perform validations on input arguments
    if (empty($tableName)) throw new EmptyParameterException('tableName');
    if (!is_array($index) ||  empty($index['name'])) {
        throw new BadParameterException('index','The parameter "#(1)" must be an array, the "fields" key inside it must be an array and the "name" key must be set).');
    }
    if (empty($databaseType)) {
        $databaseType = xarDB::getType();
    }

    // Select the correct database type
    switch($databaseType) {
        case 'mysql':
        case 'mysqli':
            $sql = 'ALTER TABLE '.$tableName.' DROP INDEX '.$index['name'];
            break;
        case 'postgres':
        case 'oci8':
        case 'oci8po':
        case 'sqlite':
        case 'pdosqlite':
            $sql = 'DROP INDEX '.$index['name'];
            break;
        case 'mssql':
        case 'datadict':
            sys::import('xarigami.tableddl.datadict');
            $sql = xarDB__datadictDropIndex($tableName, $index);
            break;
        // Other DBs go here
        default:
            throw new BadParameterException($databaseType,'Unknown database type: "#(1)"');
    }
    return $sql;
}

?>
