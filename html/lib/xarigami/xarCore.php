<?php
/**
 * The Core
 *
 * @package Xarigami core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2013 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Core version informations
 *
 * should be upgraded on each release for
 * better control on config settings
 *
 */
//start mtn revision inclusion
$rev = 'unknown';
try { // "revision" file should be generated based on source code system by dist-build tools
    if (file_exists('revision')) {
        $t= file('revision');
        if (isset($t[0]))
            $rev = trim($t[0]);
    }
} catch (Exception $e) {}
define('XARCORE_GENERATION',1);
define('XARCORE_VERSION_NUM', '1.5.5');
define('XARCORE_VERSION_ID',  'Xarigami');
define('XARCORE_VERSION_SUB', 'Cumulus');
define('XAR_BL_VERSION_NUM', '1.0.0');
define('XARCORE_VERSION_REV', $rev);


/*
 * System dependencies for (optional) systems
 * FIXME: This diagram isn't correct (or at least not detailed enough)
 * -------------------------------------------------------
 * | Name           | Depends on                | Define |
 * ------------------------------------------------------
 * | EXCEPTIONS     | nothing (really ?)        |        |
 * | LOG            | nothing                   |        |
 * | SYSTEMVARS     | nothing                   |        |
 * | DATABASE       | SYSTEMVARS                |    1   |
 * | EVENTS         | nothing ?                 |        |
 * | CONFIGURATION  | DATABASE                  |    8   |
 * | LEGACY         | CONFIGURATION             |        |
 * | SERVER         | CONFIGURATION (?)         |        |
 * | MLS            | CONFIGURATION             |        |
 * | SESSION        | CONFIGURATION (?), SERVER |    2   |
 * | BLOCKS         | CONFIGURATION             |   16   |
 * | MODULES        | CONFIGURATION             |   32   |
 * | TEMPLATE       | MODULES, MLS (?)          |   64   |
 * | USER           | SESSION, MODULES          |    4   |
 * -------------------------------------------------------
 *
*   DATABASE           (00000001)
 *   |
 *   |- CONFIGURATION   (00001001)
 *      |
 *      |- SESSION      (00001011)
 *      |
 *      |- BLOCKS       (00011001)
 *      |
 *      |- MODULES      (00101001)
 *         |
 *         |- USER      (00101111)
 *
 *   ALL                (01111111)
 */

/*
 * Optional systems defines that can be used as parameter for xarCoreInit
 * System dependancies are yet present in the define, so you don't
 * have to care of what for example the SESSION system depends on, if you
 * need it you just pass XARCORE_SYSTEM_SESSION to xarCoreInit and its
 * dependancies will be automatically resolved
 *
 * @access public
 * @todo   bring these under a class as constant
**/
/**#@+
 * Bit defines to keep track of the loading based on the defines which
 * are passed in as arguments
 *
 * @access private
 * @todo we should probably get rid of these
**/
define('XARCORE_BIT_DATABASE',         1);
define('XARCORE_BIT_CONFIGURATION',    2);
define('XARCORE_BIT_MODULES',          4);
define('XARCORE_BIT_TEMPLATES',        8);
define('XARCORE_BIT_SESSION',          16);
define('XARCORE_BIT_USER',             32);
define('XARCORE_BIT_BLOCKS',           64);
define('XARCORE_BIT_HOOKS',            128);
define('XARCORE_BIT_ALL',              255);

define('XARCORE_SYSTEM_NONE',            0);
define('XARCORE_SYSTEM_DATABASE',        XARCORE_BIT_DATABASE);
define('XARCORE_SYSTEM_CONFIGURATION',   XARCORE_BIT_CONFIGURATION | XARCORE_SYSTEM_DATABASE);
define('XARCORE_SYSTEM_MODULES',         XARCORE_BIT_MODULES | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_TEMPLATES',       XARCORE_BIT_TEMPLATES | XARCORE_SYSTEM_MODULES);
define('XARCORE_SYSTEM_SESSION',         XARCORE_BIT_SESSION | XARCORE_SYSTEM_TEMPLATES);
define('XARCORE_SYSTEM_USER',            XARCORE_BIT_USER | XARCORE_SYSTEM_SESSION);
define('XARCORE_SYSTEM_BLOCKS',          XARCORE_BIT_BLOCKS | XARCORE_SYSTEM_USER);
define('XARCORE_SYSTEM_HOOKS',           XARCORE_BIT_HOOKS | XARCORE_SYSTEM_USER);
define('XARCORE_SYSTEM_ALL',             XARCORE_BIT_ALL);
/**#@-*/

/**#@+
 * Debug flags
 * @access private
 * @todo   encapsulate in class
**/
define('XARDBG_ACTIVE'           , 1);
define('XARDBG_SQL'              , 2);
define('XARDBG_EXCEPTIONS'       , 4);
define('XARDBG_SHOW_PARAMS_IN_BT', 8);
define('XARDBG_INACTIVE'         ,16);
/**#@-*/

/*
 * Miscelaneous
 */
define('XARCORE_CONFIG_FILE', 'config.system.php');
define('XARCORE_CACHEDIR'     , '/cache');
define('XARCORE_DB_CACHEDIR'  , '/cache/database');
define('XARCORE_RSS_CACHEDIR' , '/cache/rss');
//define('XARCORE_ADODB_CACHEDIR' , '/cache/adodb');
define('XARCORE_STYLES_CACHEDIR' , '/cache/styles');
define('XARCORE_TPL_CACHEDIR' , '/cache/templates');

if (!defined('XAR_TPL_CACHE_DIR')) {
    define('XAR_TPL_CACHE_DIR',sys::varpath() . '/cache/templates');
}

// Before we do anything make sure we can except out of code in a predictable matter
sys::import('xarigami.xarException');
// Load core caching in case we didn't go through xarCache::init()
sys::import('xarigami.caching.core');
/**
 * Convenience class for keeping track of core stuff
 *
**/
class xarCore extends xarCoreCache
{
    const GENERATION = 1;
    // The actual version information
    const VERSION_ID  = XARCORE_VERSION_ID;
    const VERSION_NUM = XARCORE_VERSION_NUM;
    const VERSION_SUB = XARCORE_VERSION_SUB;
    const VERSION_REV = XARCORE_VERSION_REV;

    const BIT_DATABASE       = XARCORE_BIT_DATABASE;
    const BIT_CONFIGURATION  = XARCORE_BIT_CONFIGURATION;
    const BIT_MODULES        = XARCORE_BIT_MODULES;
    const BIT_TEMPLATES      = XARCORE_BIT_TEMPLATES;
    const BIT_SESSION        = XARCORE_BIT_SESSION;
    const BIT_USER           = XARCORE_BIT_USER;
    const BIT_BLOCKS         = XARCORE_BIT_BLOCKS;
    const BIT_HOOKS          = XARCORE_BIT_HOOKS;
    const BIT_ALL            = XARCORE_BIT_ALL;

    const SYSTEM_NONE          = XARCORE_SYSTEM_NONE;
    const SYSTEM_DATABASE      = XARCORE_SYSTEM_DATABASE;
    const SYSTEM_CONFIGURATION = XARCORE_SYSTEM_CONFIGURATION;
    const SYSTEM_MODULES       = XARCORE_SYSTEM_MODULES;
    const SYSTEM_TEMPLATES     = XARCORE_SYSTEM_TEMPLATES;
    const SYSTEM_SESSION       = XARCORE_SYSTEM_SESSION;
    const SYSTEM_USER          = XARCORE_SYSTEM_USER;
    const SYSTEM_BLOCKS        = XARCORE_SYSTEM_BLOCKS;
    const SYSTEM_HOOKS         = XARCORE_SYSTEM_HOOKS;
    const SYSTEM_ALL           = XARCORE_SYSTEM_ALL;

    protected static $_currentSystemLevel = self::SYSTEM_NONE;
    protected static $_newSystemLevel     = self::SYSTEM_NONE;
    protected static $_firstLoad = TRUE;

    protected static $_modName = '';
    protected static $_modType = '';
    protected static $_modFunc = '';
    protected static $_mainModuleOuput = '';

    public static function init($whatToLoad = self::SYSTEM_ALL)
    {
        self::$_newSystemLevel = $whatToLoad;

        // Make sure it only loads the current load level (or less than the current load level) once.
        if ($whatToLoad <= self::$_currentSystemLevel) {
            if (!self::$_firstLoad) return TRUE; // Does this ever happen? If so, we might consider an assert
            self::$_firstLoad = FALSE;
        } else {
            // if we are loading a load level higher than the
            // current one, make sure to XOR out everything
            // that we've already loaded
            $whatToLoad ^= self::$_currentSystemLevel;
        }

        sys::import('xarigami.xarPHPCompat');
        xarPHPCompat::loadAll(sys::lib().'xarigami/phpcompat');
         /**
         * At this point we should be able to catch all low level errors, so we can start the debugger
         * Set the types of debug you want to see by adding flags to the activation
         *
         * FLAGS:
         *
         * XARDBG_INACTIVE          disable  the debugger
         * XARDBG_ACTIVE            enable   the debugger
         * XARDBG_EXCEPTIONS        debug exceptions
         * XARDBG_SQL               debug SQL statements
         * XARDBG_SHOW_PARAMS_IN_BT show parameters in the backtrace
         *
         * Flags can be OR-ed together
         */
        self::activateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);

        //load up the System Variabls
        sys::import('xarigami.variables.system');
        // Safe start - we raise an error here if config.system is not found
        xarSystemVars::init(sys::CONFIG);

        /*
         * We want to be able to log anything that happens
         */
        $systemArgs = array('loggerName' => xarSystemVars::get(sys::CONFIG, 'Log.LoggerName', TRUE),
                            'loggerArgs' => xarSystemVars::get(sys::CONFIG, 'Log.LoggerArgs', TRUE),
                            'logLevel'   => xarSystemVars::get(sys::CONFIG, 'Log.LogLevel',   TRUE),
                            'logFile'    => xarSystemVars::get(sys::CONFIG, 'Log.LogFile',    TRUE)
                           );
        sys::import('xarigami.xarLog');
        xarLog_init($systemArgs);

        //ensure we have the date default timezone set
        try {
            $tz =  xarSystemVars::get(sys::CONFIG, 'SystemTimeZone',TRUE);
            date_default_timezone_set($tz?$tz:date_default_timezone_get());
        } catch (Exception $e) {
            sys::failsafe('Error setting timezone', '<p>The timezone might be missing in your configuration. <br />
            Please review your configuration file config.system.php or php.ini. Alternatively refer to the
             <a href="http://xarigami.com/resources/installing_xarigami">Xarigami installation</a>
             documentation or <a href="http://xarigami.com/forums">Xarigami forums</a> for assistance.</p>', 503);
        }

        /*
         * Start Database Connection Handling System
         *
         * Most of the stuff, except for logging, exception and system related things,
         * we want to do in the database, so initialize that as early as possible.
         * It think this is the earliest we can do
         *
         */
        if ($whatToLoad & self::BIT_DATABASE) { // yeah right, as if this is optional
            sys::import('xarigami.xarDB');

            // Decode encoded DB parameters
            $userName = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
            $password = xarSystemVars::get(sys::CONFIG, 'DB.Password');
            $persistent = NULL;
            try {
                $persistent =  xarSystemVars::get(sys::CONFIG, 'DB.Persistent');
            } catch(VariableNotFoundException $e) {
                $persistent = NULL;
            }
            try {
                if (xarSystemVars::get(sys::CONFIG, 'DB.Encoded') == '1') {
                    $userName = base64_decode($userName);
                    $password  = base64_decode($password);
                }
            } catch(VariableNotFoundException $e) {
                // doesnt matter, we assume not encoded
            }
            $systemArgs = array('userName'          => $userName,
                                'password'          => $password,
                                'databaseHost'      => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                                'databaseType'      => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                                'databaseName'      => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                                'databaseCharset'   => xarSystemVars::get(sys::CONFIG, 'DB.Charset'),
                                'persistent'        => $persistent,
                                'systemTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                                'siteTablePrefix'   => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix')
                                );
            // Connect to database
            xarDB::init($systemArgs, $whatToLoad);
            $whatToLoad ^= self::BIT_DATABASE;
        }

        /*
         * Start Event Messaging System
         *
         * The event messaging system can be initialized only after the db, but should
         * be as early as possible in place. This system is for *core* events
         *
         */
            sys::import('xarigami.xarEvt');
            $systemArgs = array();
           // xarEvents::init($systemArgs);

        /*
         * Start Configuration System
         *
         * Ok, we can  except, we can log our actions, we can access the db and we can
         * send events out of the core. It's time we start the configuration system, so we
         * can start configuring the framework
         *
         */
         if ($whatToLoad & self::BIT_CONFIGURATION) {
            // Start Variables utilities
            sys::import('xarigami.xarVar');
            sys::import('xarigami.variables.config');
            xarVars::init($systemArgs, $whatToLoad);
            $whatToLoad ^= self::BIT_CONFIGURATION;

        // we're about done here - everything else requires configuration, at least to initialize them !?
        } else {
            // Make the current load level == the new load level
            self::$_currentSystemLevel = self::$_newSystemLevel;
            return TRUE;
        }

        /**
         * Legacy systems
         *
         * Before anything fancy is loaded, let's start the legacy systems
         *
         */
        if (xarConfigVars::get(NULL,'Site.Core.LoadLegacy') == TRUE) {
            sys::import('xarigami.xarLegacy');
        }

        /*
         * At this point we haven't made any assumptions about architecture
         * except that we use a database as storage container.
         *
         */

        /*
         * Bring HTTP Protocol Server/Request/Response utilities into the story
         *
         */
        sys::import('xarigami.xarServer');
        $systemArgs = array('enableShortURLsSupport' => xarConfigVars::get(NULL, 'Site.Core.EnableShortURLsSupport'),
                            'defaultModuleName'      => xarConfigVars::get(NULL, 'Site.Core.DefaultModuleName'),
                            'defaultModuleType'      => xarConfigVars::get(NULL, 'Site.Core.DefaultModuleType'),
                            'defaultModuleFunction'  => xarConfigVars::get(NULL, 'Site.Core.DefaultModuleFunction'),
                            'generateXMLURLs' => TRUE);
        xarSerReqRes_init($systemArgs, $whatToLoad);

        /*
         * Bring Multi Language System online
         */
        sys::import('xarigami.xarMLS');
        $systemArgs = array('MLSMode'             => xarConfigVars::get(NULL, 'Site.MLS.MLSMode'),
                            'translationsBackend' => 'xml2php',
                            'defaultLocale'       => xarConfigVars::get(NULL, 'Site.MLS.DefaultLocale'),
                            'allowedLocales'      => xarConfigVars::get(NULL, 'Site.MLS.AllowedLocales'),
                            'defaultTimeZone'     => xarConfigVars::get(NULL, 'Site.Core.TimeZone'),
                            'systemTimeZone'      => xarSystemVars::get(sys::CONFIG,'SystemTimeZone'),
                            'defaultTimeOffset'   => xarConfigVars::get(NULL, 'Site.MLS.DefaultTimeOffset'),
                            'MLSEnabled'          => xarConfigVars::get(NULL, 'Site.MLS.Enabled', true)
                            );

        xarMLS_init($systemArgs, $whatToLoad);

        /*
         * We deal with users through the sessions subsystem
         *
         */
        $anonuid = xarConfigVars::get(NULL,'Site.User.AnonymousUID');
        // fall back to default uid 2 during installation (cfr. bootstrap function)
        $anonuid = !empty($anonuid) ? $anonuid : 2;
        define('_XAR_ID_UNREGISTERED', $anonuid);

        if ($whatToLoad & self::BIT_SESSION) {
            sys::import('xarigami.xarSession');

            $systemArgs = array('securityLevel'     => xarConfigVars::get(NULL, 'Site.Session.SecurityLevel'),
                                'duration'          => xarConfigVars::get(NULL, 'Site.Session.Duration'),
                                'inactivityTimeout' => xarConfigVars::get(NULL, 'Site.Session.InactivityTimeout'),
                                'cookieName'        => xarConfigVars::get(NULL, 'Site.Session.CookieName'),
                                'cookiePath'        => xarConfigVars::get(NULL, 'Site.Session.CookiePath'),
                                'cookieDomain'      => xarConfigVars::get(NULL, 'Site.Session.CookieDomain'),
                                'refererCheck'      => xarConfigVars::get(NULL, 'Site.Session.RefererCheck'));

            xarSession_init($systemArgs);

            $whatToLoad ^= self::BIT_SESSION;
        } else {
                // Make the current load level == the new load level
                self::$_currentSystemLevel = self::$_newSystemLevel;
                return TRUE;
        }

        /**
         * Block subsystem
         *
         */
        if ($whatToLoad & self::BIT_BLOCKS) {
            sys::import('xarigami.xarBlocks');
            // Start Blocks Support Sytem
            $systemArgs = array();
            xarBlock::init($systemArgs);
           $whatToLoad ^= self::BIT_BLOCKS;
        } /*else {
            // Make the current load level == the new load level
            $_currentSystemLevel = self::$_newSystemLevel;
            return TRUE;
        }*/

        /**
         * Start Modules Subsystem
         *
         * @todo <mrb> why is this optional?
         * @todo <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request or XHTML site compliance. For now just pass TRUE.
        **/
         if ($whatToLoad & self::BIT_MODULES) {
            sys::import('xarigami.xarMod');
            $systemArgs = array('enableShortURLsSupport' =>xarConfigVars::get(NULL, 'Site.Core.EnableShortURLsSupport'),
                                'generateXMLURLs' => TRUE);
            xarMod::init($systemArgs, $whatToLoad);
            $whatToLoad ^= self::BIT_MODULES;

        } else {
            // Make the current load level == the new load level
            self::$_currentSystemLevel = self::$_newSystemLevel;
            return TRUE;
        }

        /**
         * We've got basically all we want, start the interface
         * Start BlockLayout Template Engine
         */
        if ($whatToLoad & self::BIT_TEMPLATES) {
            sys::import('xarigami.xarTemplate');
            sys::import('xarigami.variables.theme');
            xarThemeVars::init($systemArgs, $whatToLoad);

            $defaultTheme = xarModVars::get('themes','default');
            $defaultThemeDir =  xarMod::getDirFromName($defaultTheme,'theme');
            $systemArgs = array(
                'enableTemplatesCaching' => xarConfigVars::get(NULL, 'Site.BL.CacheTemplates'),
                'themesBaseDirectory'    => xarConfigVars::get(NULL, 'Site.BL.ThemesDirectory'),
                'defaultThemeDir'        => $defaultThemeDir,
                'generateXMLURLs'        => TRUE
            );
            sys::import('xarigami.xarTheme');
            xarTpl::init($systemArgs, $whatToLoad);
               $whatToLoad ^= self::BIT_TEMPLATES;
            // we're about done here - everything else requires templates !?
        } /* else {
            // Make the current load level == the new load level
            $_currentSystemLevel = self::$_newSystemLevel;
            return TRUE;
        } */

        /**
         * At last, we can give people access to the system.
         *
         * @todo <marcinmilan> review what pasts of the old user system need to be retained
         */
        if ($whatToLoad & self::BIT_USER)
        {
            sys::import('xarigami.xarUser');
            sys::import('xarigami.xarSecurity');
            xarSecurity_init();
            // Start User System
            $systemArgs = array('authenticationModules' => xarConfigVars::get(NULL, 'Site.User.AuthenticationModules'));
            xarUser_init($systemArgs, $whatToLoad);
            $whatToLoad ^= self::BIT_USER;
        } else {
                // Make the current load level == the new load level
                self::$_currentSystemLevel = self::$_newSystemLevel;
                return TRUE;
        }

        self::$_currentSystemLevel = self::$_newSystemLevel;

        return TRUE;
    }

    /**
     * Core main method (was originally in index.php)
     *
     * @access public
     * @return bool
     */
     public static function main()
     {
        // Load the core with all optional systems loaded
        self::init(XARCORE_SYSTEM_ALL);

        // Get module parameters
        list($modName, $modType, $funcName) = xarRequest::getInfo();

        // Default Page Title
        $SiteSlogan = xarModGetVar('themes', 'SiteSlogan');
        xarTpl::setPageTitle(xarVarPrepForDisplay($SiteSlogan));

        // Set page template
        if ($modType == 'admin') {
            xarTpl::setAdminTheme($modName);
        }

        // Theme Override
        xarVarFetch('theme','str:1:',$themeName,'',XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
        if (!empty($themeName)) {
            $themeName = xarVarPrepForOS($themeName);
            if (xarThemeIsAvailable($themeName)){
                xarTpl::setThemeName($themeName);

                xarCoreCache::setCached('Themes.name','CurrentTheme', $themeName);
            }
        }

        // Get cache key for page
        $cacheKey = xarCache::getPageKey();

        $run = 1;
        if (!empty($cacheKey) && xarPageCache::isCached($cacheKey)) {
            // Output the cached page *or* a 304 Not Modified status
            if (xarPageCache::getCached($cacheKey)) {
                // we could return true here, but we'll continue just in case
                // processing changes below someday...
                $run = 0;
            }
        }

        if ($run) {

            // Load the module
            if (!xarMod::load($modName, $modType)) return; // throw back

            // if the debugger is active, start it
            if (self::isDebuggerActive()) {
                ob_start();
            }

            // Call the main module function
            $mainModuleOutput = xarMod::guiFunc($modName, $modType, $funcName);

            if (self::isDebuggerActive()) {
                if (ob_get_length() > 0) {
                    $rawOutput = ob_get_contents();
                    $mainModuleOutput = 'The following lines were printed in raw mode by module, however this
                                         should not happen. The module is probably directly calling functions
                                         like echo, print, or printf. Please modify the module to exclude direct output.
                                         The module is violating Xarigami architecture principles.<br /><br />'.
                                         $rawOutput.
                                         '<br /><br />This is the real module output:<br /><br />'.
                                         $mainModuleOutput;
                }
                ob_end_clean();
            }

            // We're all done, one ServerRequest made
            xarEvents::trigger('ServerRequest');

            // Note : the page template may be set to something else in the module function
            if (xarTpl::getPageTemplateName() == 'default' && $modType != 'admin') {
                // NOTE: we should fallback to the way we were handling this before
                // (ie: use pages/$modName.xt if pages/user-$modName is not found)
                // instead of just switching to the new way without a deprecation period
                // so as to prevent breaking anyone's sites. <rabbitt>
                if (!xarTpl::setPageTemplateName('user-'.$modName)) {
                    xarTpl::setPageTemplateName($modName);
                }
            }

            xarVarFetch('pageName','str:1:', $pageName, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
            if (!empty($pageName)){
                xarTpl::setPageTemplateName($pageName);
            }

            // Render page
            $pageOutput = xarTpl::renderPage($mainModuleOutput);

            // Where is the timer snippet?
            $pos = strrpos($pageOutput, '<!--xarPageTimer-->'); // 19 characters long

            // Set the output of the page in cache
            if (!empty($cacheKey)) {
                // Remove the timer snippet from cache output.
                if ($pos !== FALSE) $pageOutput = substr_replace($pageOutput,'', $pos, 19);

                // save the output in cache *before* sending it to the client
                xarPageCache::setCached($cacheKey, $pageOutput);
            } else if ($pos !== FALSE) {
                // Replace the timer snippet by the suitable value
                $strTimer = xarML('Page rendered in #(1) sec', substr(xarDebug::getTime(), 0, 6));
                $pageOutput = substr_replace($pageOutput,$strTimer, $pos, 19);
            }
            echo $pageOutput;
        }
        return TRUE;
    }

    /**
     * Activates the debugger.
     *
     * @access public
     * @param integer $flags bit mask for the debugger flags
     * @todo  a big part of this should be in the exception (error handling) subsystem.
     * @return void
     */
    public static function activateDebugger($flags)
    {
        xarDebug::$flags = $flags;
        xarDebug::startTime(); // We start it normally in the entry point page (eg index.php)
        if ($flags & XARDBG_INACTIVE) {
            // Turn off error reporting
            error_reporting(0);
            // Turn off assertion evaluation
            assert_options(ASSERT_ACTIVE, 0);
        } elseif ($flags & XARDBG_ACTIVE) {
            // See if config.system.php has info for us on the errorlevel, but dont break if it has not
            try {
                sys::import('xarigami.variables.system');
                $errLevel = xarSystemVars::get(sys::CONFIG,'Exception.ErrorLevel',TRUE);
            } catch (Exception $e) {
                $errLevel = E_ALL;
            }
            error_reporting($errLevel);
            // Activate assertions
            assert_options(ASSERT_ACTIVE,    1);    // Activate when debugging
            assert_options(ASSERT_WARNING,   1);    // Issue a php warning
            assert_options(ASSERT_BAIL,      0);    // Stop processing?
            assert_options(ASSERT_QUIET_EVAL,0);    // Quiet evaluation of assert condition?
            xarDebug::$sqlCalls = 0;
        }
    }

    /**
     * Check if the debugger is active
     *
     * @access public
     * @return bool TRUE if the debugger is active, FALSE otherwise
     */
    public static function isDebuggerActive()
    {
        return xarDebug::$flags & XARDBG_ACTIVE;
    }

    /**
     * Check for specified debugger flag.
     *
     * @access public
     * @param integer flag the debugger flag to check for activity
     * @return bool TRUE if the flag is active, FALSE otherwise
     */
    public static function isDebugFlagSet($flag)
    {
        return (xarDebug::$flags & XARDBG_ACTIVE) && (xarDebug::$flags & $flag);
    }
}

/**
 * Checks if a certain function was disabled in php.ini
 *
 *
 * @access public
 * @param string $funcName The function name; case-sensitive
 * @todo this seems out of place here.
**/
function xarFuncIsDisabled($funcName)
{
    static $disabled;

    if (!isset($disabled))
    {
        // Fetch the disabled functions as an array.
        // White space is trimmed here too.
        $functions = preg_split('/[\s,]+/', trim(ini_get('disable_functions')));

        if ($functions[0] != '')
        {
            // Make the function names the keys.
            // Values will be 0, 1, 2 etc.
            $disabled = array_flip($functions);
        } else {
            $disabled = array();
        }
    }

    return (isset($disabled[$funcName]) ? TRUE : FALSE);
}

/**@+ Wrappers for older API functions */
function xarCoreInit($whatToLoad = xarCore::SYSTEM_ALL)
{
    return xarCore::init($whatToLoad);
}
function xarCoreActivateDebugger($flags)
{
    xarCore::activateDebugger($flags);
}
function xarCore_getSystemVar($name, $returnNull = FALSE)
{
    return xarSystemVars::get(sys::CONFIG,$name,$returnNull);
}
function xarCore_setSystemVar($name, $value)
{
    return xarSystemVars::set(sys::CONFIG,$name,$value);
}
function xarCoreIsDebuggerActive()
{
    return xarCore::isDebuggerActive();
}
function xarCoreIsDebugFlagSet($flag)
{
    return xarCore::isDebugFlagSet($flag);
}
/**@-*/

?>
