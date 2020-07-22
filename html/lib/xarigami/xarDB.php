<?php
/**
 * Xarigami Database Abstraction Layer API Helpers and wrappers
 *
 * @package core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami ADODB Abstraction layer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
 * For more information:
 *   http://phplens.com/lens/adodb/docs-datadict.htm
 */
$_lib_ = sys::lib();
include_once $_lib_.'/adodb/adodb.inc.php';
include_once $_lib_.'/adodb/adodb-exceptions.inc.php';
if (!defined('XAR_ADODB_DIR')) define('XAR_ADODB_DIR', $_lib_.'adodb');
unset($_lib_);

class xarDB extends xarObject
{
    public static $count = 0;
    // shared db current static connection (used in xarBase)
    public static $dbconn = NULL;
    // current table name mapping
    public static $tables = array();
    
    // connection array
    protected static $connections = array();
    // default conneciton
    protected static $defaultConnection = NULL;
    
    public static $prefix = '';
    public static $sysprefix = 'xar';
    // For internal use only
    private static $systemArgs = array();
    
    /**
     * Initialize DB and establish the default connection
     * Initializes the database connection.
     *
     * This function loads up ADODB  and starts the database
     * connection using the required parameters then it sets
     * the table prefixes and xartables up and returns true
     *
     * @access protected
     * @global array xarDB_systemArgs
     * @global object dbconn database connection object
     * @global array xarTables database tables used by Xarigami
     * @param string args[name]
     * @param string args[databaseType] database type to use
     * @param string args[databaseHost] database hostname
     * @param string args[databaseName] database name
     * @param string args[userName] database username
     * @param string args[password] database password
     * @param bool args[persistent] flag to say we want persistent connections (optional)
     * @param string args[systemTablePrefix] system table prefix
     * @param string args[siteTablePrefix] site table prefix
     * @return bool true on success, false on failure
     * @todo Lakys: this is a shortcut to assume the current connection, is also the default one,
     *       and the first to be established. Revisit this in core 2.
     */
    public static function init(array $args = NULL)
    {
        //set global fetch mode  - ADODB uses a global for this
        $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_NUM;
        
        self::$prefix =  $args['siteTablePrefix'];
        self::$sysprefix =  $args['systemTablePrefix'];
        self::$dbconn = self::$defaultConnection = self::newConn($args);
        self::$tables['template_tags'] = self::$prefix . '_template_tags';
        return TRUE;
    }

    /**
     * Initialise a new db connection
     *
     * Create a new connection based on the supplied parameters
     * @access public
     * @param idem as init() method
     */
    public static function newConn(array $args = NULL)
    {
        if ($args !== NULL || empty(self::$systemArgs)) {
            // Get database parameters
            if (!isset($args['userName']) || !isset($args['password'])) {
                $userName = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
                $password = xarSystemVars::get(sys::CONFIG, 'DB.Password');
                if (xarSystemVars::get(sys::CONFIG, 'DB.Encoded') == '1') {
                    $userName = base64_decode($userName);
                    $password  = base64_decode($password);
                }
            }
            // Get database parameters but only override with global if they are not passed in
            $systemArgs = array('dbType'   => isset($args['databaseType'])? $args['databaseType'] : xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                                'dbHost'   => isset($args['databaseHost'])? $args['databaseHost'] : xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                                'dbName'   => isset($args['databaseName'])? $args['databaseName'] : xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                                'dbUname'  => isset($args['userName'])    ? $args['userName']    : $userName,
                                'dbPass'   => isset($args['password'])    ? $args['password']    : $password);
        
            $systemArgs['persistent'] = !empty($systemArgs['persistent']) ? TRUE : FALSE;
        } else if ($args === NULL) {
            $systemArgs = self::$systemArgs;
        }
        
        $flags = 0;
        try {
            $conn = self::getConnection($systemArgs, $flags);
        } catch (Exception $e) {
            xarLogMessage("DATABASE CONNECTION PROBLEM: $e", XARLOG_LEVEL_EMERGENCY, array('sql'));
            throw new ServiceUnavailableException($e);
        }
    
        xarLogMessage("New connection created, now serving " . self::$count . " connections", XARLOG_LEVEL_DEBUG, array('sql'));
        return $conn;
    }

    /**
     * Get a database connection
     *
     * @access public
     * @global array  xarDB_connections array of database connection objects
     * @return object database connection object
     */
    public static function getConn($index=0)
    {
        // we only want to return the first default connection here
        if (count(self::$connections) <= $index && !empty(self::$systemArgs)) {
            $conn = self::getConnection(self::$systemArgs);
        } else {
            $conn= self::$connections[$index];
        }
        return $conn;
    }

    public static function getConnection($systemArgs, $flags = 0)
    {
        $dbType = $systemArgs['dbType'];
        self::$systemArgs = $systemArgs;
        // Check if there is a xar- version of the driver.
        // @todo - jojo - do we really need these special xar drivers now?
        if (self::driverExists('xar'.$dbType, 'adodb')) {
             $dbType = 'xar'.$dbType;
        }
        
        $conn = ADONewConnection($dbType);
        $cfunction = '';
        if ($systemArgs['persistent']) {
            $cfunction='PConnect';
            $boolvalue = FALSE;
        } else {
            $cfunction='Connect';
            $boolvalue = TRUE;
        }
        $conn->$cfunction($systemArgs['dbHost'], $systemArgs['dbUname'], $systemArgs['dbPass'], $systemArgs['dbName'], $boolvalue);

        self::$connections[] = $conn;
        $database_key = key(self::$connections);
        // Store the key in the connection object so the caller knows how to fetch it again.
        // lakys: there is no property in ADOConnection to to that. Accessing the property cause an error.
        // $conn->database_key = $database_key; 
        // lakys: best way to compare connection is to use identity operator ===. (refer to xarSecurityCheck() in xarSecurity.php)
        //jojo - fix setDefault
        //self::$setDefault($database_key);
        self::$count++;

        return $conn;
    }

    /**
     * Check whether an ADOdb driver exists.
     * Checks the driver by looking for its file, without attempting to load it.
     * It would be nice if this were a public function of ADOdb, because we
     * should not have to keep this code updated.
     *
     * @access private
     * @return boolean TRUE if the driver exists
     * @todo this is a copy of private ADOdb code, must keep it updated
     * @todo expand the handler types as necessary (e.g. for creole)
     */
    public static function driverExists($dbType, $handler = 'adodb')
    {
        if (empty($dbType) || $handler != 'adodb') return FALSE;
        // Strip off and save the 'xar' prefix, if it exists.
        if (strpos($dbType, 'xar') === 0) {
            $prefix = 'xar';
            $dbType = substr ($dbType, 3);
        } else {
            $prefix = '';
        }
        // Do some ADOdb-specific mapping.
        // This mapping is for version 4.60.
        $db = strtolower($dbType);
        switch ($db) {
            case 'ado':
                if (PHP_VERSION >= 5) $db = 'ado5';
                $class = 'ado';
                break;
            case 'ifx':
            case 'maxsql': $class = $db = 'mysqlt'; break;
            case 'postgres':
            case 'postgres8':
            case 'pgsql': $class = $db = 'postgres'; break;
            default:
                $class = $db; break;
        }
        $file = XAR_ADODB_DIR . "/drivers/adodb-" . $prefix . $db . ".inc.php";
        return (file_exists($file) ? TRUE : FALSE);
    }
    /**
     * Get an array of database tables
     * @access public
     * @global array xarDB_tables array of database tables
     * @return array array of database tables
     */
    public static function getTables()
    {
        return self::$tables;
    }
    /**
     * Set Default Connection
     * Set a new connection based on the supplied parameters
     * @todo: jojo - fix this
     */
    private static function setDefault($database_key=0)
    {
        self::$defaultConnection = self::$connections[0];
/*        if (!isset(self::$defaultConnection)) {
            self::$connections['default'] = self::$connections[$database_key];
        } else {
             self::$connections['default'] = self::$connections[0];
        }

        self::$defaultConnection =  self::$connections['default'];
  */
    }
    /**
     * Get Default Connection
     * Geta new connection based on the default parameters
     */
    public static function getDefault()
    {
        return self::$defaultConnection;
    }
    /**
     * Load the Table Maintenance API
     *
     * @access public
     * @return TRUE
     * @todo <johnny> change to protected or private?
     * @todo <mrb> Insane function name
     * @todo <mrb> This needs to be replaced by datadict functionality
     */
    public static function loadTableMaintenanceAPI()
    {
        static $loaded = FALSE;
        // Include Table Maintainance API file
        if ($loaded === FALSE) {
            sys::import('xarigami.xarTableDDL');
            $loaded = TRUE;
        }
        return TRUE;
    }

    /**
     * Create a data dictionary object
     *
     * This function will include the appropriate classes and instantiate
     * a data dictionary object for the specified mode. The default mode
     * is 'READONLY', which just provides methods for reading the data
     * dictionary. Mode 'METADATA' will return the meta data object.
     * Mode 'ALTERTABLE' will provide methods for altering schemas
     * (creating, removing and changing tables, indexes, constraints, etc).
     * Mode 'ALTERDATABASE' will provide the highest level of commands
     * for creating, dropping and changing databases.
     *
     * NOTE: until the data dictionary is split into separate classes
     * all modes except METADATA will return the ALTERDATABASE object.
     *
     * @access public
     * @return data   dictionary object (specifics depend on mode)
     * @param  object $dbconn ADODB database connection object
     * @param  string $mode the mode in which the data dictionary will be used; default READONLY
     * @todo   fully implement the mode, by layering the classes into separate files of readonly and amend methods
     * @todo   xarMetaData class needs to accept the database connection object
     * @todo   make xarMetaData the base class for the data dictionary
     * @todo jojo: What are we going to do with this now?
     */
    public static function newDataDict($dbconn = array(), $mode = 'READONLY')
    {
        static $loaded = FALSE;
        if ($loaded === FALSE && !class_exists('xarDataDict')) {
            // Include the data dictionary source.
            // Depending on the mode, there may be one or more files to include.
            sys::import('xarigami.xarDataDict');
        }
        $loaded = TRUE;

        // Decide which class to use.
        if ($mode == 'METADATA') {
            $class = 'xarMetaData';
        } else {
            // 'READONLY', 'ALTERTABLE', 'ALTERDATABASE' or other.
            $class = 'xarDataDict';
        }
        // Instantiate the object.
        $dict = new $class($dbconn);
        return $dict;
    }
    /**
     * Get the database host
     *
     * @access public
     * @return string
     */
    public static function getHost()
    {
        return self::$systemArgs['dbHost'];
    }
    /**
     * Get the database type
     *
     * @access public
     * @return string
     */
    public static function getType()
    {
        return self::$systemArgs['dbType'];
    }
    /**
     * Get the database name
     *
     * @access public
     * @return string
     */
    public static function getName()
    {
        return self::$systemArgs['dbName'];
    }
    /**
     * Get the system table prefix
     * @access public
     * @return string
     */
    public static function getSystemTablePrefix()
    {
        return  self::$sysprefix;
    }
    //compatibility only
    public static function getPrefix()
    {
        return  self::$prefix;
    }
    /**
     * Get the site table prefix
     * @todo change it back to return site table prefix
     *       when we decide how to store site information
     */
    public static function getSiteTablePrefix()
    {
        return self::$prefix;
    }

    public static function setPrefix($prefix)
    {
         self::$prefix =  $prefix;
    }
    /**
     * Import module tables in the array of known tables
     * @global xartable array
     */
    public static function importTables($tables)
    {
        self::$tables = array_merge(self::$tables, $tables);
    }
}

/**
 * Base class supporting db operations for xar static classes
 */ 
class xarBase extends xarDB
{

}

/**
 * Base object class support db operations for xar instanciated classes
 */
class xarBaseObject extends xarBase
{
    protected $mydbconn = NULL;
    protected $mytables = NULL;
    protected $myprefix = '';
    
    public function __construct($args = NULL)
    {
        if (empty($args)) { 
            $this->mydbconn = self::$dbconn;
            $this->$mytables = &self::$tables;
            $this->$myprefix = self::$prefix;
        }
        
    }
}

class xarFactory extends xarObject 
{
    protected $_classname = 'xarObject';
    protected $_master = NULL;
    
    protected function setClass($classname)
    {
        $this->_classname = $classname;
    }
    
    protected function createMaster($args = NULL)
    {
        $this->_master = new $_classname($args);
    }
}

class xarDeepFactory extends xarFactory
{
    
}



/**
 * xarDB Function wrappers
 */
function xarDB_init(array $args)
{
    xarDB::init($args);
    return true;
}
/**
 * Initialise a new db connection
 *
 * Create a new connection based on the supplied parameters
 * @access public
 */
function xarDBNewConn(array $args = NULL)
{ return xarDB::newConn($args); }

//wrapper classes
function xarDBGetConn($index = 0)
{
    if ($index === 0 && xarDB::$count === 1) return xarDB::$dbconn;
    return xarDB::getConn($index);
}
//function xarDBNewConn(array $args = NULL)
//{ return xarDB::newConn($args);}

function xarDBdriverExists($dbType, $handler = 'adodb')
{ return xarDB::driverExists($dbType, $handler = 'adodb'); }
function xarDBGetTables()
{
    return xarDB::$tables;
}

function xarDBSetDefault($database_key = NULL)
{ return xarDB::setDefault($database_key = NULL);}
function xarDBGetDefault()
{ return xarDB::getDefault();}
function xarDBLoadTableMaintenanceAPI()
{ return xarDB::loadTableMaintenanceAPI();}
function xarDBNewDataDict($dbconn, $mode = 'READONLY')
{ return xarDB::newDataDict($dbconn, $mode = 'READONLY'); }
function xarDBGetHost()
{ return xarDB::getHost();}
function xarDBGetType()
{ return xarDB::getType();}
function xarDBGetName()
{ return xarDB::getName();}
function xarDBGetSystemTablePrefix()
{ return xarDB::$sysprefix;}
function xarDBGetSiteTablePrefix()
{ return xarDB::$prefix;}
function xarDB_importTables($tables)
{ return xarDB::importTables($tables);}


?>
