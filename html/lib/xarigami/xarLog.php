<?php
/**
 * Logging Facilities
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * Logging System
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Logging package defines
 * jojo - move to bootstrap so we can use them in config.system.php
 */

//define('XARLOG_LEVEL_EMERGENCY', 1);
//define('XARLOG_LEVEL_ALERT',     2);
//define('XARLOG_LEVEL_CRITICAL',  4);
//define('XARLOG_LEVEL_ERROR',     8);
//define('XARLOG_LEVEL_WARNING',   16);
//define('XARLOG_LEVEL_NOTICE',    32);
//define('XARLOG_LEVEL_INFO',      64);
//define('XARLOG_LEVEL_DEBUG',     128);
//special log level for audit
//define('XARLOG_LEVEL_AUDIT',     65);
// This is a special define that includes all the levels defined above
//define('XARLOG_LEVEL_ALL',       255);

/**
 * Exceptions raised within the loggers
 *
 */
class LoggerException extends Exception
{
    // Fill in later.
}

/**
 * Initialize the logging subsystem
 *
 * @return void
 * @throws LoggerException
**/
function xarLog_init($args)
{
    static $logFile;

    $GLOBALS['xarLog_loggers'] = array();
    $xarLogConfig = array();

    $loggerType = isset($args['loggerName'])? $args['loggerName']:'simple';
    $loggerArgs = isset($args['loggerArgs'])? $args['loggerArgs']:array();
    $logLevel =  isset($args['logLevel'])? $args['logLevel']:XARLOG_LEVEL_ALL;
    $logFile =  isset($args['logFile'])? $args['logFile']:'';

    if (xarLogConfigReadable())
    {
        // CHECKME: do we need to wrap this?
        if (!include (xarLogConfigFile())) {
            throw new LoggerException('xarLog_init: Log configuration file is invalid!');
        }

    } elseif (xarLogFallbackPossible()) {

        //Fallback mechanism to allow some logging in important cases when
        //the user might now have logging yet installed, or for some reason we
        //should be able to have a way to get error messages back => installation?!
        if (empty($logFile)) {
            $logFile = xarLogFallbackFile();
        }
        if ($logFile) {
           $filename = basename($logFile);
           $logdir =   xarPath::makeFromWeb(dirname($logFile));
           $logFile = $logdir->getAbs().$filename;

            $xarLogConfig[] = array(
                        'type'      =>  $loggerType,
                        'config'    => array(
                            'fileName' => $logFile,
                            'logLevel'  => $logLevel));
        }
    }

    // If none of these => do nothing.
     foreach ($xarLogConfig as $logger) {
        xarLog__add_logger($logger['type'], $logger['config']);
     }

    // Subsystem initialized, register a shutdown function
    register_shutdown_function('xarLog__shutdown_handler');
    xarDebug::$logLevel = $logLevel;
    return true;
}

/**
 * Will return the log configuration file directory and name
 */
function xarLogConfigFile()
{
    static $logConfigFile;

    if (isset($logConfigFile)) return $logConfigFile;

    $logConfigFile = sys::varpath() . '/logs/config.log.php';

    if (file_exists($logConfigFile)) {
        $logConfigFile = realpath($logConfigFile);
    }

    return $logConfigFile;
}

/**
 * Will return true if the log config file exists and is readable, and false if not
 */
function xarLogConfigReadable()
{
    $logConfigFile = xarLogConfigFile();

    if (file_exists($logConfigFile) && is_readable($logConfigFile)) {
        return true;
    }

    return false;
}

/**
 * Will return the log file directory and name
 */
function xarLogFallbackFile()
{
    static $logFile;

    if (isset($logFile)) return $logFile;

    $logFile = sys::varpath() . '/logs/log.txt';

    if (file_exists($logFile)) {
        $logFile = realpath($logFile);
    }

    return $logFile;
}

/**
 * Will check if the fallback mechanism can be used
 * @return bool
 */
function xarLogFallbackPossible()
{
    $logFile = xarLogFallbackFile ();
    if (file_exists($logFile) && is_writeable($logFile)) {
        return true;
    }

    return false;
}

/**
 * Shutdown handler for the logging system
 *
 *
 */
function xarLog__shutdown_handler()
{
     xarLogMessage("xarLog shutdown handler.");

     // If the debugger was active, we can dispose it now.
     if(xarDebug::$flags & XARDBG_SQL) {
         xarLogMessage("Total SQL queries: $GLOBALS[xarDebug_sqlCalls].");
     }

     if (xarDebug::$flags & XARDBG_ACTIVE) {
         $totalTime = xarDebug::getTime();
         xarLogMessage("Response was served in $totalTime seconds.");
     }

//During register_shutdown, it's already too late.
//fwrite presents problems during it.
//you can't use it with javascript/mozilla loggers...
//Maybe there should be a xaraya shutdown event?
/*
     xarLogMessage("xarLog shutdown handler: Ending all logging.");

    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
       $GLOBALS['xarLog_loggers'][$id]->;
    }
 */
}

/**
 * Add a logger to active loggers
 *
 * @return void
 * @throws LoggerException
**/
function xarLog__add_logger($type, $config_args)
{
    sys::import('xarigami.log.loggers.'.$type);
    $type = 'xarLogger_'.$type;

    if (!$observer = new $type()) {
        throw new LoggerException('xarLog_init: Unable to instantiate class for logging: '.$type);
    }

    $observer->setConfig($config_args);

    $GLOBALS['xarLog_loggers'][] = $observer;
}

function xarLogMessage($message, $level = XARLOG_LEVEL_DEBUG, $unwantedmask=array())
{
    //if (($level == XARLOG_LEVEL_DEBUG) && !xarCore::isDebuggerActive()) return;
    if (($level == XARLOG_LEVEL_DEBUG) && !(xarDebug::$flags & XARDBG_ACTIVE)) return;

    // this makes a copy of the object, so the original $this->_buffer was never updated
    //foreach ($_xarLoggers as $logger) {

    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
        $type = str_replace('xarLogger_', '', get_class($GLOBALS['xarLog_loggers'][$id]));

        if (!in_array($type, $unwantedmask)) $GLOBALS['xarLog_loggers'][$id]->notify($message, $level);
    }
}

function xarLogVariable($name, $var, $level = XARLOG_LEVEL_DEBUG)
{
    $args = array('name'=>$name, 'var'=>$var, 'format'=>'text');

    //Encapsulate core libraries in classes and let __call work lazy loading
    sys::import('xarigami.log.functions.dumpvariable');
    xarLogMessage(xarLog__dumpVariable($args),$level);
}
 /**
     * Returns the defined integer representation of a string from the configuration.
     *
     * @param string $string   One of the priority level strings.
     *
     * @return string           The string representation of $level.
     */
    function stringToLevel($string)
    {
        static $strings = array (
            'EMERGENCY' => XARLOG_LEVEL_EMERGENCY,
            'ALERT'     => XARLOG_LEVEL_ALERT,
            'CRITICAL'  => XARLOG_LEVEL_CRITICAL,
            'ERROR'     => XARLOG_LEVEL_ERROR,
            'WARNING'   => XARLOG_LEVEL_WARNING,
            'NOTICE'    => XARLOG_LEVEL_NOTICE,
            'INFO'      => XARLOG_LEVEL_INFO,
            'AUDIT'     => XARLOG_LEVEL_AUDIT,
            'DEBUG'     => XARLOG_LEVEL_DEBUG
        );
        if (!isset($strings[$string])) $string = 'DEBUG';
        return $strings[$string];
    }
?>
