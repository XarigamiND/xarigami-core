<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Phase 5: Pre-Boot, Modify Configuration
 *
 * @access private
 * @param dbHost
 * @param dbName
 * @param dbUname
 * @param dbPass
 * @param dbPrefix
 * @param dbType
 * @param createDb
 * @todo better error checking on arguments
 */
function installer_admin_phase5()
{
    //jojo - replace this check with a better one once we finish new installer
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarCoreCache::setCached('installer','installing', true);

    // Get arguments
    if (!xarVarFetch('install_database_host','pre:trim:passthru:str',$dbHost)) return;
    if (!xarVarFetch('install_database_name','pre:trim:passthru:str',$dbName,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_username','pre:trim:passthru:str',$dbUname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_password','pre:trim:passthru:str',$dbPass,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_prefix','pre:trim:passthru:str',$dbPrefix,'xar',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_charset','pre:trim:passthru:str',$dbCharset,'utf8',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_type','str:1:',$dbType)) return;
    if (!xarVarFetch('install_create_database','checkbox',$createDB,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmDB','bool',$confirmDB,false,XARVAR_NOT_REQUIRED)) return;

    sys::import('xarigami.variables.system');

    $invalid = array();
    if ($dbName == '') {
        $invalid['database'][] = xarML('No database was specified');

    }

    if ($dbUname == '' && $dbType != 'sqlite') {
        $invalid['username'][] = xarML('No user name was specified');

    }
    // allow only a-z 0-9 and _ in table prefix
    if (!preg_match('/^\w*$/',$dbPrefix)) {
        $invalid['prefix'][] = xarML('Invalid table prefix or characters in table prefix. Only use a-z, a _ and/or 0-9 in the prefix.');
    }

    if (count($invalid)>0) {
        $invalid = serialize($invalid);

        $redirecturl =$_SERVER['HTTP_REFERER'].'?install_phase=4&install_language='.$install_language.'&invalid='.$invalid;
       xarResponseRedirect($redirecturl);
    }
    // Save config data
    $config_args = array('dbHost'    => $dbHost,
                         'dbName'    => $dbName,
                         'dbUname'   => $dbUname,
                         'dbPass'    => $dbPass,
                         'dbPrefix'  => $dbPrefix,
                         'dbType'    => $dbType,
                         'dbCharset' => $dbCharset);

    if (!xarInstallAPIFunc('modifyconfig', $config_args)) {
        return;
    }

    $defaultcollations = array(
                             'utf8'    => 'utf8_general_ci',
                             'big5'    => 'big5_chinese_ci',
                             'koi8r'   => 'koi8r_general_ci',
                             'ujis'    => 'ujis_japanese_ci',
                             'sjis'    => 'sjis_japanese_ci',
                             'latin1'  => 'latin1_swedish_ci',
                             'latin2'  => 'latin2_general_ci',
                             'ascii'   => 'ascii_general_ci',
                             'gb2312'  => 'gb2312_chinese_ci',
                             'cp1250'  => 'cp1250_general_ci'
                                );
        $collation = $defaultcollations[$dbCharset];
        $config_args['dbCollation'] = $collation;

    //Do we already have a db?
    //TODO: rearrange the loading sequence so that I can use xar functions
    //rather than going directly to adodb
    // Load in ADODB
    // FIXME: This is also in xarDB init, does it need to be here?
    //jojo - yes as it is not defined when xarDBDriverExists function is call
   if (!defined('XAR_ADODB_DIR')) {
        define('XAR_ADODB_DIR', sys::lib().'adodb');
    }
    include_once XAR_ADODB_DIR .'/adodb.inc.php';
    $ADODB_CACHE_DIR = sys::varpath() . '/cache/adodb';

    // {ML_dont_parse sys::lib().'xarigami/xarDB.php'}
    sys::import('xarigami.xarDB');
    // Check if there is a xar- version of the driver, and use it.
    // Note the driver we load does not affect the database type.
    if (xarDBdriverExists('xar' . $dbType, 'adodb')) {
        $dbDriver = 'xar' . $dbType;
    } else {
        $dbDriver = $dbType;
    }
    $dbconn = ADONewConnection($dbDriver);
    $dbExists = TRUE;
    // Not all Database Servers support selecting the specific db *after* connecting
    // so let's try connecting with the dbname first, and then without if that fails
    $dbConnected = @$dbconn->Connect($dbHost, $dbUname, $dbPass, $dbName );
    if (!$dbConnected) {
        // Couldn't connect to the specified dbName. Let's try connecting without dbName now
        // Need to reset dbconn prior to trying just a normal connection
        unset($dbconn);
        $dbconn = ADONewConnection($dbDriver);

        if ($dbConnected = @$dbconn->Connect($dbHost, $dbUname, $dbPass)) {
            $dbExists = FALSE;
        } else {
            $dbConnected = FALSE;
            $dbExists = FALSE;
        }
    }

    if (!$dbConnected) {
        $invalid['database'][] = xarML('Database connection failed. The information supplied was erroneous, such as a bad or missing password or wrong username.');
    }

    if (!$createDB && !$dbExists) {
        $invalid['database'][] = xarML('Check database #(1) exists as it wasnt selected to be created.', $dbName);
    }

    if (count($invalid)>0) {
        $invalid = serialize($invalid);
        $redirecturl =$_SERVER['HTTP_REFERER'].'?install_phase=4&install_language='.$install_language.'&invalid='.$invalid;
        xarResponseRedirect($redirecturl);
    }

    $data['confirmDB']  = $confirmDB;
    if ($dbExists && !$confirmDB) {
        $data['dbHost']     = $dbHost;
        $data['dbName']     = $dbName;
        $data['dbUname']    = $dbUname;
        $data['dbPass']     = $dbPass;
        $data['dbPrefix']   = $dbPrefix;
        $data['dbType']     = $dbType;
        $data['dbCharset']  = $dbCharset;
        $data['install_create_database'] = $createDB;
        $data['language']    = $install_language;
        return $data;
    }


    // Create the database if necessary
    if ($createDB) {
        $data['confirmDB']  = true;
        //Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
        //Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
        //Probably there is already a core function that can make that for us...
        //the config.system.php is lazy loaded in xarSystemVars::get(sys::CONFIG, $name), which means we cant reload the values
        // in this phase... Not a big deal 'though.
        if ($dbExists) {
            if (!$dbconn->Execute('DROP DATABASE ' . $dbName)) return;
        }
        if (!xarInstallAPIFunc('createdb', $config_args)) {
            $invalid['database'][]= xarML('Could not create database (#(1)). Check if you already have a database by that name and remove it.', $dbName);
            $invalid = serialize($invalid);
            $redirecturl =$_SERVER['HTTP_REFERER'].'?install_phase=4&install_language='.$install_language.'&invalid='.$invalid;
        xarResponseRedirect($redirecturl);

        }
    }
    else {
        $removetables = true;
    }

    // Start the database
    $systemArgs = array('userName' => $dbUname,
                        'password' => $dbPass,
                        'databaseHost' => $dbHost,
                        'databaseType' => $dbType,
                        'databaseName' => $dbName,
                        'systemTablePrefix' => $dbPrefix,
                        'siteTablePrefix' => $dbPrefix);
    // Connect to database
    $whatToLoad = XARCORE_SYSTEM_NONE;
    xarDB::init($systemArgs, $whatToLoad);

    // drop all the tables that have this prefix
    //TODO: in the future need to replace this with a check further down the road
    // for which modules are already installed
    xarDBLoadTableMaintenanceAPI();

    if (isset($removetables) && $removetables) {
        $dbconn = xarDB::$dbconn;
        $result = $dbconn->Execute($dbconn->metaTablesSQL);
        if(!$result) return;
        $tables = array();
        while(!$result->EOF) {
            list($table) = $result->fields;
            $parts = explode('_',$table);
            if ($parts[0] == $dbPrefix) $tables[] = $table;
            $result->MoveNext();
        }
        $metatable = $dbPrefix.'_tables';
        foreach ($tables as $table) {
            if ($table !=$metatable) {
                // FIXME: a lot!
                // 1. the drop table drops the sequence while the table gets dropped in the second statement - for postres
                //    so if that fails, the table remains while the sequence is gone, at least transactions is needed
                // 3. generating sql and executing in 2 parts sucks, wrt encapsulation
                try {
                    $sql = xarDBDropTable($table,$dbType);
                    $result = $dbconn->Execute($sql);
                    if(!$result) return;
                } catch (Exception $e) {
                    //some db will error if they try to drop a table that is not there
                    //hopefully this is why in this case
                }
            }
        }
        //now do the metatable
        $result = $dbconn->Execute($dbconn->metaTablesSQL);
        if($result) { //we have tables still
            $tables = array();
            while(!$result->EOF) {
                list($table) = $result->fields;
                $parts = explode('_',$table);
                if ($parts[0] == $dbPrefix) $tables[] = $table;
                $result->MoveNext();
            }
            if (count($tables)==1 && current($tables) ==$metatable) {
                $sql = xarDBDropTable($metatable,$dbType);
                $result = $dbconn->Execute($sql);
            }
        }
    }

    // install the security stuff here, but disable the registerMask and
    // and xarSecurityCheck functions until we've finished the installation process
    sys::import('xarigami.xarSecurity');
    //xarSecurity_init();

    // Base init need config vars
    sys::import('xarigami.variables.config');

    // Load in modules/installer/xarinit.php and start the install
    // This effectively initializes the base module.
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'installer',
                                 'initfunc'  => 'init'))) {
        return;
    }
   xarLogMessage ('INSTALLER: finished initializing Base');

    // If we are here, the base system has completed
    // We can now pass control to xarigami.

    $params=array();
    xarConfigSetVar('Site.MLS.DefaultLocale', $install_language);

    // Set the allowed locales to our "C" locale and the one used during installation
    // TODO: make this a bit more friendly.
    $necessaryLocale = array('en_US.utf-8');
    $install_locale  = array($install_language);
    $allowed_locales = array_merge($necessaryLocale, $install_locale);

    xarConfigSetVar('Site.MLS.AllowedLocales',$allowed_locales);

    $data['language'] = $install_language;
    $data['phase'] = 5;
    $data['phase_label'] = xarML('Step Five');

    return $data;
}


?>