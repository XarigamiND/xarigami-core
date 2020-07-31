<?php
/**
 * Bootstrap - minimal, lightweight collection of utility functions
 *
 * @package core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Core
 * @copyright (C) 2006-2013 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Initializes the pre core system - if there is ever anything to initialize
 *
 * @access public
 * @return bool TRUE
 */
/* This file is the only one (longer term) which always gets included when
 * running Xarigami. Everything else is lazy loaded. This file contains the
 * things which *absolutely* need to be available, which should be very little.
 *
 * So far:
 *  - Declaration of the Object   class of which all other classes are derived
 *  - Declaration of the xarClass class which Metamodels a PHP class.
 *  - Declaration of the xarProperty class which Metamodels a PHP property
 *  - Definition of the sys class which contains methods to steer php in the right direction
 *    for getting the right files (now: inclusion and the var path)
 *
 * If anything, make absolutely sure you get the fastest implementation
 * of what you want to do here.
 *
 */

//defines for logs - we start logging early and define levels in config.system.php
define('XARLOG_LEVEL_EMERGENCY', 1);
define('XARLOG_LEVEL_ALERT',     2);
define('XARLOG_LEVEL_CRITICAL',  4);
define('XARLOG_LEVEL_ERROR',     8);
define('XARLOG_LEVEL_WARNING',   16);
define('XARLOG_LEVEL_NOTICE',    32);
define('XARLOG_LEVEL_INFO',      64);
define('XARLOG_LEVEL_DEBUG',     128);
//special log level for audit
define('XARLOG_LEVEL_AUDIT',     65);
// This is a special define that includes all the levels defined above
define('XARLOG_LEVEL_ALL',       255);

/**
 * Get the public properties of an object (this must be done outside the class)
 */
function xarBoot_getPublicObjectProperties($obj)
{
    return get_object_vars($obj);
}

/**
 * The xarObject class from which all other classes should be derived.
 *
 * This is basically a placeholder extending from stdClass so we have a
 * place to put things in our root class. There are severe limitations to what
 * can and can not be placed into this class. For example, it can not have a
 * constructor because it would prevent descendants to have a private
 * constructor, which is rather common in the SingleTon pattern.
 *
 * @package core
 */
class xarObject extends stdClass
{
    /**
     * Convert an object to a string representation
     *
     * As PHP has its own __toString() magic method, we want to use that, but
     * that method can not be called explicitly. So we declare toString() in
     * the interface so every object has it, but still reacts properly to __toString()
     * method invocations by the engine itself (when converting internally to a string)
     * If customized behaviour is needed, override __toString() in your class.
     *
     * @return string string representation of the object.
     * @todo php version 5.2 is ok for sure, 5.1.4/5.1.6 works, but manual says it
     *       shouldnt work with sprintf(), keep an eye on it.
     */
    final public function toString()
    {
        // Reuse __toString magic by internal conversion.
        return sprintf('%s',$this);
    }

    /**
     * Return the class for an object
     *
     * We want to be consistent with objects, so we need a class to model a class
     * PHP allows directly only get_class() or something like that, which
     * returns a string.
     * By defining a class called xarClass
     * we can get the class from each object and maintain the 'richness' of
     * an object versus the 'flatness' of a string.
     *
     * @return Class_ the class of the object
     */
    final public function getClass()
    {
        return new xarClass($this);
    }

    /**
     * Determine equality of two objects
     *
     * We do this because it allows to make the comparison overridable and
     * pair it up with the hashCode method
     * Usually when overriding equals or hashCode, you will want to override
     * the other method too.
     * Note: $this === $object is the same here, but this way just overriding
     * hashCode allows for equality specialization.
     */
    public function equals(xarObject $object)
    {
        return $this->hashCode() === $object->hashCode();
    }

    /**
     * A unique id for an object
     *
     * @return string unique id
     */
    public function hashCode()
    {
        return spl_object_hash($this);
    }

    /**
     * Get an array of property values
     *
     * @todo why not deliver a Property[] instead?
     * @todo the public part is something that probably belongs in the caller, not here
     * @todo it doesn't get properties ;-)
     */
    public function getPublicProperties()
    {
       return xarBoot_getPublicObjectProperties($this);
    }
}

/**
 * Base class for the reflectable objects we will expose
 *
 * @package core
 */
abstract class xarReflectable extends xarObject
{
    protected $reflect = NULL;

    public function getName()
    {
        return $this->reflect->getName();
    }
}

/**
 * A class to model a class in PHP
 *
 * The purpose of this class is mainly to support the getClass() method
 * of the Object class above, but i can see it grow a bit further later on.
 * The class is final, there's only one definition of a class, it can not be
 * specialized in any way. Furthermore the constructor is made protected.
 * In combination with the final keyword, this makes this class only instantiable
 * by its ancestors, which only is the Object class and is exactly what we want.
 *
 * @package core
 * @todo can we come up with a better name without the underscore?
 * @todo look at visibility of the methods
 */
final class xarClass extends xarReflectable
{
    /**
     * Create a Class_ object based on an instance object
     *
     * @param Object $object any object
     */
    protected function __construct(xarObject $object)
    {
        $this->reflect = new ReflectionClass($object);
    }

    /**
     * Get an array of Property objects
     *
     * @return Property[] array of Property objects from the class
     */
    public function getProperties()
    {
        $ret = array();
        foreach ($this->reflect->getProperties() as $p) $ret[] = new xarProperty($this, $p->getName());
        return $ret;
    }

    /**
     * Return a property object by name from a class
     *
     * @param  string   $name Name of the property
     * @return Property Property object
     * @todo get rid of the underscore once DataPropertyMaster:getProperty is remodelled
     */
    final public function getProperty($name)
    {
        return new xarProperty($this, $name);
    }
}

/**
 * A class to model a property in PHP
 *
 * The purpose of this class i mainly to support the getProperty_() method
 * in the Object class above, but i can see it grow a bit further later on.
 * The class is final, there's only one definition of a property, it can not be
 * specialized in any way. The constructor is public here because of the getProperty
 * method in the Class_ class.
 *
 * @package core
 */
final class xarProperty extends xarReflectable
{
    /**
     * Create a Property object based on the class it is in
     *
     * @param Class_ $clazz the class object
     * @param string $name  the name of the property
     */
    public function __construct(xarClass $clazz, $name)
    {
        $this->reflect = new xarReflectionProperty($clazz->getName(), $name);
    }

    public function isPublic()
    {
        return $this->reflect->isPublic();
    }

    public function getValue(xarObject $object)
    {
        return $this->reflect->getValue($object);
    }
}

/**
 * Manage all the core class instances based a singleton pattern
 * Do not use it in PHP 5.2.x. PHP 5.3.x only.
 *
 * @package core
 */
final class xar extends xarObject
{
    static private $__instances = array();

    // An associative array to load eventually automatically a class
    static private $__import = array( 'xaroles' => 'modules.roles.xarclass.xaroles' );


    static public function init()
    {
        // Paranoid way to ensure that no static vars are remaining
        self::$__instances = array();
    }

    /**
     * Singleton pattern/factory
     *
     * @example
     *      $roles = xar::Roles();
     *      $roles = xar::Roles('modules.roles.xarclass.xaroles');
     *      $mask = xar::Mask('', $pargs);
     * @param string $classname: the classname to be instantiate
     * @param string $import: a possible failsafe import path
     * @param various $args: pass by values the constructor settings
     */
    static public function __callstatic($name, $arguments)
    {
        $c = count($arguments);
        // @todo see if we don't need something better to create class name in some cases
        $classname = 'xar' . $name;
        if (!array_key_exists($classname, self::$__instances)) {
            $import = $c !== 0 ? $arguments[0] : '';
            if (!class_exists($classname)) {
                // Try several ways to import the class
                if (!empty($arguments[0])) sys::import($arguments[0]);
                if (!class_exists($classname) && array_key_exists($classname, self::$__import)) {
                    sys::import(self::$__import[$classname]);
                }
            }
            if (class_exists($classname)) {
                switch ($c) {
                    case 0:
                    case 1:
                        self::$__instances[$classname] = new $classname();
                        break;
                    case 2:
                        self::$__instances[$classname] = new $classname($arguments[1], $arguments[2]);
                        break;
                    case 3:
                        self::$__instances[$classname] = new $classname($arguments[1], $arguments[2], $arguments[3]);
                        break;
                    case 4:
                    default:
                        self::$__instances[$classname] = new $classname($arguments[1], $arguments[2], $arguments[3], $arguments[4]);
                        break;
                }
            } else {
                throw new Exception();
            }
        } elseif ($c > 2) {
            switch ($c) {
                    // If constructors settings are passed again, calls it.
                case 2:
                    self::$__instances[$classname]->__construct($arguments[1], $arguments[2]);
                    break;
                case 3:
                    self::$__instances[$classname]->__construct($arguments[1], $arguments[2], $arguments[3]);
                    break;
                case 4:
                default:
                    self::$__instances[$classname]->__construct($arguments[1], $arguments[2], $arguments[3], $arguments[4]);
                    break;
            }
        }
        return self::$__instances[$classname];
    }
}

/**
 * Convenience class for keeping track of debugger operation
 *
 * @todo this is close to exceptions or logging than core, see also notes earlier
**/
class xarDebug extends xarObject
{
    public static $flags     = 0; // default off?
    public static $sqlCalls  = 0; // Should be in flags imo
    public static $startTime = 0; // Should not be here at all
    public static $logLevel =  0;

    /**
     * Start the logger/debug clock
     * a microtime() can be passed
     */
    public static function startTime($microtime = NULL)
    {
        if (self::$startTime === 0) {
            if ($microtime === NULL) $microtime = microtime();
            $lmtime = explode(' ', $microtime);
            self::$startTime = $lmtime[1] + $lmtime[0];
        }
    }

    /**
     * Get current time spent since startTime
     */
     public static function getTime()
     {
         $lmtime = explode(' ', microtime());
         $endTime = $lmtime[1] + $lmtime[0];
         $totalTime = ($endTime - self::$startTime);
         return $totalTime;
     }
}

/**
 * A base class for xarServer which integrates the minimal requirements to
 * run the bootstrap.
 */
class xarServerBone extends xarObject
{
    static private $__arrvars = array();
    //
    static private $__webRootPath = '';     // The web root as provided by the web server (might be virtual)
    static private $__webRootPathReal = ''; // The real web root path, where the files are

    static private $__bUseRealPath = FALSE; // Use the real path for the web root
    static private $__bCurrentWebRoot = NULL;
    static private $__pathWebRoot = NULL;
    static private $__pathWebRootReal = NULL;
    static private $__pathCurrent = NULL;

    /**
     * Gets a server variable
     *
     * Returns the value of $name server variable.
     * Accepted values for $name are exactly the ones described by the
     * {@link http://www.php.net/manual/en/reserved.variables.html PHP manual}.
     * If the server variable doesn't exist NULL is returned.
     *
     * @access public
     * @param name string the name of the variable
     * @return mixed value of the variable
     */
    final static public function getVar($name)
    {
        // @TODO: is there any servers var we don't want to cache here?
        if (!array_key_exists($name, self::$__arrvars)) {
            if (isset($_SERVER[$name])) {
                self::$__arrvars[$name] = $_SERVER[$name];
            } else if ($name == 'PATH_INFO') {
                self::$__arrvars[$name] = NULL;
            } else if (isset($_ENV[$name])) {
                self::$__arrvars[$name] = $_ENV[$name];
            } else if (getenv($name) !== FALSE) {
                self::$__arrvars[$name] = getenv($name);
            } else {
                self::$__arrvars[$name] = NULL;
            }
        }
        return self::$__arrvars[$name];
    }

    /**
     * Init once
     * We only want it to occur during sys initialization
     */
    final static public function initOnce()
    {
        if (!sys::isInit()) sys::fail();
        self::$__arrvars = array();
        self::$__webRootPath = '';
        self::$__webRootPathReal = '';
        self::$__bUseRealPath = FALSE; // Use the real path for the web root
        self::$__bCurrentWebRoot = NULL;
        self::$__pathWebRoot = NULL;
        self::$__pathWebRootReal = NULL;
        self::$__pathCurrent = NULL;
    }

    /**
     * Determine the web root in a safe way, returns a xarPath object
     * @param bool realpath: if TRUE returns a path not being a symlinks
     * if realpath is FALSE, it returns what is provided by the web server, could be a symlink
     */
    final public static function getWebRootPath($useRealPath = FALSE)
    {
        if (empty(self::$__webRootPath) || empty(self::$__webRootPathReal)) {
            $rootServer = xarServerBone::getVar('DOCUMENT_ROOT');
            // Validate the server root value (not '' or equal to 'off')
            if (empty($rootServer) || strcasecmp($rootServer, 'off') === 0) {

                $rel = xarRequestBone::getBaseDir();

                // On Apache Windows, SCRIPT_FILENAME returns normalized, but not on IIS
                $scriptpath = xarFileBone::dirname(xarServerBone::getVar('SCRIPT_FILENAME'));
                if (DIRECTORY_SEPARATOR === '\\') $scriptpath = str_replace('\\', '/', $scriptpath);

                if (!empty($rel)) {
                    $pos = strripos($scriptpath, $rel);

                    if ($pos !== FALSE) {
                        $rootServer = substr_replace($scriptpath, '', $pos, strlen($rel));
                    } else {
                        $msg = "\nError getting the root from the web server using script name and filename: \n\n";
                        $msg .= "SCRIPT_FILENAME: " . xarServerBone::getVar('SCRIPT_FILENAME') . "\n";
                        $msg .= "SCRIPT_NAME: " . xarServerBone::getVar('SCRIPT_NAME') . "\n\n";
                        throw new Exception($msg);
                    }
                } else {
                    $rootServer = $scriptpath;
                }
            }
            if (empty($rootServer)) throw new Exception('Error getting the root from the web server');

            self::$__pathWebRoot = xarPath::make($rootServer, xarPath::MODE_ABSOLUTE);
            $rootServerReal = realpath($rootServer);
            if ($rootServerReal != FALSE) {
                self::$__pathWebRootReal = xarPath::make(realpath($rootServer), xarPath::MODE_ABSOLUTE);
                self::$__webRootPathReal = self::$__pathWebRootReal->getAbs();
            }

            self::$__webRootPath = self::$__pathWebRoot->getAbs();
            self::$__bUseRealPath = $useRealPath && $rootServerReal != FALSE;
        }
        return $useRealPath ? self::$__pathWebRootReal : self::$__pathWebRoot;
    }

    /**
     * Determine the current web root
     */
     final public static function getCurrentPath($forceRefresh = FALSE)
     {
         if (self::$__pathCurrent === NULL || $forceRefresh) {
             if (empty(self::$__webRootPath)) self::getWebRootPath();
             // getcwd() returns resolved symlinks/alias on Apache. Can be lowercase too.
             // Todo: could we work differently and handle real paths just like getWebRoot()
             $dirCurrent = xarFileBone::dirname(xarServerBone::getVar('SCRIPT_FILENAME'));
             self::$__pathCurrent = xarPath::make($dirCurrent, xarPath::MODE_ABSOLUTE);
         }
         return self::$__pathCurrent;
     }

     final public static function isCurrentWebRoot($forceRefresh = FALSE)
     {
        if (self::$__pathCurrent === NULL || $forceRefresh || self::$__bCurrentWebRoot === NULL) {
            self::getCurrentPath($forceRefresh);
            self::$__bCurrentWebRoot = self::$__pathWebRoot->isEqual(self::$__pathCurrent, xarPath::MODE_ABSOLUTE);
        }
        return self::$__bCurrentWebRoot;
     }


    /**
     *  hasSymlinkRoot()
     * @return TRUE if the web root uses symlinks, FALSE otherwise
     */
     final public static function hasSymlinkRoot()
     {
        if (empty(self::$__webRootPath) || empty(self::$__webRootPathReal)) self::getWebRootPath();
        return self::$__webRootPath !== self::$__webRootPathReal;
     }
}

/**
 * xarRequestBone
 * a lightweight class to provide convenience features to the bootstrap
 */
class xarRequestBone extends xarObject
{
    static private $__bScriptValid = FALSE;
    static private $__objFile = NULL;
    static private $__stack = array();

    static private $__reqFileName = '';
    static private $__reqBaseDir = '';

    /**
     * Init once
     * We only want it to occur during sys initialization
     */
    final static public function initOnce()
    {
        if (!sys::isInit()) sys::fail();
        self::$__bScriptValid = FALSE;
        self::$__objFile = NULL;
        self::$__stack = array();

        // We compute the script file call
        $filename = '/' . ltrim(xarServerBone::getVar('SCRIPT_NAME'), '/');

        $arr = xarFileBone::pathinfo($filename, PATHINFO_DIRNAME | PATHINFO_BASENAME);
        self::$__reqFileName = $arr['basename'];
        self::$__reqBaseDir = $arr['dirname'];
    }

    final static public function getBaseName() { return self::$__reqFileName; }

    /**
     * Return the base dir relative web path from web root where
     * the entry point is
     */
    final static public function getBaseDir() { return self::$__reqBaseDir; }

    /**
     * Returns the path filename which can be reused for the web
     */
    final static public function getWebUrl() { return self::$__reqBaseDir . '/' . self::$__reqFileName; }

    /**
     *  Returns the PHP file calling
     */
    final static function getScriptFile()
    {
        if (self::$__bScriptValid && is_object($__objFile)) return $__objFile;
        self::$__bScriptValid = FALSE;
        $scriptFileName = realpath(xarServerBone::getVar('SCRIPT_FILENAME')); // Be careful as the realpath of a file is not the same as the realpath of a dir (symlink for index.php only)
        $arrScriptFile = xarFileBone::pathinfo($scriptFileName, PATHINFO_DIRNAME | PATHINFO_EXTENSION | PATHINFO_BASENAME | PATHINFO_BASENAME);
        // We need the realpath to be able to compare to the debug trace stack
        if (DIRECTORY_SEPARATOR === '/') {
            $arrScriptFile['dirname'] = rtrim(realpath($arrScriptFile['dirname']), '/');
        } else {
            $arrScriptFile['dirname'] = rtrim(str_replace('\\', '/', strtolower(realpath($arrScriptFile['dirname']))), '/');
        }
        self::$__stack = debug_backtrace();
        $last = end(self::$__stack);
        if (!isset($last) && !isset($last['file'])) sys::fail();
        $arrScriptStack = xarFileBone::pathinfo($last['file'], PATHINFO_DIRNAME | PATHINFO_EXTENSION | PATHINFO_BASENAME | PATHINFO_BASENAME);

        $t1 = $arrScriptFile === $arrScriptStack;
        if (!$t1) sys::fail(); // Something is terribly wrong. We might not want to go further. SCRIPT_NAME is used by xarServer/xarRequest. We need to be able to rely on it.

        // If path starts by '/' on *nix, it is considered as absolute.
        $oPath = xarPath::makeFromWeb(ltrim(self::$__reqBaseDir, '/'));

        $objFile = new xarFileSigned($oPath, self::$__reqFileName);
        self::$__bScriptValid = $objFile->isValid();
        if (self::$__bScriptValid) self::$__objFile = $objFile;
        return self::$__objFile;
    }
}

/**
 * xarResponseBone
 * a lightweight class to provide error messages before the bootsrap and the core are loaded
 */
class xarResponseBone extends xarObject
{
    static protected $_arrStatusCodes = array(200 => 'OK', 301 => 'Moved Permanently', 302 => 'Moved Temporarily',
        303 => 'See Other', 307 => 'Temporary Redirect', 403 => 'Forbidden', 404 => 'Not Found', 503 => 'Service Unavailable');

    static protected $_codeSent = 0;

    // @TODO Study this a bit more http://www.askapache.com/server-administration/php-errordocument.html
    // Does Apache with FastCGI still need to get header starting with Status Code?
    // http://bugs.php.net/bug.php?id=36705 => last message states that it works.
    // It doesn't seem to be required at all with FastCGI / PHP 5.3 and IIS7.
    final static public function sendStatusCode($code = 404, $location = '')
    {
        $code = intval($code);
        if (!array_key_exists($code, self::$_arrStatusCodes)) $code = 404;
        if (!headers_sent()) {
            self::$_codeSent = $code;
            switch ($code) {
                case 403: case 404:
                    header('HTTP/1.0 '. $code . ' '  . self::$_arrStatusCodes[$code], TRUE);
                    break;
                case 301: case 302: case 303: case 307:
                    header('Location: ' . $location, TRUE, $code);
                    break;
                case 503:
                    header('HTTP/1.0 ' . $code . ' ' . self::$_arrStatusCodes[$code], TRUE);
                    // Indicates the search engines to retry later
                    header('Retry-After: 3600');
                    break;
                case 200:
                    break;
            }
        }
    }
}

/**
 * A class to manage a given path and returned well-formed sub paths
 * It can work either in absolute mode, or in relative mode:
 * this can be determined automatically at creation, or
 * forced manually to be absolute.
 *
 * Instantiate a path:
 *    $newpath = xarPath::make('../lib/xarigami');
 *    $newpath = xarPath::make('/home/foobar/www/html/');
 */

class xarPath extends xarObject
{
    // Static members
    protected static $_absDefaultMode = TRUE;  // define the default mode for operations
    // Note : for the time being we set to TRUE to emulate the old way the precore worked.
    // Anyway full relative mode is not supported yet.
    protected static $_depthSystemWebRoot = 1;  // define the relative position of the system from the web root:
                                                // 0: system root = web root
                                                // 1: system root = web root parent directory
                                                // 2: system root = web root grand parent
    protected static $_webRootMethod = '';      // keep track of the method used to determine the root

    protected static $_webRootPath = '';        // the absolute web root path
    protected static $_sysRootPath = '';        // the absolute system web root path
    // Storage arrays
    protected static $_arrWebRoot = array();    // storage array for absolute web root path directories
    protected static $_arrSystemRoot = array(); // storage array for absolute web root path directories
    protected static $_arrCurrent = array();    // storage array for the current path directories
    protected static $_arrSysToWeb = array();   // storage array providing the relative path from sys to root
    protected static $_arrSysToCurr = array();  // storage array providing the relative path from sys to current array
    protected static $_bSysToCurrValid = FALSE;// flag to determine whether the current path has been computed
    // Instance members
    protected $_inputPath = '';                 // unchanged input path at creation time
    protected $_outputAbsNormPath = '';         // normalized absolute output path
    protected $_outputAbsOsPath = '';           // OS specific absolute output path
    protected $_outputRelNormPath = '';         // relative normalized output path (from system)
    protected $_outputRelOsPath = '';           // relative OS specific output path (from system)
    protected $_outputRelWebNormPath = ''; // relative normalized output path (from web root)
    protected $_outputRelWebOsPath = ''; // relative OS specific output path (from web root)
    protected $_outputRelCurrNormPath = ''; // relative normalized output path (from current directory)
    protected $_outputRelCurrOsPath = ''; // relative OS specific output path (from current directory)
    // Storage for instance
    protected $_arrAbsPath = array();           // storage array for absolute path directories
    protected $_arrRelPath = array();           // storage array for relative path directories
    protected $_bRelArrValid = FALSE;           // flag to determine if the relative path array has been computed
    protected $_bRelOsOutputValid = FALSE;      // flag to determine if the relative path output has been saved
    protected $_bRelNormOutputValid = FALSE;    // flag to determine if the relative path output has been saved
    protected $_bRelWebOsOutputValid = FALSE;   // flag to determine if the relative path output has been saved
    protected $_bRelWebNormOutputValid = FALSE; // flag to determine if the relative path output has been saved
    protected $_bRelCurrNormOutputValid = FALSE;// flag to determine if the relative path output has been saved
    protected $_bRelCurrOsOutputValid = FALSE;  // flag to determine if the relative path output has been saved

    protected $_arrAppendCache = array();       // cache array for append
    protected $_isInputAbsolute = NULL;         // determine if the input is absolute
    protected $_isAbsolute = NULL;              // bool defining the operating mode for the given instance
    protected $_mode = self::MODE_DEFAULT;      // Keep the mode information (absolute, relative, from input, default)
    protected $_from = self::FROM_SYSTEMROOT;   // The input might be relative from various references

    // Static methods
    final public static function initStatic()
    {
        if (sys::isInit()) {
            self::$_absDefaultMode = TRUE;
            self::$_depthSystemWebRoot = 1;
            self::$_webRootMethod = '';
            self::$_webRootPath = '';
            self::$_sysRootPath = '';
            self::$_arrWebRoot = array();
            self::$_arrSystemRoot = array();
            self::$_arrCurrent = array();
            self::$_arrSysToWeb = array();
            self::$_arrSysToCurr = array();
            self::$_bSysToCurrValid = FALSE;
        }
    }

    /**
     * Safe way to prepare a path with a trailing slash formatted for a specific OS
     */
    final public static function addOsTrailingSlash($path, $what = DIRECTORY_SEPARATOR)
    {
        if (strlen($path) > 1 && $path !== DIRECTORY_SEPARATOR) {
            return rtrim($path, $what) . DIRECTORY_SEPARATOR;
        }
    }

    /*
     * Prepare and get the web root path array
     */
    final protected static function _getWebRootArray()
    {
        if (count(self::$_arrWebRoot) === 0) {
            $oPath = xarServerBone::getWebRootPath(); // ensure the web root is already computed
            self::$_webRootPath = $oPath->getAbs();
            self::$_arrWebRoot = $oPath->getInternalArray();
        }
        return self::$_arrWebRoot;
    }

    /*
     * Prepare and get the system root path array
     */
    final protected static function _getSystemRootArray()
    {
        if (count(self::$_arrSystemRoot) === 0) {
            // Is the web root already computed?
            if (count(self::$_arrWebRoot) === 0) {
                self::_getWebRootArray();
            }
            self::$_arrSystemRoot = self::$_arrWebRoot;

            if (self::$_depthSystemWebRoot !== 0) {
                $size = count(self::$_arrSystemRoot);
                //unset(self::$_arrSystemRoot[$size-1]); // Remove the trailing slash
                $end = $size-1;
                $start = $end - self::$_depthSystemWebRoot;
                for ($i = $start; $i !== $end; $i++) {
                    // This is not made to be safe. We preserve the first slash for *Nix and the C:\ for Windows.
                    // @todo: windows network path could be handled here.
                    if (DIRECTORY_SEPARATOR === '/' && $i === 0 || DIRECTORY_SEPARATOR === '\\' && $i < 2) continue;
                    unset(self::$_arrSystemRoot[$i]);
                }
            }
            self::$_arrSystemRoot = array_values(self::$_arrSystemRoot);
            $c = count(self::$_arrSystemRoot);
            if ($c !== 0 && self::$_arrSystemRoot[$c-1] !== '') {
                self::$_arrSystemRoot[$c] = ''; // Restore the trailing slash
            }
        }
        return self::$_arrSystemRoot;
    }

    /*
     * Prepare and get the current directory path array
     */
    final protected static function _getCurrentArray($forceRefresh = FALSE)
    {
        if (count(self::$_arrCurrent) === 0 || $forceRefresh) {
            // Is the web root already computed?
            if (count(self::$_arrWebRoot) === 0) {
                self::_getWebRootArray();
            }
            self::$_arrCurrent = xarServerBone::getCurrentPath($forceRefresh)->getInternalArray(xarPath::MODE_ABSOLUTE);
        }
        return self::$_arrCurrent;
    }

     /*
      * Get the absolute reference dir array depending of the from source
      */
     final protected function _getAbsOriginArray($from = NULL, $forceRefresh = FALSE)
     {
        if ($from === NULL) $from = $this->_from;
         switch ($from) {
            default:
            case self::FROM_SYSTEMROOT:
                return count(self::$_arrSystemRoot) !== 0 ? self::$_arrSystemRoot : self::_getSystemRootArray();

            case self::FROM_WEBROOT:
                return count(self::$_arrWebRoot) !== 0 ? self::$_arrWebRoot : self::_getWebRootArray();

            case self::FROM_CURRENTDIR:
                return count(self::$_arrCurrent) !== 0 ? self::$_arrCurrent : self::_getCurrentArray($forceRefresh);
        }
    }

    /*
     * Get the relative reference dir depending of the from source
     */
     final protected function _getRelOriginArray($from = NULL, $forceRefresh = FALSE)
     {
        if ($from === NULL) $from = $this->_from;
        switch ($from) {
            default:
            case self::FROM_SYSTEMROOT:
                return array();

            case self::FROM_WEBROOT:
                return self::$_depthSystemWebRoot === 0 ? array() : (count(self::$_arrSysToWeb) === 0 ? self::_getSysToWebArray() : self::$_arrSysToWeb);

            case self::FROM_CURRENTDIR:
                return self::$_bSysToCurrValid && !$forceRefresh ? self::$_arrSysToCurr : self::_getSysToCurrArray();
        }
     }

    /**
     * Get the relative path array from the system root to the web root
     * Does not have a trailing slash!
     */
    final protected static function _getSysToWebArray()
    {
        if (self::$_depthSystemWebRoot === 0) return array();
        if (count(self::$_arrSysToWeb) !==0) return self::$_arrSysToWeb;
        $arr = array();
        $arr1 = self::_getWebRootArray();
        $c1 = count($arr1);
        $arr2 = self::_getSystemRootArray();
        $c2 = count($arr2);

        // Remove trailing slashes
        unset($arr1[$c1-1]);
        unset($arr2[$c2-1]);

        for ($i = 0; $i <$c2-1; $i++) {
            if ($arr1[$i] !== $arr2[$i]) throw new Exception('Why do we have a system root different from web root?'); // Should never happen
        }

        for ($i = $c2-1; $i < $c1-1; $i++) {
            $arr[] = $arr1[$i];
        }

        return self::$_arrSysToWeb = $arr;
    }

    /**
     * Get the relative path array from the system root to the current directory (supposed to be in the web root)
     * Does not have a trailing slash!
     */
    final protected static function _getSysToCurrArray($forceRefresh = FALSE)
    {
        if (!self::$_bSysToCurrValid || $forceRefresh) {
            if (xarServerBone::isCurrentWebRoot($forceRefresh)) {
                self::$_arrSysToCurr = self::$_arrSysToWeb = self::_getSysToWebArray();
            } else {
                self::$_arrSysToCurr = array();
                $arr1 = self::_getCurrentArray(FALSE);
                $c1 = count($arr1);
                $arr2 = self::_getSystemRootArray(); // Refresh is already done in isCurrentWebBoot
                $c2 = count($arr2);

                // Remove trailing slashes
                unset($arr1[$c1-1]);
                unset($arr2[$c2-1]);

                for ($i = 0; $i <$c2-1; $i++) {
                    if ($arr1[$i] !== $arr2[$i]) throw new Exception('Is it normal we have a current root different from web root?'); // Might happen?
                }

                for ($i = $c2-1; $i < $c1-1; $i++) {
                    self::$_arrSysToCurr[] = $arr1[$i];
                }
            }
            self::$_bSysToCurrValid = TRUE;
        }
        return self::$_arrSysToCurr;
    }

    /**
     * Returns the internal array (the absolute or the relative one)
     * This is not a reference, so modifying it has no effect
     */
     final public function getInternalArray($mode = self::MODE_ABSOLUTE)
     {
        if ($mode === self::MODE_DEFAULT) $mode = self::$_absDefaultMode ? self::MODE_ABSOLUTE : self::MODE_RELATIVE;
        if ($mode === self::MODE_INPUT) $mode = $this->_isInputAbsolute ? self::MODE_ABSOLUTE : self::MODE_RELATIVE;
        switch ($mode) {
            default:
            case self::MODE_ABSOLUTE:
                if (!$this->_isAbsolute && count($this->_arrAbsPath) === 0 && $this->_bRelArrValid) {
                    $arrRoot = self::_getSystemRootArray();
                    $this->_arrAbsPath = $this->_tidy(self::arrayAppend($arrRoot, $this->_arrRelPath));
                }
                return $this->_arrAbsPath;

            case self::MODE_RELATIVE:
                if ($this->_isAbsolute && !$this->_bRelArrValid) {
                    $this->_arrRelPath = $this->_gapArray(xarPath::FROM_SYSTEMROOT);
                    $this->_bRelArrValid = TRUE;
                }
                return $this->_arrRelPath;
        }
     }

    /**
     * Returns the system root. OS format.
     * Note: for more convenience use the xarPath::makeSystemRootPath() to create
     * a xarPath instance using the System Root
     */
    final public static function getSystemRoot()
    {
        if (empty(self::$_sysRootPath)) {
            if (count(self::$_arrSystemRoot) === 0) self::_getSystemRootArray();
            self::$_sysRootPath = implode(DIRECTORY_SEPARATOR, self::$_arrSystemRoot);
        }
        return self::$_sysRootPath;
    }

    // Intance management methods
    const MODE_DEFAULT = 0;      // Use the mode defined by default in static var $_absDefaultMode
    const MODE_ABSOLUTE = 1;     // Force the path to be absolute
    const MODE_RELATIVE = 2;     // Force the path to be relative
    const MODE_INPUT = 3;        // The original path mode determines whether it is absolute or not
    /**
     * Create an instance of xarPath.
     * @param $path the input $path
     * @param $makeabsolute
     */
    final public static function make($path, $mode = self::MODE_DEFAULT, $from = self::FROM_SYSTEMROOT)
    {
        return DIRECTORY_SEPARATOR === '/' ? new xarNixPath($path, $mode, $from) : new xarWinPath($path, $mode, $from);
    }

    /**
     * Create an instance of xarPath possibly relative from web root (input only!).
     * @param $path the input $path
     * @param $makeabsolute
     */
    final public static function makeFromWeb($path)
    {
        return DIRECTORY_SEPARATOR === '/' ? new xarNixPath($path, self::MODE_RELATIVE, self::FROM_WEBROOT) : new xarWinPath($path, self::MODE_RELATIVE, self::FROM_WEBROOT);
    }

    /**
     * Create an instance of xarPath possibly relative from web root (input only!).
     * @param $path the input $path
     * @param $makeabsolute
     */
    final public static function makeFromCurrent($path)
    {
        return DIRECTORY_SEPARATOR === '/' ? new xarNixPath($path, self::MODE_RELATIVE, self::FROM_CURRENTDIR) : new xarWinPath($path, self::MODE_RELATIVE, self::FROM_CURRENTDIR);
    }



    /**
     * Create an instance of xarPath initialized with
     * the system root.
     */
    final public static function makeSystemRootPath()
    {
        return DIRECTORY_SEPARATOR === '/' ? new xarNixPath(self::_getSystemRootArray()) : new xarWinPath(self::_getSystemRootArray());
    }

    // Instance overridden methods
    protected function _normalize()
    { /* Mostly abstract */ }
    protected function _tidy($arrPath)
    { /* Mostly abstract */ }
    protected function _initForArr()
    { /* Mostly abstract */ }

    /**
     * Append an array to another keeping the original key order, and reindexing then
     */
     final static function arrayAppend(Array $arr1, Array $arr2)
     {
        $arr = array();
        foreach ($arr1 as $element) $arr[] = $element;
        foreach ($arr2 as $element) $arr[] = $element;
        return $arr;
     }

    /**
     * Build a path relative or absolute to the system root
     * @params
     *      path: the relative or absolute path string
     *            or a path array
     *      makeabsolute: make it absolute
     */
    protected function __construct($path, $mode = self::MODE_DEFAULT, $from = self::FROM_SYSTEMROOT)
    {
        $this->_mode = $mode;
        $this->_from = $from;
        if (!is_array($path)) {
             // The default static operating mode can force to make absolute paths only, no matter what.
            switch ($mode) {
                default:
                case self::MODE_DEFAULT:
                    $this->_isAbsolute = self::$_absDefaultMode;
                    break;
                case self::MODE_ABSOLUTE:
                    $this->_isAbsolute = TRUE;
                    break;
                case self::MODE_RELATIVE:
                    $this->_isAbsolute = FALSE;
                    break;
                case self::MODE_INPUT:
                    // This cannot be determined here.
                    break;
            }
            // We preserve the original path, can be used to send back the input to the user or for debug.
            $this->_inputPath = trim($path);
            $this->_normalize();
        } else {
            // We pass an array with an absolute path array
            $this->_isAbsolute = TRUE;
            $this->_arrAbsPath = $path;
            // We may need some very little OS specific initialization
            $this->_initForArr();
        }
    }

    /**
     * Retrieve the full path conforming to the OS file system or to the normalized definition
     * @param $osSpec if FALSE returns a normalized path
     *                if TRUE returns an OS specific path
     */
    final public function getAbs($osSpec=FALSE)
    {
        if ($osSpec) {
            if (empty($this->_outputAbsOsPath))
            {
                if (count($this->_arrAbsPath) === 0 && !$this->_isAbsolute && $this->_bRelArrValid) {
                    $arrRoot = self::_getSystemRootArray();
                    $this->_arrAbsPath = $this->_tidy(self::arrayAppend($arrRoot, $this->_arrRelPath));
                }

                $this->_outputAbsOsPath = implode(DIRECTORY_SEPARATOR, $this->_arrAbsPath);
                if (DIRECTORY_SEPARATOR === '/' && empty($this->_outputAbsNormPath)) {
                    $this->_outputAbsNormPath = $this->_outputAbsOsPath;
                }
            }
            return $this->_outputAbsOsPath;
        } else {
            if (empty($this->_outputAbsNormPath)) {
                if (count($this->_arrAbsPath) === 0 && !$this->_isAbsolute && $this->_bRelArrValid) {
                    $arrRoot = self::_getSystemRootArray();
                    $this->_arrAbsPath = $this->_tidy(self::arrayAppend($arrRoot, $this->_arrRelPath));
                }
                $this->_outputAbsNormPath = implode('/', $this->_arrAbsPath);
                if (DIRECTORY_SEPARATOR === '/' && empty($this->_outputAbsNormPath)) {
                    $this->_outputAbsOsPath = $this->_outputAbsNormPath;
                }
            }
            return $this->_outputAbsNormPath;
        }
    }

    /**
     * Prepares the path relative or absolute, to be returned, and to be directly used, as a
     *  relative path to the current directory
     * example:
     * $path = xarPath::make($inputunsafepath, xarPath::MODE_INPUT);
     * if (is_dir($path->getRel())) { }
     *
     * @param $osSpec if FALSE returns a normalized path
     *                if TRUE returns an OS specific path
     */
    final public function getRel($osSpec=FALSE, $forceRefresh=FALSE)
    {
        // Get cached values
        if (!$forceRefresh) {
            if ($osSpec) {
                    if ($this->_bRelCurrOsOutputValid) return $this->_outputRelCurrOsPath;
                } else {
                    if ($this->_bRelCurrNormOutputValid) return $this->_outputRelCurrNormPath;
            }
        }

        // If we are in absolute mode with no relative path
        if ($this->_isAbsolute && !$this->_bRelArrValid) {
            $this->_gapArray(xarPath::FROM_SYSTEMROOT);
        }

        $arrPath = array(); // We do not store the path in the class.

        // Eventually refresh the sys to curr array
        if (!self::$_bSysToCurrValid || $forceRefresh) self::_getSysToCurrArray($forceRefresh);

        $c = count(self::$_arrSysToCurr);
        $size = count($this->_arrRelPath);

        if ($size === 0) {
            if (self::$_depthSystemWebRoot === 0) {
                $this->_outputRelCurrOsPath = '.'.DIRECTORY_SEPARATOR;
                $this->_outputRelCurrNormPath = './';
            } else {
                $this->_outputRelCurrOsPath = str_repeat('..'.DIRECTORY_SEPARATOR, $c);
                $this->_outputRelCurrOsPath = str_repeat('../', $c);
            }
            $this->_bRelCurrOsOutputValid = TRUE;
            $this->_bRelCurrNormOutputValid = TRUE;
            return $osSpec ? $this->_outputRelCurrOsPath : $this->_outputRelCurrNormPath;
        }

        if ($c !== 0) {
            // System root is not the current directory
            $bSubWebPath = TRUE;

            $next = $c > $size ? $size : $c;

            if (DIRECTORY_SEPARATOR === '/') {
                for ($i=0; $i !== $next; $i++) {
                    if ($this->_arrRelPath[$i] !== self::$_arrSysToCurr[$i]) {
                        $bSubWebPath = FALSE;
                        break;
                    }
                }
            } else {
                for ($i=0; $i !== $next; $i++) {
                    if (strcasecmp($this->_arrRelPath[$i], self::$_arrSysToCurr[$i]) !== 0) {
                        $bSubWebPath = FALSE;
                        break;
                    }
                }
            }
            $next = $i > 0 ? $i : 0;

            if ($bSubWebPath) {
                $arrPath = array('.');
                // This is a sub web path, we just need to remove the system web path and we are done
                for ($j=$next; $j !== $size; $j++) {
                    $arrPath[] = $this->_arrRelPath[$j];
                }
            } else {
                // This is not a sub web path, we need to go to the parent directories.
                // array_fill could the same, but maybe not that fast
                for ($j=$next; $j < $c; $j++) {
                    $arrPath[] = '..';
                }
                for ($j=$next; $j < $size; $j++) {
                    $arrPath[] = $this->_arrRelPath[$j];
                }
            }

            if ($osSpec) {
                $this->_outputRelCurrOsPath = implode(DIRECTORY_SEPARATOR, $arrPath);
                $this->_bRelCurrOsOutputValid = TRUE;
                return $this->_outputRelCurrOsPath;
            } else {
                $this->_outputRelCurrNormPath = implode('/', $arrPath);
                if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelOsOutputValid) {
                    $this->_outputRelCurrOsPath = $this->_outputRelCurrNormPath;
                    $this->_bRelCurrOsOutputValid = TRUE;
                }
                $this->_bRelCurrNormOutputValid = TRUE;
                return $this->_outputRelCurrNormPath;
            }

        } else {
            // System root is the current directory.
            // Should we cache something else?
            if (count($this->_arrRelPath) === 0) return $osSpec ? '.'.DIRECTORY_SEPARATOR :'./';
            if ($osSpec) {
                if (!$this->_bRelOsOutputValid) {
                    if ($this->_arrRelPath[0] !== '..') {
                        $this->_outputRelOsPath = implode(DIRECTORY_SEPARATOR, $this->_arrRelPath);
                    } else {
                        $this->_outputRelOsPath = '.'.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $this->_arrRelPath);
                    }
                    if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelNormOutputValid) {
                        $this->_outputRelNormPath = $this->_outputRelOsPath;
                        $this->_bRelNormOutputValid = TRUE;
                    }
                    $this->_bRelOsOutputValid = TRUE;
                }
                return $this->_outputRelOsPath;
            } else {
                if (!$this->_bRelNormOutputValid) {
                    if ($this->_arrRelPath[0] !== '..') {
                        $this->_outputRelNormPath = implode('/', $this->_arrRelPath);
                    } else {
                        $this->_outputRelNormPath = '.'.DIRECTORY_SEPARATOR.implode('/', $this->_arrRelPath);
                    }
                    if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelOsOutputValid) {
                        $this->_outputRelOsPath = $this->_outputRelNormPath;
                        $this->_bRelOsOutputValid = TRUE;
                    }
                    $this->_bRelNormOutputValid = TRUE;
                }
                return $this->_outputRelNormPath;
            }
        }
    }

    /**
     * Retrieve the path relative or absolute. It respects the MODE selected.
     * Beware relative paths are relative to system root!
     * Use getRel() to get a path relative to current path.
     *
     * @param $osSpec if FALSE returns a normalized path
     *                if TRUE returns an OS specific path
     */
    final public function get($osSpec=FALSE, $forceRefresh=FALSE)
    {
        if ($this->_isAbsolute) {
            if ($osSpec) {
                if (!empty($this->_outputAbsOsPath)) return $this->_outputAbsOsPath;
            } else {
                if (!empty($this->_outputAbsNormPath)) return $this->_outputAbsNormPath;
            }
            return $this->getAbs($osSpec);
        } else {
            if (!$forceRefresh) {
                if ($osSpec) {
                    if ($this->_bRelCurrOsOutputValid) return $this->_outputRelCurrOsPath;
                 } else {
                    if ($this->_bRelCurrNormOutputValid) return $this->_outputRelCurrNormPath;
                 }
            }
            return $this->getRel($osSpec, $forceRefresh);
        }
    }

    /**
     * Get the parent (or grandparent)directory of the path set (absolute or relative)
     * Might be unsafe for Windows network paths
     */
     final public function getParent($parentLevel=1, $osSpec=FALSE)
     {
        $parent = _getParentArray($parentLevel);
        return implode($osSpec ? DIRECTORY_SEPARATOR : '/', $parent);
     }

     /**
     * Get the parent xarPath
     * Might be unsafe for Windows network paths
     */
     final public function getParentPath($parentLevel=1)
     {
        $parent = _getParentArray($parentLevel);
        return self::make($parent, $this->_mode);
     }

    /**
     * Get the parent (or grandparent)directory array of the path set (absolute or relative)
     * Might be unsafe for Windows network paths
     */
     final protected function _getParentArray($parentLevel=1)
     {
        $parent = array();

        // Get the dir array
        if ($this->_isAbsolute) {
            $arr = self::$_arrAbsPath;
        } else {
            $arr = self::$_arrRelPath;
        }

        // Eventually remove the trailing slash
        $c = count($arr);
        if ($c > 1 && $arr[$c-1] === '') {
            unset($arr[$c-1]);
            $c--;
        }

        if ($this->_isAbsolute) {
            // We need to preserve the / (*nix )or C: (Windows)
            if ($c > $parentLevel+1) {
                for($i=0; $i !== $c-$parentLevel; $i++) {
                    $parent[$i] = $arr[$i];
                }
                $parent[$c-$parentLevel] = ''; // Add the trailing slash
            } else {
                // We are already getting at root level. Cannot go upper
                $parent = DIRECTORY_SEPARATOR === '/' ? array('') : array($arr[0], '');
            }
        } else {
           if ($c - $parentLevel > 0) {
                for($i=0; $i !== $c - $parentLevel; $i++) {
                    $parent[$i] = $arr[$i];
                }
                $parent[$parentLevel-$c] = '';
           } elseif ($c === $parentLevel) {
                $parent = array('.', ''); // That's ./
           } else {
                for($i=0; $i !== $parentLevel-$c; $i++) {
                    $parent[$i] = '..';
                }
                $parent[$parentLevel-$c] = '';
           }
        }

        return $parent;
     }

    /*
     * Compare a xarPath instance to another one
     */
    final public function isEqual(xarPath $oPath, $mode = self::MODE_ABSOLUTE)
    {
        return $this->getInternalArray($mode) === $oPath->getInternalArray($mode);
    }

    /**
     * Return the path relative to the system root (conforming to system.config.php directory layout)
     */
    final public function forSys($osSpec=FALSE)
    {
        // If we are in absolute mode with no relative path
        if ($this->_isAbsolute && !$this->_bRelArrValid) {
            $this->_gapArray(xarPath::FROM_SYSTEMROOT);
        }
        if ($osSpec) {
            if (!$this->_bRelOsOutputValid) {
                $this->_outputRelOsPath = implode(DIRECTORY_SEPARATOR, $this->_arrRelPath);
                if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelNormOutputValid) {
                    $this->_outputRelNormPath = $this->_outputRelOsPath;
                    $this->_bRelNormOutputValid = TRUE;
                }
                $this->_bRelOsOutputValid = TRUE;
            }
            return $this->_outputRelOsPath;
        } else {
            if (!$this->_bRelNormOutputValid) {
                $this->_outputRelNormPath = implode('/', $this->_arrRelPath);
                if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelOsOutputValid) {
                    $this->_outputRelOsPath = $this->_outputRelNormPath;
                    $this->_bRelOsOutputValid = TRUE;
                }
                $this->_bRelNormOutputValid = TRUE;
            }
            return $this->_outputRelNormPath;
        }
    }

    /**
     * Prepares the path relative or absolute, to be returned, and to be directly used, as a
     *  relative path to the web root.
     * example:
     * $path = xarPath::make($inputunsafepath, xarPath::MODE_INPUT);
     * if (is_dir($path->forWeb())) { }
     *
     * @param $osSpec if FALSE returns a normalized path
     *                if TRUE returns an OS specific path
     */
    final public function forWeb($osSpec=FALSE)
    {
        // Get cached values
        if ($osSpec) {
            if ($this->_bRelWebOsOutputValid) return $this->_outputRelWebOsPath;
        } else {
            if ($this->_bRelWebNormOutputValid) return $this->_outputRelWebNormPath;
        }

        // If we are in absolute mode with no relative path
        if ($this->_isAbsolute && !$this->_bRelArrValid) {
            $this->_gapArray(xarPath::FROM_SYSTEMROOT);
        }

        $arrPath = array(); // We do not store the path in the class.

        // This should not happen, but just in case
        if (!$this->_bRelArrValid) throw new Exception('Relative path internal array not computed');

        $size = count($this->_arrRelPath);
        if ($size === 0) {
            if (self::$_depthSystemWebRoot === 0) {
                $this->_outputRelWebOsPath = '.'.DIRECTORY_SEPARATOR;
                $this->_outputRelWebNormPath = './';
            } else {
                $this->_outputRelWebOsPath = str_repeat('..'.DIRECTORY_SEPARATOR, self::$_depthSystemWebRoot);
                $this->_outputRelWebOsPath = str_repeat('../', self::$_depthSystemWebRoot);
            }
            $this->_bRelWebOsOutputValid = TRUE;
            $this->_bRelWebNormOutputValid = TRUE;
            return $osSpec ? $this->_outputRelWebOsPath : $this->_outputRelWebNormPath;
        }

        if (self::$_depthSystemWebRoot !== 0) {
            // System root is not the web root
            if (count(self::$_arrSysToWeb) === 0) self::_getSysToWebArray();

            $bSubWebPath = TRUE;
            if ($size < self::$_depthSystemWebRoot) {
                $bSubWebPath = FALSE;
            } else {
                if (DIRECTORY_SEPARATOR === '/') {
                    for ($i=0; $i !== self::$_depthSystemWebRoot; $i++) {
                        if ($this->_arrRelPath[$i] !== self::$_arrSysToWeb[$i]) {
                            $bSubWebPath = FALSE;
                            break;
                        }
                    }
                } else {
                    for ($i=0; $i !== self::$_depthSystemWebRoot; $i++) {
                        if (strcasecmp($this->_arrRelPath[$i], self::$_arrSysToWeb[$i]) !== 0) {
                            $bSubWebPath = FALSE;
                            break;
                        }
                    }
                }
            }
            if ($bSubWebPath) {
                $arrPath = array('.');
                // This is a sub web path, we just need to remove the system web path and we are done
                for ($j=self::$_depthSystemWebRoot; $j !== $size; $j++) {
                    $arrPath[] = $this->_arrRelPath[$j];
                }
            } else {
                // This is not a sub web path, we need to go to the parent directories.
                // array_fill could the same, but maybe not that fast
                for ($j=0; $j !== self::$_depthSystemWebRoot; $j++) {
                    $arrPath[] = '..';
                }
                for ($j=0; $j !== $size; $j++) {
                    $arrPath[] = $this->_arrRelPath[$j];
                }
            }

            if ($osSpec) {
                $this->_outputRelWebOsPath = implode(DIRECTORY_SEPARATOR, $arrPath);
                $this->_bRelWebOsOutputValid = TRUE;
                return $this->_outputRelWebOsPath;
            } else {
                $this->_outputRelWebNormPath = implode('/', $arrPath);
                if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelOsOutputValid) {
                    $this->_outputRelWebOsPath = $this->_outputRelWebNormPath;
                    $this->_bRelWebOsOutputValid = TRUE;
                }
                $this->_bRelWebNormOutputValid = TRUE;
                return $this->_outputRelWebNormPath;
            }

        } else {
            // System root is the web root.
            // @todo: this is a copy and paste of get(), the part where the relative path is returned
            // if we call get() here, we made it slower. Find a better way.
            // Should we cache something else?
            if (!$this->_bRelArrValid || count($this->_arrRelPath) === 0) return $osSpec ? '.'.DIRECTORY_SEPARATOR :'./';
            if ($osSpec) {
                if (!$this->_bRelOsOutputValid) {
                    if ($this->_arrRelPath[0] !== '..') {
                        $this->_outputRelOsPath = implode(DIRECTORY_SEPARATOR, $this->_arrRelPath);
                    } else {
                        $this->_outputRelOsPath = '.'.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $this->_arrRelPath);
                    }
                    if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelNormOutputValid) {
                        $this->_outputRelNormPath = $this->_outputRelOsPath;
                        $this->_bRelNormOutputValid = TRUE;
                    }
                    $this->_bRelOsOutputValid = TRUE;
                }
                return $this->_outputRelOsPath;
            } else {
                if (!$this->_bRelNormOutputValid) {
                    if ($this->_arrRelPath[0] !== '..') {
                        $this->_outputRelNormPath = implode('/', $this->_arrRelPath);
                    } else {
                        $this->_outputRelNormPath = '.'.DIRECTORY_SEPARATOR.implode('/', $this->_arrRelPath);
                    }
                    if (DIRECTORY_SEPARATOR === '/' && !$this->_bRelOsOutputValid) {
                        $this->_outputRelOsPath = $this->_outputRelNormPath;
                        $this->_bRelOsOutputValid = TRUE;
                    }
                    $this->_bRelNormOutputValid = TRUE;
                }
                return $this->_outputRelNormPath;
            }
        }
    }

    /**
     *  Create a new xarPath instance by appending a normalised subdir to the current instance
     */
    public function getSubPath($subdir)
    {
        $arrPathToAppend = explode('/', trim($subdir));
        $size = count($arrPathToAppend);
        if ($this->_isAbsolute) {
            $arrPath = $this->_arrAbsPath;
        } else {
            $arrPath = $this->_arrRelPath;
        }

        $size = count($arrPathToAppend);

        for ($i = 0; $i !== $size; $i++) {
            $arrPath[] = $arrPathToAppend[$i];
        }

        return xarPath::make($this->_tidy($arrPath), $this->_mode, self::FROM_PATH);
    }

    /**
     * Append a relative path to the current instance
     *
     * @param   $path       is a relative normalized path
     *          $osSpec     if TRUE returns an OS specific path
     *                      if FALSE returns a normalized path
     */
    public function append($path, $osSpec=FALSE)
    {
        if (!array_key_exists($osSpec ? '#'.$path : $path, $this->_arrAppendCache)) {
            $sep = $osSpec ? DIRECTORY_SEPARATOR : '/';
            if ($this->_isAbsolute) {
                $arrPath = $this->_arrAbsPath;
            } else {
                $arrPath = $this->_arrRelPath;
                $this->_bRelArrValid = TRUE;
            }
            // It is a bit faster to make manually the merge instead of using array_values(array_merge())
            $arrPathToAppend = explode($sep, trim($path));
            $size = count($arrPathToAppend);
            for ($i = 0; $i !== $size; $i++) {
                $arrPath[] = $arrPathToAppend[$i];
            }
            $this->_arrAppendCache[$osSpec ? '#'.$path : $path] = implode('/', $this->_tidy($arrPath));
        }
        return $this->_arrAppendCache[$osSpec ? '#'.$path : $path];
    }

    const FROM_SYSTEMROOT = 0;
    const FROM_WEBROOT = 1;
    const FROM_CURRENTDIR = 2;
    const FROM_PATH = 3;
    /**
     * Get the relative path to the destination array path from the current path
     * Resolves things based on absolute path. Safer but possibly longer.
     *
     * @param $from: from where we need to build our path
     * @param $arrPath: pass a path array as an absolute reference
     */
     final protected function _gapArray($from=self::FROM_SYSTEMROOT, $arrPath=NULL)
     {
        $arrGap = array(); // Neutral relative path is now empty
        if ($arrPath === NULL) $arrPath = $this->_getAbsOriginArray($from, FALSE);

        if (count($this->_arrAbsPath) === 0 && $this->_bRelArrValid) {
            // Compute the absolute path if we only have the relative one
            $this->_arrAbsPath = $this->_tidy(self::arrayAppend(self::_getSystemRootArray(), $this->_arrRelPath));
        }
        $sizeTo = count($this->_arrAbsPath)-1; // The minus 1 is to remove the trailing slash.
        $sizeFrom = count($arrPath)-1;
        $sizeCom = min($sizeTo, $sizeFrom);
        $i=0;
        if (DIRECTORY_SEPARATOR === '/') {
            while ($i < $sizeCom) {
                if ($this->_arrAbsPath[$i] !== $arrPath[$i]) break;
                ++$i;
            } // We should get the first different element index in $i
        } else {
            // We need to be careful to be case insensible
            // Otherwise, a c:\ would not work with the C:\
            while ($i < $sizeCom) {
                if (strcasecmp($this->_arrAbsPath[$i], $arrPath[$i]) !== 0) break;
                ++$i;
            } // We should get the first different element index in $i
        }
        if ($sizeTo === $sizeFrom && $sizeTo === $i) {
            // Already initialized with an empty string
        } else {
            for ($k = $i; $k < $sizeFrom; $k++) {
                $arrGap[] = '..';
            }
            for ($k = $i; $k < $sizeTo; $k++) {
                $arrGap[] = $this->_arrAbsPath[$k];
            }
        }
        // Add the trailing slash
        $size = count($arrGap);
        if ($size !== 0 && $arrGap[$size-1] !== '') $arrGap[] = '';
        // Cache if it is relative path from system root
        if ($from === self::FROM_SYSTEMROOT) {
            $this->_arrRelPath = $arrGap;
            $this->_bRelArrValid = TRUE;
        }
        return $arrGap;
    }

    public function isExist()
    {
        return is_dir(DIRECTORY_SEPARATOR === '/' ? $this->getAbs() : $this->getAbs(TRUE));
    }

    final public function gap($from=self::FROM_SYSTEMROOT, $osSpec=FALSE)
    {
       return implode($osSpec ? DIRECTORY_SEPARATOR : '/', $this->_gapArray($from));
    }

    final public function gapFromPath(xarPath $path, $osSpec=FALSE)
    {
        return implode($osSpec ? DIRECTORY_SEPARATOR : '/', $this->_gapArray(self::FROM_PATH, $path->getInternalArray(self::MODE_ABSOLUTE)));
    }
}

class xarNixPath extends xarPath
{
    /**
     * Normalize the input path for the given OS (Unix/Linux style assumed here)
     */

    final public function isExist()
    {
        return is_dir($this->getAbs());
    }

    final protected function _normalize()
    {
        if (strlen($this->_inputPath) > 3 && !preg_match('/^[^:;*?"\\\\<>=&@#{}[\]()\'|\r\n]+$/i', $this->_inputPath)) throw new Exception('invalid path');

        // We start exploding the path in directories
        // We are using some Unix/Linux system. Separator is only /
        $arrPath = explode('/', $this->_inputPath);

        // We determine whether this is an absolute or a relative path
        // An empty first string means the path starts with '/'
        $this->_isInputAbsolute = empty($arrPath[0]);
        if ($this->_mode === self::MODE_INPUT) $this->_isAbsolute = $this->_isInputAbsolute;

        // Tidy the path directories
        if ($this->_isAbsolute) {
            // We are after some absolute output path
            if ($this->_isInputAbsolute) {
                $this->_arrAbsPath = $this->_tidy($arrPath); // Input is also absolute
            } else {
                $this->_arrAbsPath = $this->_tidy(self::arrayAppend(self::_getAbsOriginArray(), $arrPath));
            }
        } else {
            // We want some relative output path
            if (!$this->_isInputAbsolute) {
                // Input is also relative
                if ($this->_from !== parent::FROM_SYSTEMROOT) {
                    $this->_arrRelPath = $this->_tidy(self::arrayAppend(self::_getRelOriginArray(), $arrPath));
                } else {
                    $this->_arrRelPath = $this->_tidy($arrPath);
                }
                $this->_bRelArrValid = TRUE;
            } else {
                $this->_arrAbsPath = $this->_tidy($arrPath);
                $this->_arrRelPath = $this->_gapArray(xarPath::FROM_SYSTEMROOT);
                $this->_bRelArrValid = TRUE;
            }
        }
        return TRUE;
    }

    /*
     * Tidy and clean the path. *Nix version.
     */
    final protected function _tidy($arrPath)
    {
        $size = count($arrPath);
        if ($size === 0) return array();
        if ($size === 1) {
            if (empty($arrPath[0]) || $arrPath[0] === '.') return array();
            $arrPath[] = ''; // add the trailing slash
            return $arrPath;
        }
        $last = $size-1;

        // 1st pass: cleaning
        for ($i = $last; $i !== 0; $i--) {
            // Detect dir1/'.'/dir2 cases or two slashes dir1/''/dir2 case.
            if ($arrPath[$i] === '.' || empty($arrPath[$i]) && $i !== $last) {
                unset($arrPath[$i]);
                continue;
            }
        }
        $newsize = count($arrPath);
        if ($newsize !== $size) {
            $arrPath = array_values($arrPath);
            $size = $newsize;
            $last = $size-1;
        }

        // 2nd pass: tidy the upper directory
        // Remove parent upper directory dir1/dir2/../dir3/ => dir1/dir3/
        $first = $this->_isAbsolute ? 1 : 0;
        $arrUp = array();
        $arrTot = array();
        for ($i=0; $i !== $size; $i++) {
            if ($i > $first && $arrPath[$i] === '..') {
                $arrUp[] = $i;
                $arrTot[$i] = TRUE;
            } else {
                $arrTot[$i] = FALSE;
            }
        }
        $sizeUp = count($arrUp);
        for ($i=0; $i !== $sizeUp; $i++) {
            $k = $arrUp[$i];
            do {
                --$k;
                if (!$arrTot[$k] && isset($arrPath[$k])) {
                    unset($arrPath[$arrUp[$i]]);
                    unset($arrPath[$k]);
                    unset($arrUp[$i]);
                    break;
                }
            } while ($k !== $first);
        }

        $newsize = count($arrPath);
        if ($newsize !== $size) {
            $arrPath = array_values($arrPath);
            $size = $newsize;
        }

        // 3rd pass: final cleaning
        if ($size === 2 && empty($arrPath[0]) && empty($arrPath[1])) {
            // Due the tidying, first and last slash might collide. /dir2/../ => // => /
            unset($arrPath[1]);
        } else {
            // A path must end by a slash and then an empty element at the end of the array
            if (!empty($arrPath[$size-1])) $arrPath[$size] = '';
        }
        return $arrPath;
    }
}

class xarWinPath extends xarPath
{
    protected $_pathRelPart = 0; // Memorize where the relative part starts in the path array.

    public function isExist()
    {
        return is_dir($this->getAbs(TRUE));
    }

    /**
     * Normalize the path for the given OS (Windows and normalized styles assumed here)
     *
     */
    final protected function _normalize()
    {
        if (strlen($this->_inputPath) > 4 && !preg_match('%^([a-z]:[/\\\\])?+[^:;=*?"<>&@#{}[\]()\'|\r\n]+%i', $this->_inputPath)) throw new Exception('invalid path');

        // We start exploding the path in directories
        // We are using some Windows system:
        // we have no idea whether the user would send
        // normalized, OS specific paths, or a mix of both
        $arrPath = explode('/', strtr($this->_inputPath, '\\', '/'));

        // We determine whether this is an absolute or a relative path

        $size = count($arrPath);
        if (strpos($arrPath[0], ':') !== FALSE) {
            $this->_pathRelPart = 1;
        } elseif ($size > 3 && empty($arrPath[0]) && empty($arrPath[1])) {
            $this->_pathRelPart = 4;
        } else {
            // Might happen with a path starting with / only.
            $this->_pathRelPart = 0;
            if ($arrPath[0] === '') {
                unset($arrPath[0]);
                $arrPath = array_values($arrPath);
            }
        }

        // Using inline code above is faster.
        $this->_isInputAbsolute = $this->_pathRelPart !== 0;
        if ($this->_mode === self::MODE_INPUT) $this->_isAbsolute = $this->_isInputAbsolute;

        // Tidy the path directories
        if ($this->_isAbsolute) {
            // We are after some absolute output path
            if ($this->_isInputAbsolute) {
                $this->_arrAbsPath = $this->_tidy($arrPath); // Input is also absolute
            } else {
                // Relative input, we add the origin root
                $this->_arrAbsPath = $this->_tidy(self::arrayAppend(self::_getAbsOriginArray(), $arrPath));
            }
        } else {
            // We want some relative output path
            if (!$this->_isInputAbsolute) {
                // Input is also relative
                if ($this->_from !== parent::FROM_SYSTEMROOT) {
                    $this->_arrRelPath = $this->_tidy(self::arrayAppend(self::_getRelOriginArray(), $arrPath));
                } else {
                    $this->_arrRelPath = $this->_tidy($arrPath);
                }
                $this->_bRelArrValid = TRUE;
            } else {
                $this->_arrAbsPath = $this->_tidy($arrPath);
                $this->_arrRelPath = $this->_gapArray(xarPath::FROM_SYSTEMROOT);
                $this->_bRelArrValid = TRUE;
            }
        }
        return TRUE;
    }

    /**
     * Tidy and clean the path. Windows version.
     */
    final protected function _tidy($arrPath)
    {
        $size = count($arrPath);
        if ($size === 0) return array('');
        if ($size === 1) {
            if (empty($arrPath[0]) || $arrPath[0] === '.') return array();
            $arrPath[] = ''; // add a trailing slash
            return $arrPath;
        }

        $last = $size - 1;

        // 1st pass: cleaning
        for ($i=$last; $i !== $this->_pathRelPart; $i--) {
            // Detect dir1\'.'\dir2 cases or two slashes dir1\''\dir2 case.
            if ($arrPath[$i] === '.' || empty($arrPath[$i]) && $i !== $last) {
                unset($arrPath[$i]);
                continue;
            }
        }

        $newsize = count($arrPath);
        if ($newsize !== $size) {
            $arrPath = array_values($arrPath);
            $size = $newsize;
        }

        // 2nd pass: tidy the upper directory
        // Remove parent upper directory dir1/dir2/../dir3/ => dir1/dir3/
        $arrUp = array();
        $arrTot = array();
        for ($i=0; $i !== $size; $i++) {
            if ($i > $this->_pathRelPart && $arrPath[$i] === '..') {
                $arrUp[] = $i;
                $arrTot[$i] = TRUE;
            } else {
                $arrTot[$i] = FALSE;
            }
        }

        $sizeUp = count($arrUp);
        for ($i=0; $i !== $sizeUp; $i++) {
            $k = $arrUp[$i];
            do {
                --$k;
                if (!$arrTot[$k] && isset($arrPath[$k])) {
                    unset($arrPath[$arrUp[$i]]);
                    unset($arrPath[$k]);
                    unset($arrUp[$i]);
                    break;
                }
            } while ($k !== $this->_pathRelPart);
        }

        $newsize = count($arrPath);
        if ($newsize !== $size) {
            $arrPath = array_values($arrPath);
            $size = $newsize;
        }

        // 3rd pass: final cleaning
        if ($size === $this->_pathRelPart+2 && empty($arrPath[$this->_pathRelPart]) && empty($arrPath[$this->_pathRelPart+1])) {
            // Due the tidying, first and last slash might collide. /dir2/../ => // => /
            unset($arrPath[$this->_pathRelPart+1]);
        } else {
            // A path must end by a slash and then an empty element at the end of the array
            if (!empty($arrPath[$size-1])) $arrPath[$size] = '';
        }

        return $arrPath;
    }

    /**
     * Quick initialization for instancing with an absolute path array
     * We need to determine whether the path is a drive letter one
     * or a network path.
     */
    final protected function _initForArr()
    {
        $size = count($this->_arrAbsPath);
        if ($size !== 0 && strpos($this->_arrAbsPath[0], ':') !== FALSE) {
            $this->_pathRelPart = 1;
        } elseif ($size > 3 && empty($this->_arrAbsPath[0]) && empty($$this->_arrAbsPath[1])) {
            $this->_pathRelPart = 4;
        } else {
            $this->_pathRelPart = 0;
        }
    }
}

/**
 * A quick attempt to start a generic class to handle files, just like we have xarPath for paths.
 */
class xarFileBone
{
    const FAIL_EXCEPTION = 0;        // Raise exceptions
    const FAIL_EXCEPTION_MSG = 1;    // Raise exceptions with an explicit message
    const FAIL_DIE_POST_MESSAGE = 2; // Post an explicit message and die
    const FAIL_DIE_FORBIDDEN = 3;    // Would make the page to die with a 403 error
    const FAIL_DIE_MISSING = 4;      // Would make the page to die with a 404 error
    const FAIL_DIE_UNAVAILABLE = 5;  // Would make the page to die with a 503 error
    const FAIL_NOACTION = -1;        // Leave with no action, and continue excecution
    // @TODO consider more modes interacting with xarResponse to add as a xarFile class extending xarFileBone

    const CHECK_NONE  = 0;      // No checks
    const CHECK_FILE_EXIST = 1;      // Check whether the file exists
    const CHECK_PATH_EXIST = 2;      // Check whether the path exists

    const FILTER_NONE = 0;
    const FILTER_FILENAME = 1;       // Filter the filename
    const FILTER_CLEAN_ABSOLUTE = 2; // Filter and make the path an absolute path
    const FILTER_CLEAN_RELATIVE = 3; // Filter and make the path a relative path from web root

    protected $_path = '';
    protected $_objPath = NULL;      // xarPath instance, if a FILTER_CLEAN option is used

    protected $_basename = '';
    protected $_extension = '';
    protected $_filename = '';

    protected $_failMode = self::FAIL_EXCEPTION;
    protected $_checks = self::CHECK_NONE;
    protected $_filter = self::FILTER_NONE;
    protected $_bFailed = FALSE;
    protected $_bValid = FALSE;

    protected $_bPathExist = FALSE;
    protected $_bFileExist = FALSE;

    protected $__valid = FALSE;

    /**
     * As php dirname doesn't seem to work fully with UTF-8 finenames'
     * doesn't return any trailing slashes, nor normalize the path
     */
    static public function dirname($filename)
    {
        $lastslash = strrpos($filename, '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $lastback = strrpos($filename, '\\');
            if ($lastslash === FALSE && $lastback !== FALSE) {
                $lastslash = $lastback;
            } else if ($lastslash !== FALSE && $lastback !== FALSE) {
                $lastslash = max($lastslash, $lastback);
            }
        }
        return $lastslash !== FALSE ? substr($filename, 0, $lastslash) : '';
    }

    /**
     * A rewrite of pathinfo() PHP function
     * the PHP function has several issues, just like the lack of support for UTF-8
     * it is normalizing Windows paths
     * http://php.net/manual/fr/function.pathinfo.php
     * @param string $filename
     * @return array()
     */
    static public function pathinfo($filename, $flags = NULL)
    {
        $info = array();
        if ($flags === NULL) $flags = PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME;
        // We normalise paths
        $filename = trim($filename);

        if (DIRECTORY_SEPARATOR === '\\') $filename = str_replace('\\', '/', strtolower($filename));

        $lastslash = strrpos($filename, '/');
        if ($flags & PATHINFO_BASENAME | $flags & PATHINFO_EXTENSION | $flags & PATHINFO_FILENAME) {
            $basename = $lastslash !== FALSE ? substr($filename, $lastslash+1) : $filename;
            if ($flags & PATHINFO_EXTENSION | $flags & PATHINFO_FILENAME) {
                $lastperiod = strrpos($basename, '.');
                if ($flags & PATHINFO_EXTENSION)$info['extension'] = $lastperiod !== FALSE ? substr($basename, $lastperiod + 1) : '';
                if ($flags & PATHINFO_FILENAME) $info['filename'] = $lastperiod !== FALSE ? substr($basename, 0, $lastperiod) : '';
            }
            if ($flags & PATHINFO_BASENAME) $info['basename'] = $basename;
        }
        // Note that the dirname path returned has no trailing slash (just like the PHP function)
        if ($flags & PATHINFO_DIRNAME) $info['dirname']= $lastslash !== FALSE ? substr($filename, 0, $lastslash) : '';
        return $info;
    }

    /**
     * Constructor for xarFileBone
     *
     * @param string $filename: full path filename or just basename if a xarPath object instance is passed
     * @param int $failmode: how the code should handle errors
     * @param int $checks (flags): the checks to perform
     * @param int $filter: select a given filter to use. Only one can be used at a time.
     * @param object $objPath: a xarPath instance
     */
    public function __construct($filename, $failmode = self::FAIL_EXCEPTION, $checks = self::CHECK_FILE_EXIST, $filter = self::FILTER_FILENAME, xarPath $objPath = NULL, $data = NULL)
    {
        $this->_failMode = $failmode; $this->_checks = $checks; $this->_filter = $filter; $this->__valid = $data;
        if ($objPath === NULL) {
            // no xarPath instance is provided.
            $info = self::pathinfo($filename, PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME);
            switch ($filter) {
                case self::FILTER_FILENAME:
                    // We use this filter mode to validate any unsafe filename inputs
                    if (DIRECTORY_SEPARATOR === '/') {
                        // Unix/Linux
                        if (count($filename) > 4096) $this->_fail(); // Linux has a limit fixed at 4096 bytes
                        if (!preg_match('%^(?:[^:;*?"\\\\<>=&@#{}\\[\\]()\'|\r\n]+[/])+(?:[^:;*?"\\\\<>=&@#{}\\[\\]()\'|\r\n]+)*$%i', $filename)) $this->_fail('wrong filename');
                    } else {
                        // Windows
                        if (count($filename) > 32767) $this->_fail(); // NTFS can support 32767 bytes for pathname
                        if (!preg_match('%^([a-z]:[/\\\\])?+(?:[^:;=*?"<>&@#{}\\[\\]()\'|\r\n]+[\\\\/])+(?:[^:;=*?"<>&@#{}\\[\\]()\'|\r\n]+)*$%i', $filename)) $this->_fail('wrong filename');
                    }
                    // no break here!
                case self::FILTER_NONE:
                    $this->_path = $info['dirname'].'/';
                    break;
                case self::FILTER_CLEAN_ABSOLUTE:
                    // This method will use the xarPath object
                    $this->_objPath = xarPath::makeFromCurrent($info['dirname']);
                    $this->_path = $this->_objPath->getAbs();
                    break;
                case self::FILTER_CLEAN_RELATIVE:
                    $this->_objPath = xarPath::makeFromCurrent($info['dirname']);
                    $this->_path = $this->_objPath->getRel();
                    break;
            }
            $this->_basename = $info['basename']; $this->_extension = $info['extension']; $this->_filename = $info['filename'];
        } else if ($objPath instanceof xarPath) {
            // xarPath instance provided
            $this->_objPath = $objPath;
            if (!preg_match('%^(?:[^:;*?"\\\\/<>&\'|\r\n]+)*$%', $filename)) $this->_fail('wrong filename');
            $info = self::pathinfo($filename, PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME);
            $this->_basename = $info['basename']; $this->_extension = $info['extension']; $this->_filename = $info['filename'];
            if ($filter === self::FILTER_CLEAN_RELATIVE) {
                    $this->_path = $this->_objPath->getRel();
                } else {
                    $this->_path = $this->_objPath->getAbs();
                }
            }

        if ($checks & self::CHECK_FILE_EXIST) {
            $this->_bPathExist = $this->_bFileExist = is_file($this->_path . $this->_basename);
            if (!$this->_bFileExist) $this->_fail('file does not exist');
        }

        if (!$this->_bPathExist && $checks & self::CHECK_PATH_EXIST) {
            $this->_bPathExist = is_dir($this->_path);
            if (!$this->_bPathExist) $this->_fail('path does not exist');
        }
        $this->_bValid = !$this->_bFailed;
    }

    public function getPath() { return $this->_objPath; }

    public function isValid()
    {
        return $this->_bValid;
    }

    public function isFileExist()
    {
        if ($this->_checks & self::CHECK_FILE_EXIST) return $this->_bFileExist;
        return $this->_bFileExist = is_file($this->_path . $this->_basename);
    }

    public function isPathExist()
    {
        if ($this->_checks & self::CHECK_PATH_EXIST) return $this->_bPathExist;
        return $this->_bPathExist = is_dir($this->_path);
    }

    public function getPathFilename()
    {
        return $this->_path . $this->_basename;
    }

    public function getExtension()
    {
        return $this->_extension;
    }

    public function getWebUrl()
    {
        return $this->_objPath->gap(xarPath::FROM_WEBROOT) . $this->_basename;
    }

    public function getBasename()
    {
        return $this->_basename;
    }

    protected function _fail($msg = '', $mode = NULL)
    {
        $this->_bFailed = TRUE;
        if ($mode === NULL) $mode = $this->_failMode;
        switch ($mode) {
            case self::FAIL_NOACTION: return;
            case self::FAIL_EXCEPTION: throw new Exception();
            case self::FAIL_EXCEPTION_MSG: throw new Exception($msg);
            case self::FAIL_DIE_POST_MESSAGE: echo $msg; exit();
            case self::FAIL_DIE_FORBIDDEN: xarResponseBone::sendStatusCode(403); exit();
            case self::FAIL_DIE_MISSING: xarResponseBone::sendStatusCode(404); exit;
            case self::FAIL_DIE_UNAVAILABLE: xarResponseBone::sendStatusCode(503); exit();
        }
    }

    public function load()
    {
        $content = '';
        $b = FALSE;
        try {
            $this->_path . $this->_basename;
            $content = file_get_contents($this->_path . $this->_basename, FILE_USE_INCLUDE_PATH);
            $b = TRUE;
        } catch (Exception $e) {
            $b = FALSE;
        }
        if (!$b || $content === FALSE) $this->_fail('file load failed');
        return $content;
    }
}

/**
 * A class to check files against a known signature
 */
final class xarFileSigned extends xarFileBone
{
    private $__signs = array();
    // Those values are important and must not be changed
    private $__arrLvl = array('8', '6', '5', '2');
    private $__arrPos = array('t', 'n', 'u', 'f');
    private $__strSign = 'aea711edc83caf59194cf4bc1b25de2f44291e9bdb83d0d18d6698631427ec4d2f9edf0bdd43221d80463b287d5de761193eab2c6c67657665f634ccb8f2206194532370ad764454c8991f8276139f89c6510bef4a4f7207eff5334d3169fef9d2118544234efae608cbffec13555df8c0b801202713fa634b4ee1f8a19a59a3c58f7ef16b5b965bba0fd08e9800363375c3754702e978e74d89676c3adfc52641ade27b1cbfc53529525979660406eb8de2bdd5cfeba6be218578e05de1d0365884444c8d2f76aa449eb29dbfc75ad8b9e15e501bd9608bdd275d475e3c1a4b';
    private $_g = FALSE;
    public function __construct(xarPath $oPath, $filename)
    {
        $this->_g = sys::isGetting();
        parent::__construct($filename, xarFileBone::FAIL_DIE_MISSING, !$this->_g ? xarFileBone::CHECK_FILE_EXIST | xarFileBone::CHECK_PATH_EXIST : xarFileBone::CHECK_NONE, xarFileBone::FILTER_CLEAN_ABSOLUTE, $oPath, 'unfu_uznp_svyr');

        if (stripos($this->_filename, sys::FILE_OPER) !== FALSE) $this->_failMode = xarFileBone::FAIL_DIE_UNAVAILABLE;
    }
    /**
     * Quick method to check the file
     */
    public function check($mode, $afm, $ext = NULL)
    {
        $_f=$this->_path.$this->_basename; extract($afm);$k=$this->__strSign;$c=count($mId);$s=64;$go=str_rot13($this->__valid);$_g=$this->_g;
        if ($mode===sys::MODE_EXTERNAL&&$go!==NULL) {
            if (($ext===NULL||!is_array($ext)||count($ext)===0)&&!$_g) self::fail();
            $arr=$ext;$f=$go;$path=strtolower(ltrim($this->_objPath->gap(xarPath::FROM_WEBROOT).$this->_basename,'/'));
            $a = strpos($path,'modules/');
            if ($a !== FALSE && $a > 0) $path = substr($path, $a);
            $f=&$go;
        } else {
            if ($c*$s !== strlen($k)) self::fail();
            $ak=str_split($k,$s);
            for($i=0; $i<$c; $i++) if ($mId[$i] === $mode) break;
            $path='/any/path/you/like/myfile.php'; $f=$go; $k =$ak[$i];
        }
        $r = 'ok'; $ver = strrev(str_rot13(implode($go===$path?'path':'',array_merge_recursive(array_shift($this->__arrLvl)!=='5'?$this->__arrLvl:array(),array_shift($this->__arrPos)!=='k'?$this->__arrPos:array()))));
        $comb = str_rot13(strrev($go.$ver).'dir'.strrev($path))!==$r=$go!==NULL?$f($ver,$_f,$path):$_f.$path;
        return (!$ext!== NULL&&$mode===sys::MODE_EXTERNAL&&$go!== NULL)?($comb?$comb&&$_g?$r:array_key_exists($r,$arr):$path):($comb?$comb&&$_g?$r:$k===$r:$path);
    }

    public function fail()
    {
        parent::_fail('');
    }
}

/**
 * A sandbox to include safely a config file using PHP code and get a specific var
 */
final class xarPhpSandbox
{
    private $__content = '';
    private $__file = '';
    private $__isValid = FALSE;
    private $__isIncluded = FALSE;
    private $__supervar = '';
    private $__isincluded = FALSE;


    public function __construct($filename, $varname)
    {
        $this->__file = new xarFileBone($filename, xarFileBone::FAIL_NOACTION, xarFileBone::CHECK_FILE_EXIST, xarFileBone::FILTER_NONE);

        if ($this->__file->isValid()) {
            $this->__content = $this->__file->load();
            $this->__filter();
            $this->__supervar = $varname;
            $this->__isValid = TRUE;
        }
    }

    public function isValid()
    {
        return $this->__isValid;
    }

    public function isIncluded()
    {
        return $this->__isIncluded;
    }

    private function __filter()
    {
        $arrFilters = array('self::',  '__supervar');
        $bFound = FALSE;
        foreach($arrFilters as $filter) {
            $bFound = $bFound || strpos($this->__content, $filter) !== FALSE;
        }
        if ($bFound) $this->__fail();
    }

    /**
     * The sandbox function
     *
     * @return array to extract containing the vars
     */
    public function import()
    {
        $this->__isIncluded = FALSE;
        ob_start();
        try {
            include($this->__file->getPathFileName());
            ob_get_clean();
        }
        catch (exception $e) {
            ob_get_clean();
            $this->__fail();
        }
        if (isset(${$this->__supervar})) {
            $this->__isIncluded = TRUE;
            return array($this->__supervar => ${$this->__supervar});
        }
        return array();
    }

    private function __fail()
    {
        // we use it in case of hacking attempt
        xarResponseBone::sendStatusCode(503);
        exit();
    }
}

/**
 * The sys class contains routines guaranteed to be available to do small
 * things which we do a lot as fast as possible.
 *
 * The routines in this class should be:
 * - very well documented, since they may be unreadable for performance reasons
 * - as superfast as possible.
 * - depend on nothing but itself and assumptions we make for the whole framework
 *
 * @package core
 */
final class sys extends xarObject
{
    // Mode to detect an install or an upgrade
    const MODE_FAILSAFE = -3;
    const MODE_INSTALL = -2;
    const MODE_UPGRADE = -1;
    const MODE_UNDEFINED = 0;
    const MODE_OPERATION = 1;
    const MODE_WEBSERVICE = 2;
    const MODE_AJAX = 3;  // Provision for later
    const MODE_HCMK = 4;
    const MODE_VALIDATION = 5;
    const MODE_EXTERNAL = 10; // external PHP script
    private static $__mId = array(self::MODE_FAILSAFE, self::MODE_INSTALL, self::MODE_UPGRADE, self::MODE_OPERATION, self::MODE_WEBSERVICE, self::MODE_HCMK, self::MODE_VALIDATION);

    const CONFIG = 'config.system.php';     // Default system configuration file
    const CONFIG_EXT = 'config.ext.php';
    const KEY    = '.key.php';
    const FILE_FAIL = 'fail.php';
    const FILE_HCMK = 'hcmk.php';
    const FILE_INST = 'install.php';
    const FILE_UPG = 'upgrade.php';
    const FILE_OPER = 'index.php';
    const FILE_WS = 'ws.php';
    // const FILE_AJAX = 'ajax.php';
    const FILE_VAL = 'val.php';
    const FILE_BOOT = 'bootstrap.php';
    private static $__fId = array(self::FILE_FAIL, self::FILE_INST, self::FILE_UPG, self::FILE_OPER, self::FILE_WS, self::FILE_HCMK, self::FILE_VAL);

    private static $__mode = self::MODE_UNDEFINED;
    private static $__init = FALSE;
    private static $__done = FALSE;
    private static $__bRenameOperFile = TRUE; // Allow to rename index.php operation file

    public static $pathSysRoot = NULL;
    public static $pathWebRoot = NULL;
    public static $pathWeb = NULL;
    public static $pathCode = NULL;
    public static $pathLib = NULL;
    public static $pathSites = NULL;
    public static $pathVar = NULL;
    public static $pathCurrent = NULL;

    private static $__has  = array();         // Keep a list of what we already have
    private static $__var = ''; // Save the var location
    private static $__root = ''; // Save our root location
    private static $__web = ''; // Save our webroot directory
    private static $__sites = ''; // Save location of individual site data
    private static $__lib = ''; // Save location of the lib directory
    private static $__code = ''; // Save location of the code directory
    private static $__codeAbs = ''; // Save absolute location too
    private static $__current = ''; // Save location of the current directory
    private static $__webpath = '';
    private static $__started = FALSE;
    private static $__configFileFound = FALSE;

    private function __construct()
    {} // no objects can be made out of this.

    /**
     * Returns sys state.
     * @return bool TRUE if during init, FALSE otherwise
     */
    final public static function isInit() { return self::$__init; }

    final public static function setStart()
    {
        self::$__started = FALSE;
    }
    /**
     * Returns the current operating mode calling sys::init
     */
    final public static function mode()
    {
        return self::$__mode;
    }

    final public static function fail($mode=self::MODE_UNDEFINED, $msg='')
    {
        $mode = self::$__mode !== self::MODE_UNDEFINED ? self::$__mode : $mode;
        // @TODO use fail safe page for mode operation
        xarResponseBone::sendStatusCode($mode === sys::MODE_OPERATION ? 503 : 404);
        exit();
    }

    /**
     * Determines if the system is stable and not in install or upgrade modes
     */
    final public static function isStable() { return self::$__mode > self::MODE_UNDEFINED; }

    /**
     * Determines if the system is in installation mode
     */
    final public static function isInstall() { return self::$__mode === self::MODE_INSTALL; }

    /**
     * Determines if the system is in upgrade mode
     */
    final public static function isUpgrade() { return self::$__mode === self::MODE_UPGRADE; }

    /**
     * Determine if the call comes from an external (non-core based) source
     */
    final public static function isExternal() { return self::$__mode === self::MODE_EXTERNAL; }

    /**
     * Determine if the call is from the failsafe entry point
     */
    final public static function isFromFail() { return self::$__mode === self::MODE_FAILSAFE; }

    /**
     *  This mode is intended to retrieve some information
     */
    final public static function isGetting() { return self::$__mode === self::MODE_HCMK; }

    final public static function startOnce()
    {
        // We intialize any static vars, arrays used to replace global. Should not be done anywhere else.
        self::$__init = TRUE; // --- START ---
        
        self::$__done = FALSE; // Init has never ever been done yet

        // By default the mode is undefined until we are sure of what's going on'
        self::$__mode = self::MODE_UNDEFINED;

        xarPath::initStatic();
        xarServerBone::initOnce();
        xarRequestBone::initOnce();

        // xar Instance Builder initialization
        xar::init();

        // sys::import mapping
        self::$__has  = array();

        // Initialze sys xarPath instances
        self::$pathSysRoot = NULL;
        self::$pathWebRoot = NULL;
        self::$pathWeb = NULL;
        self::$pathCode = NULL;
        self::$pathLib = NULL;
        self::$pathSites = NULL;
        self::$pathVar = NULL;
        self::$pathCurrent = NULL;
        self::$__configFileFound = FALSE;

        self::$__init = FALSE; // --- END ---

        self::$__started = TRUE;
    }
    /**
     * Initialiation to prepare a$ll the paths and values very fastly in further
     * This assumes that var values were loaded and are passed.
     */
    final public static function init($mode = self::MODE_UNDEFINED)
    {
        // Initialization of static internal vars done once
        if (!self::$__started) self::startOnce();
        
        // Have init already done before?
        if (self::$__done) {
            // We should not call init twice. We only allow to call MODE_FAILSAFE
            if ($mode !== self::MODE_FAILSAFE) sys::fail($mode);
            $oFile = new xarFileSigned(self::$pathWeb, self::FILE_FAIL);
            if (self::$__mode !== self::MODE_EXTERNAL && self::$__mode !== self::MODE_HCMK) {
                if (TRUE !== $oFile->check($mode, self::__getfm(), NULL)) sys::fail($mode);
            }
            return TRUE;
        }

        // We need to initialize include paths to make things working properly
        self::$pathWebRoot = xarServerBone::getWebRootPath();
        self::$pathCurrent = xarServerBone::getCurrentPath();
        self::$pathSysRoot = xarPath::makeSystemRootPath();
        $isWebCurrentPath = xarServerBone::isCurrentWebRoot();
        $isOutWebMode = self::$__mode === self::MODE_EXTERNAL || self::$__mode === self::MODE_HCMK;
        if ($isWebCurrentPath) {
            set_include_path(self::$pathCurrent->getAbs(TRUE) . PATH_SEPARATOR . get_include_path());
        } else {
            set_include_path(self::$pathCurrent->getAbs(TRUE) . PATH_SEPARATOR . self::$pathWebRoot->getAbs(TRUE) . PATH_SEPARATOR . get_include_path());
        }

        // We don't want somebody to call init without being explicit on the intention.
        if ($mode === self::MODE_UNDEFINED) self::fail();
        $oFile = xarRequestBone::getScriptFile();
        if ($mode === self::MODE_OPERATION && !self::$__bRenameOperFile || $mode !== self::MODE_OPERATION) {
            // Checking the mode against the filename
            if (self::__getExpMode($oFile->getBasename()) !== $mode) sys::fail($mode);
        }
        if ($mode !== sys::MODE_EXTERNAL && TRUE !== $oFile->check($mode, self::__getfm(), NULL)) sys::fail($mode);
        self::$__mode = $mode;

        $vardir = NULL;
        // Prepare a var from the web root
        if (!$isOutWebMode && !$isWebCurrentPath) {
            // Normal access but out of web root
            // try first <curentpath>/var
            $vardir = self::$pathCurrent->append('var');
            if (!is_dir($vardir)) $vardir = NULL;
        }
        // Try to find var in <web root>/var
        if ($vardir === NULL) {
            $vardir = self::$pathWebRoot->append('var');
            if (!is_dir($vardir)) $vardir = NULL;
        }
        // If we failed to find a var folder, give up.
        if ($vardir === NULL) self::failsafe('System error', 'Could not locate any var folder in the web root, or in the current directory');

        $path = xarPath::make($vardir, xarPath::MODE_RELATIVE); // we need to get a relative path from anywhere.

        $vardir = $path->getAbs();

        // Eventually get .key.php
        $sbKey = new xarPhpSandbox($vardir.self::KEY, 'protectedVarPath');
        if ($sbKey->isValid()) {
            //try key file
            extract($sbKey->import());
            // $protectedVarPath is absolute or relative to the system root (!)
            // We need to keep it absolute or relative.
            if (!empty($protectedVarPath)) {
                $path2 = xarPath::make($protectedVarPath, xarPath::MODE_INPUT);
                if ($path2->isExist()) {
                    self::$pathVar = $path2;
                    $vardir = $path2->getAbs();
                }
            }
        }

        if (!is_object(self::$pathVar)) {
            // We still have no var path. Fall back to webroot/var
            self::$pathVar = $path;
        }
        // From this point we have an existing var path. (self::$path / $vardir)

        if (self::$__mode === self::MODE_EXTERNAL) {
            $extConfiguration = array();
            $sbExt = new xarPhpSandbox($vardir.self::CONFIG_EXT, 'extConfiguration');
            if ($sbExt->isValid()) {
                try {
                    extract($sbExt->import());
                } catch (Exception $e) { } // Do nothing
            }
            if (!isset($extConfiguration) || !is_array($extConfiguration) || TRUE !== $oFile->check($mode, self::__getfm(), $extConfiguration)) {
                unset($extConfiguration);
                sys::failsafe('External system error', 'You are attempting to run an external without the proper configuration');
            }
            unset($extConfiguration);
        }

        // Now let's get the system configuration file
        $systemConfiguration = array();
        // Prepare a sandbox for config.system.php
        $sbSystem = new xarPhpSandbox($vardir.self::CONFIG, 'systemConfiguration');
        self::$__configFileFound = FALSE;
        if ($sbSystem->IsValid()) {
            try {
                extract($sbSystem->import());
            } catch (Exception $e) { } // Do nothing
            self::$__configFileFound = TRUE;
        }

        // Instanciate other xarPaths

        // The former sys::root() in sys requires no trailing slash. @todo: make it more uniform and consistent
        self::$__root = rtrim(self::$pathSysRoot->getAbs(), '/');
        // @todo: is there any good reason to distinguish the TRUE web root, from the webDir?

        // webDir
        if (array_key_exists('webDir', $systemConfiguration)) {
            self::$pathWeb = xarPath::make($systemConfiguration['webDir']);
         } else {
            if (!$isWebCurrentPath && !$isOutWebMode) {
                $root = self::$pathCurrent->getAbs();
                echo "bad";
            } else {
                $root = self::$pathWebRoot->getAbs();
            }
            // That's from where we run install
            self::$pathWeb = xarPath::make($root);
        }
        $root = self::$pathWeb->getAbs(); // in case something is changed from config

        // libDir
        if (array_key_exists('libDir', $systemConfiguration)) {
            $libDir = $systemConfiguration['libDir'];
            $libDir = $libDir === '' ? 'lib/' : $libDir.'/lib';
        } else {
            $libDir = $root.'lib/';
        }
        self::$pathLib = xarPath::make($libDir);

        // codeDir
        self::$pathCode = xarPath::make(array_key_exists('codeDir', $systemConfiguration) ? $systemConfiguration['codeDir'] : $root);

        // siteDir
        self::$pathSites = xarPath::make(array_key_exists('siteDir', $systemConfiguration) ? $systemConfiguration['siteDir'] : $root.'/../sites/');

        // Let's cache all the paths once for all, and save if (isset(self::$dirWeb)) test

        self::$__web = self::$pathWeb->getAbs();
        self::$__lib = self::$pathLib->getAbs();
        self::$__code = self::$pathCode->gap(xarPath::FROM_CURRENTDIR); // Required to make css code working -
        self::$__codeAbs = self::$pathCode->getAbs(); // Better for sys::import
        self::$__sites = self::$pathSites->getAbs();

        // The former sys::varpath() requires no trailing slash. @todo: make it more uniform and consistent
        self::$__var = rtrim($vardir, '/');
        
        // Init is complete
        self::$__done = TRUE;

        // We ensure that configuration elements allow to run the core
        self::checkIntegrity();

        return TRUE;
    }
    /**
     * In order to run the core, we may want to insure that files are where they are expected, or to fail safely 
     * giving some clues of what is going on.
     */
    final public static function checkIntegrity()
    {
        $installnotes = '<br /><br/>Try to <a href="install.php">run install now</a>. <br /><br />Alternatively refer to the 
<a href="http://xarigami.org/resources/installing_xarigami">Xarigami installation</a> 
documentation or <a href="http://xarigami.org/forums">Xarigami forums</a> for assistance.';
        // Installation can start without any config.system.php, but we don't want the core to start without it.
        if (self::$__mode !== self::MODE_INSTALL && !self::$__configFileFound) {
            sys::failsafe('System configuration error', 'Your configuration file appears to be missing. <br />
It may mean that your site has not been installed correctly.'.$installnotes);
        }
        // Check lib path
        if (!is_file(self::$__lib . 'xarigami/xarCache.php') || !is_file(self::$__lib . 'xarigami/xarCache.php')) {
            self::failsafe('Library files missing or misplaced', 'Sorry, but the configuration elements provided prevented to locate your library files. Please check your path configuration.');
        }
        // Check code path
        if (!is_file(self::$__codeAbs . 'modules/base/xaruser/main.php')) {
            self::failsafe('Code files missing or misplaced', 'Sorry, but the configuration elements provided prevented to locate your code files. Please check your path configuration.');
        }
        // @TODO: Do we want more checks?
        return TRUE;
    }

    /**
     * Return a split between the webDir base path and the dir relative to it
     * input is relative/absolute from web root.
     * output is relative from webdir (default ) or currentdir
     */
     const FROM_WEBDIR = 0;
     const FROM_CURRENTDIR = 1;
     final public static function getBaseDirs($dir, $from = self::FROM_WEBDIR)
     {
        $basepath = '';
        $basedir = '';

        switch ($from) {
            default:
            case self::FROM_WEBDIR:
                $pathref = sys::$pathWeb;
                break;
            case self::FROM_CURRENTDIR:
                $pathref = sys::$pathCurrent;
                break;
        }

        if (is_object($pathref)) {
            $path = xarPath::makeFromWeb($dir); // This would not work if we have a webDir for subfolders
            $gap = $path->gapFromPath($pathref);
            if (!empty($gap) && substr($gap, 0, 2) !== '..') {
                $basedir = $gap;
                $basepath = $pathref->getAbs();
            } else {
                $basepath = $path->getAbs();
            }

        }

        return array('basepath'=> $basepath, 'basedir' => $basedir);
     }

    /**
     * Import a xarigami component once, in the fastest way possible
     *
     * Little utility function which allows easy inclusion of xarigami core
     * components in the fastest (and safe) way
     * The dot path is mapped to the file to include as follows:
     *
     * sys::import(a.b.c.d);  ~~ include_once(a/b/c/d.php); (only faster)
     *
     * WHY : this implementation is nearly constant time, no matter how many
     *       times you include a component. I've benched it against:
     *       - plain include_once inline,
     *       - include_once inside a function
     *       - function with a static + include (procedural equivalent of this class)
     *       If you include something say not more than 2 to 3 times there is not
     *       much difference; if doing more than that, include_once is slower.
     *       This class and the procedural equivalent are nearly equal performing.
     *       PHP5 only obviously (tested against: 5.1.4-0.1 linux, 5.0.4 OSX)
     *
     * NOTE: only use this for class/function inclusion, they get included into
     *       the global scope. Any variables inside the include file will get
     *       the local scope of the line containing the include (which is here)
     *
     * NOTE: the line which does the actual inclusion could be faster by using
     *       include instead of include_once, but i couldnt measure much difference
     *       in practice. This is safer, because if there are still include_once's in
     *       the execution path, this class wont pick up that they have been
     *       loaded, and will issue a 'cannot redeclare' warning.
     *
     * @return mixed if file is actually included the return value determined by the included file, otherwise TRUE
     * @param  string $dp 'dot path' a dot separated string describing which component to include
     * @param  bool $root whether to prepend the relative root directory
     * @ext    string $ext 'file extension' by default .php
     *
     * Syntax examples:
     *    sys::import('blocklayout.compiler')              -> lib/blocklayout/compiler.php
     *    sys::import('modules.mymodule.xarincludes.test') -> html/modules/mymodule/xarincludes/test.php
     *
     * The beginning of the dot path is scanned for 'modules.'
     * if found it assumes a module import
     * is meant, otherwise a core component import is assumed.
     *
     */
    final public static function import($dp, $ext='.php')
    {
       // If we already have it get out of here asap
        if (!array_key_exists($dp, self::$__has)) {
            // set this *before* the include below
            self::$__has[$dp] = TRUE;
            // tiny bit faster would be to use include, but this is quite a bit safer
            if ((0===strpos($dp,'modules.')))
                return include_once(self::$__codeAbs . str_replace('.', '/', $dp) . $ext);

            return include_once(self::$__lib . str_replace('.', '/', $dp) . $ext);
        }
        return TRUE;
    }

    /**
     * This function is used to signal sys::import that a given file has been loaded by other means
     * used by xarMod for function overrides
     */
    final public static function hasImported($dp)
    {
        self::$__has[$dp] = TRUE;
    }
    
    final private static function __importFail()
    {
        $file = self::$__web . self::FILE_FAIL;
        if (!class_exists('xarFailure')) include_once($file);
    }
    
    /**
     * Call fail.php to render an failsafe error page
     * @param title string
     * @param message string
     * @param code http status code (503,404,etc.)
     */
    final public static function failsafe($title = NULL, $message = NULL, $code = NULL)
    {
        self::__importFail();

        if ($title !== NULL) xarFailure::$title = $title;
        if ($message !== NULL) xarFailure::$message = $message;
        if ($code !== NULL) xarFailure::$code = $code;
        xarFailure::render();
        exit();
    }
    /**
     * Returns the absolute path of the xarigami system root, NOT the web root
     * Note that there will be NO slash at the end of the returned path.
     *
     * @return string
     */
    final public static function root()
    {
        return self::$__root;
    }
    /**
     * Returns the absolute path of the xarigami system library directory
     * Note that there will be a slash at the end of the returned path.
     *
     * @return string
     */
    final public static function lib()
    {
        // We are in <libDir>/lib/xarigami/xarPreCore.php and we want <libDir>
        return self::$__lib;
    }
    /**
     * Returns the absolute path of the xarigami system web document directory
     * Note that there will be a slash at the end of the returned path.
     *
     * @return string
     */
    final public static function web()
    {
        return self::$__web;
    }

    /**
     * Returns the path relative from web root where modules and themes are located
     * Note that there will be a slash at the end of the returned path. There is no
     * point at the beginning
     *
     * @return string
     */
    final public static function code()
    {
        return self::$__code;
    }

    /**
     * Returns the absolute path where modules and themes are located
     * Note that there will be a slash at the end of the returned path.
     * @return string
     */
    final public static function codeAbs()
    {
        return self::$__codeAbs;
    }

    /**
     * Returns the absolute path where modules and themes are located
     * Note that there will be a slash at the end of the returned path.
     *
     * @return string
     */
    final public static function sites()
    {
        return self::$__sites;
    }

    /**
     * Returns the path name for the var directory
     *
     * The var directory may be placed outside the webroot. In this case
     * the var directory path should be placed in a file ./var/.key.php like:
     *
     * $protectedVarPath='/path/to/where/you/need/the/var/dir';
     *
     * obviously the .key.php file must be a valid php file.
     *
     * @return string the var directory path name
     * @todo the .key.php construct seems odd
     */
    final public static function varpath()
    {
        return self::$__var;
    }

    final static private function __getExpMode($f)
    {
        $c = count(self::$__fId);
        for($i=0; $i < $c; $i++) $arr[self::$__fId[$i]] = self::$__mId[$i];
        $f = strtolower($f);
        if (!array_key_exists($f, $arr)) return sys::MODE_EXTERNAL;
        return $arr[$f];
    }

    final private static function __getfm() { return array('mId' => self::$__mId, 'fId' => self::$__fId); }

    final static public function getfm()
    {
        if (self::isGetting()) return self::__getfm();
        return NULL;
    }

}

/**
 * The xarDataContainer class from which all classes containing data are derived
 *
 * This class has the minimum methods for subclasses that
 * are not "system" classes and need to interact with other Xarigami classes.
 *
 * [random]
 *     The specific use case is:
 *     collections with the idea that in the future sometime a standard "get"
 *     function like we have them in modules now will return an object
 *     and a getrall will return a collection
 *
 * @package core
 */
class xarDataContainer extends xarObject
{
    /**
     *  @todo protected members cannot be gotten?
     *  @todo <mrb> i dont think this is a feasible direction
     */
    public function get($name)
    {
        $p = $this->getProperty($name);
        if($p->isPublic())
            return $p->$name;
    }

    /**
     *  @todo protected members cannot be set?
     *  @todo <mrb> i dont think this is a feasible direction
     */
    public function set($name, $value)
    {
        $p = $this->getProperty($name);
        if($p->isPublic())
            $this->$name = $value;
    }
}

// Bug xgami-000845: we have issues with Apache/XCache/PHP5.3 if the code is placed at the top of the file.

// We don't want anyone to go further than that calling the bootstrap.
// Do not rename the bootstrap file.
sys::setStart();
sys::startOnce();
if (xarRequestBone::getBaseName() === sys::FILE_BOOT) {
    xarResponseBone::sendStatusCode(404);
    exit();
}

?>
