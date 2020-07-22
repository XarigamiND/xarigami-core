<?php
/**
 * Modify the system configuration File
 *
 * @package Installer
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Installer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Modify the system configuration file
 *
 * @param string args['dbHost']
 * @param string args['dbName']
 * @param string args['dbUname']
 * @param string args['dbPass']
 * @param string args['prefix']
 * @param string args['dbType']
 * @return bool
 */
function installer_adminapi_modifyconfig($args)
{
    extract($args);

    // fixes instances where passwords contains --> '
    $dbPass = addslashes($dbPass);

    $systemConfigFile = sys::varpath() . '/config.system.php';
    $config_php = join('', file($systemConfigFile));

    //$dbUname = base64_encode($dbUname);
    //$dbPass = base64_encode($dbPass);

    // Get exception error handler setting
    $enablePHPErrorHandler = xarSystemVars::get(sys::CONFIG, 'Exception.EnablePHPErrorHandler');

    $config_php = preg_replace('/\[\'DB.Type\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Type'] = '$dbType';", $config_php);
    $config_php = preg_replace('/\[\'DB.Host\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Host'] = '$dbHost';", $config_php);
    $config_php = preg_replace('/\[\'DB.UserName\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.UserName'] = '$dbUname';", $config_php);
    $config_php = preg_replace('/\[\'DB.Password\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Password'] = '$dbPass';", $config_php);
    $config_php = preg_replace('/\[\'DB.Name\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Name'] = '$dbName';", $config_php);
    $config_php = preg_replace('/\[\'DB.TablePrefix\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.TablePrefix'] = '$dbPrefix';", $config_php);
    //$config_php = preg_replace('/\[\'DB.Encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Encoded'] = '1';", $config_php);
    $config_php = preg_replace('/\[\'Exception.EnablePHPErrorHandler\'\]\s*=\s*(\'|\")(.*)\\1;/', "['Exception.EnablePHPErrorHandler'] =$enablePHPErrorHandler;", $config_php);

    $fp = fopen ($systemConfigFile, 'wb');
    fwrite ($fp, $config_php);
    fclose ($fp);

    return true;
}

/**
 * Include a module init file and run a function
 *
 * @access public
 * @param args['directory'] the directory to include
 * @param args['initfunc'] init|upgrade|remove
 * @return bool
 * @throws BAD_PARAM, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 */
function installer_adminapi_initialise($args)
{
    extract($args);


    if (empty($directory) || empty($initfunc)) {
        throw new EmptyParameterException('directory or initfunc');
    }

    $osDirectory = xarVarPrepForOS($directory);
    $modInitFile = sys::code().'modules/'. $osDirectory. '/xarinit.php';


    if(!file_exists($modInitFile)) throw new FileNotFoundException($modInitFile);
    sys::import('modules.'.$osDirectory.'.xarinit');

    // Run the function, check for existence

    $initFunc = $osDirectory.'_'.$initfunc;
    if (function_exists($initFunc)) {
        $res = $initFunc();

        if ($res == false) {
            // exception
            throw new Exception('Core initialization failed!');
        }
    } else {
        // modulename_init() not found?!
        throw new FunctionNotFoundException($initFunc);
    }

    return true;
}

/**
 * Create a database
 *
 * @access public
 * @param args['dbName']
 * @param args['dbType']
 * @return bool
 * @throws BAD_PARAM, DATABASE_ERROR
 */
function installer_adminapi_createdb($args)
{
    extract($args);

    // {ML_dont_parse sys::code().'xarigami/xarDB.php'}
    sys::import('xarigami.xarDB');
    // Load in Table Maintainance API
    sys::import('xarigami.xarTableDDL');

    // Load in ADODB
    // FIXME: This is also in xarDB init, does it need to be here?
    //jojo - yes as it is not defined when xarDBDriverExists function is call
    if (!defined('XAR_ADODB_DIR')) {
        define('XAR_ADODB_DIR', sys::root().'/lib/adodb');
    }
    include_once XAR_ADODB_DIR .'/adodb.inc.php';
    //not used - but maybe the var is - check
    $ADODB_CACHE_DIR = sys::varpath() . '/cache/adodb';

    // Check if there is a xar- version of the driver, and use it.
    // Note the driver we load does not affect the database type.
    if (xarDBdriverExists('xar' . $dbType, 'adodb')) {
        $dbDriver = 'xar' . $dbType;
    } else {
        $dbDriver = $dbType;
    }

    // Start connection
    $dbconn = ADONewConnection($dbDriver);
    if ($dbType == 'postgres') {
        // quick hack to enable Postgres DB creation
        $dbh = $dbconn->Connect($dbHost, $dbUname, $dbPass, 'template1');
    } else {
        $dbh = $dbconn->Connect($dbHost, $dbUname, $dbPass);
    }
    if (!$dbh) {
        $dbpass = '';
        die("Failed to connect to $dbType://$dbUname:$dbPass@$dbHost/, error message: " . $dbconn->ErrorMsg());
    }

    $query = xarDBCreateDatabase($dbName,$dbType,$dbCharset,$dbCollation);

    $result = $dbconn->Execute($query);

    if (!$result) return;

    return true;
}


/**
 * CheckForField
 *
 * @access public
 * @param args['field_name']
 * @param args['table_name']
 * @return bool true if field exists false otherwise
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_CheckForField($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) || (!isset($table_name))) {
        throw new EmptyParameterException('field_name or table_name');
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $query = "desc $table_name";
    $result = $dbconn->Execute($query);

    for(;!$result->EOF;$result->MoveNext()) {
        if ($result[Field] == $field_name) {
            return true;
        }
    }

    return false;
}

/**
 * GetFieldType
 *
 * @access public
 * @param args['field_name']
 * @param args['table_name']
 * @return field type
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_GetFieldType($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) || (!isset($table_name))) {
        throw new EmptyParameterException('field_name or table_name');
    }

    $dbconn = xarDB::$dbconn;

    $query = "desc $table_name";
    $result = $dbconn->Execute($query);

    for(;!$result->EOF;$result->MoveNext()) {
        if ($result[Field] == $field_name) {
            return ($row[Type]);
        }
    }
    return;
}

/**
 * CheckTableExists
 *
 * @access public
 * @param args['table_name']
 * @return bool true if field exists false otherwise
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_CheckTableExists($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($table_name)) throw new EmptyParameterException('table_name');

    $dbconn = xarDB::$dbconn;
    $result = $dbconn->MetaTables();
    if (in_array($table_name, $result)){
        return true;
    } else {
        return false;
    }
}

/**
 * Modify one or more variables in a configuration file
 *
 * @author Marc Lutolf
 * @param string args['variables'] = array($name => $value,...)
 * @return bool
 */

function installer_adminapi_modifysystemvars($args)
{
    if (!isset($args['variables'])) throw new BadParameterException('variables');
    $configfile = sys::varpath() . '/config.system.php';
    if (isset($args['filepath'])) $configfile = $args['filepath'];
    try {
        $config_php = join('', file($configfile));
        foreach ($args['variables'] as $name => $value) {
            $config_php = preg_replace('/\[\''.$name.'\'\]\s*=\s*(\'|\")(.*)\\1;/', "['".$name."'] = '$value';", $config_php);
        }

        $fp = fopen ($configfile, 'wb');
        fwrite ($fp, $config_php);
        fclose ($fp);
        return true;

    } catch (Exception $e) {
        throw new FileNotFoundException($configfile);
    }
}

?>
