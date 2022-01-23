<?php
/**
 * Legacy Functions
 *
 * @package legacy
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Legacy
 * @copyright (C) 2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**********************************************************************
* Please mark all stuff that you need in this file or file a bug report
*
* Necessary functions to duplicate
* MODULE SYSTEM FUNCTIONS
*
* SESSION FUNCTIONS
*
* CONFIG FUNCTIONS
*
* SECURITY FUNCTIONS
* xarSecAddSchema()
*
* SERVER FUNCTIONS (URL URI)
*
* USER FUNCTIONS
* xarUserGetLang()

* BLOCKS FUNCTIONS
* xarBlockTypeExists()
* xarUser_getThemeName()

* DATABASE FUNCTIONS
*
* VAR FUNCTIONS
*
* MISC FUNCTIONS
*
* DEPRECATED XAR FUNCTIONS
* xarModEmailURL        -> no direct equivalent
* xarVarPrepForStore()  -> use bind vars or dbconn->qstr() method
* xarExceptionSet()     -> xarErrorSet()
* xarExceptionMajor()   -> xarCurrentErrorType()
* xarExceptionId()      -> xarCurrentErrorID()
* xarExceptionValue()   -> xarCurrentError()
* xarExceptionFree()    -> xarErrorFree()
* xarExceptionHandled() -> xarErrorHandled()
* xarExceptionRender()  -> xarErrorRender()
* xarPage_sessionLess() -> xarPageCache_sessionLess()
* xarPage_httpCacheHeaders() -> xarPageCache_sendHeaders()
* xarTplAddJavascriptCode() -> xarTplAddJavascript()
* xarTplAddStyleLink()  -> use style tag
* xarCore_IsCached()
* xarCore_SetCached()
* xarCore_GetCached()
* xarCore_DelCached()
* xarCore_FlushCached()
*/
require_once("structures/descriptor.php"); 

// Prefix Add
function xarGetStatusMsg()
{
    $msg = xarSessionGetVar('statusmsg');
    xarSession::delVar('statusmsg');
    $errmsg = xarSession::getVar('errormsg');
    xarSession::delVar('errormsg');

    // Error message overrides status message
    if (!empty($errmsg)) {
        return $errmsg;
    }
    return $msg;
}

define("ACCESS_NONE","ACCESS_NONE");
define("ACCESS_OVERVIEW","ACCESS_OVERVIEW");
define("ACCESS_READ","ACCESS_READ");
define("ACCESS_COMMENT","ACCESS_COMMENT");
define("ACCESS_MODERATE","ACCESS_MODERATE");
define("ACCESS_EDIT","ACCESS_EDIT");
define("ACCESS_ADD","ACCESS_ADD");
define("ACCESS_DELETE","ACCESS_DELETE");
define("ACCESS_ADMIN","ACCESS_ADMIN");


function xarBlockTypeExists($modName, $blockType)
{
    if (!xarMod::apiLoad('blocks', 'admin')) return;
    $args = array('modName'=>$modName, 'blockType'=>$blockType);
    return xarMod::apiFunc('blocks', 'admin', 'block_type_exists', $args);
}
/**
 * get the user's language
 *
 * @return string the name of the user's language
 * @throws DATABASE_ERROR
 */
function xarUserGetLang()
{
    // FIXME: <marco> DEPRECATED?
    $locale = xarUserGetNavigationLocale();
    $data =& xarMLSLoadLocaleData($locale);
    if (!isset($data)) return; // throw back
    return $data['/language/iso3code'];
}

/**
 * Get the user's theme directory path
 *
 * @return string the user's theme directory path if successful, void otherwise
 */
function xarUser_getThemeName()
{
    if (!xarUserIsLoggedIn()) {
        return;
    }
    $themeName = xarUserGetVar('Theme');

    return $themeName;
}

/*
 * Register an instance schema with the security
 * system
 *
 * @access public
 * @param string component the component to add
 * @param string schema the security schema to add
 *
 * Will fail if an attempt is made to overwrite an existing schema
 */
function xarSecAddSchema($component, $schema)
{
    $msg = xarML('This call needs to be removed');
     throw new EmptyParameterException($msg);
}

/**
 * Generates an URL that reference to a module function via Email.
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function
 * @param funcName string module function
 * @param args array of arguments to put on the URL
 * @return mixed absolute URL for call, or false on failure
 */
function xarModEmailURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array())
{
//TODO: <garrett> either deprecate this function or keep it in synch with xarModURL *or* add another param
//      to xarModURL to handle this functionality. See bug #372
// Let's depreciate it for 1.0.0  next release I will remove it.
    if (empty($modName)) {
        return xarServer::getBaseURL() . 'index.php';
    }

    // The arguments
    $urlArgs[] = "module=$modName";
    if ((!empty($modType)) && ($modType != 'user')) {
        $urlArgs[] = "type=$modType";
    }
    if ((!empty($funcName)) && ($funcName != 'main')) {
        $urlArgs[] = "func=$funcName";
    }
    $urlArgs = join('&', $urlArgs);

    $url = "index.php?$urlArgs";

    foreach ($args as $k=>$v) {
        if (is_array($v)) {
            foreach($v as $l=>$w) {
                if (isset($w)) {
                    $url .= "&$k" . "[$l]=$w";
                }
            }
        } elseif (isset($v)) {
            $url .= "&$k=$v";
        }
    }

    // The URL
    return xarServer::getBaseURL() . $url;
}

/**
* Ready database output
 *
 * Gets a variable, cleaning it up such that the text is
 * stored in a database exactly as expected. Can have as many parameters as desired.
 *
 * @deprec 2004-02-18
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @todo are we allowing arrays and objects for real?
 */
function xarVarPrepForStore()
{
    // Issue a WARNING as this function is deprecated
    xarLogMessage('Using deprecated function xarVarPrepForStore, use bind variables instead',XARLOG_LEVEL_WARNING);
    $resarray = array();
    foreach (func_get_args() as $var) {

        // Add to array
        array_push($resarray, $var);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

function xarExceptionSet($major, $errorID, $value = NULL)
{
    xarErrorSet($major, $errorID, $value);
}



/**
* Gets the identifier of current error
 *
 * Returns the error identifier corresponding to the current error.
 * If invoked when no error was raised, a void value is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the error identifier
 * @deprec 2004-04-01
 */
function xarExceptionId()
{
    return xarCurrentErrorID();
}

/**
* Gets the current error object
 *
 * Returns the value corresponding to the current error.
 * If invoked when no error or an error for which there is no associated information was raised, a void value is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return mixed error value object
 * @deprec 2004-04-01
 */
function xarExceptionValue()
{
    return xarCurrentError();
}    // deprecated

/**
* Resets current error status
 *
 * xarErrorFree is a shortcut for xarErrorSet(XAR_NO_EXCEPTION, NULL, NULL).
 * You must always call this function when you handle a caught error or
 * equivalently you don't throw the error back to the caller.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return void
 * @deprec 2004-04-01
 */
function xarExceptionFree()
{
    xarErrorFree();
}

/**
* Handles the current error
 *
 * You must always call this function when you handle a caught error.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return void
 * @deprec 2004-04-01
 */
function xarExceptionHandled()
{
    xarErrorHandled();
}

/**
* Renders the current error
 *
 * Returns a string formatted according to the $format parameter that provides all the information
 * available on current error.
 * If there is no error currently raised an empty string is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param format string one of template or plain
 * @param stacktype string one of CORE or ERROR
 * @return string the string representing the raised error
 * @deprec 2004-04-01
 */
function xarExceptionRender($format)
{
    return xarErrorRender($format);
}    // deprecated

/**
 * Session-less page caching
 *
 * @author mikespub, jsb
 * @access private
 * @return void
 * @deprec 2005-02-01
 */
function xarPage_sessionLess()
{
    xarPageCache_sessionLess();
}

/**
 * Send HTTP headers for page caching (or return 304 Not Modified)
 *
 * @author mikespub, jsb
 * @access private
 * @return void
 * @deprec 2005-02-01
 */
function xarPage_httpCacheHeaders($cache_file)
{
    if (!file_exists($cache_file)) { return; }
    $modtime = filemtime($cache_file);

    xarPageCache_sendHeaders($modtime);
}

/**
 * Add JavaScript code to template output **deprecated**
 *
 * @access public
 * @param  string $position Either 'head' or 'body'
 * @param  string $owner    Who produced this snippet?
 * @param  string $code     The JavaScript Code itself
 * @deprec 2004-03-20       This is now handled by a custom tag of the base module
 * @return bool
 */
function xarTplAddJavaScriptCode($position, $owner, $code)
{
    assert($position == "head" || $position == "body");
    return xarTplAddJavaScript($position, 'code', "<!-- JavaScript code from {$owner} -->\n" . $code);
}

/**
 * Add stylesheet link for a module (after rc3 this function is a legacy)
 *
 * @access public (deprecated - all CSS issues are normally handled by the css classlib via bl tags)
 * @param  string $module
 * @param  string $file
 * @param  string $fileext
 * @param  string $themefolder ('' or path no leading or trailing /, )
 * @param  string $media (multiple values supported as a comma separated list "screen, print")
 * @todo   can deprecate after adoption of template css tags
 * @return bool
 */
function xarTplAddStyleLink($module = null, $file = null, $fileext = null, $themefolder = null, $media = null, $scope = 'module')
{
    $method = 'link';
    $args = compact('module', 'file', 'fileext', 'themefolder', 'media', 'scope', 'method');

    // make sure we can use css object
    require_once "modules/themes/xarclass/xarcss.php";
    $obj = new xarCss($args);
    return $obj->run_output();
}
/**
 * Wrapper functions to support Xaraya 1 API xarCore cached functions
 */
function xarCore_IsCached($cacheKey, $name)
{   return xarCoreCache::isCached($cacheKey, $name); }

function xarCore_GetCached($cacheKey, $name)
{   return xarCoreCache::getCached($cacheKey, $name); }

function xarCore_SetCached($cacheKey, $name, $value)
{   return xarCoreCache::setCached($cacheKey, $name, $value); }

function xarCore_DelCached($cacheKey, $name)
{ return xarCoreCache::isCached($cacheKey, $name);}

function xarCore_FlushCached($cacheKey)
{ return xarCoreCache::flushCached($cacheKey);}

/**
 * Check whether a certain API type is allowed
 *
 * Check whether an API type is allowed to load
 * normally the api types are 'user' and 'admin' but modules
 * may define other API types which do not fall in either of
 * those categories. (for example: visual or soap)
 * The list of API types is read from the Core configuration variable
 * Core.AllowedAPITypes.
 *
 * @author Marcel van der Boom marcel@hsdev.com
 * @access protected
 * @param  string apiType type of API to check whether allowed to load
 * @todo   See if we can get rid of this, nobody is using this
 * @return bool
 */
function xarCoreIsApiAllowed($apiType)
{
    return TRUE; // Is this function that important? Comment out if a problem.

    // Testing for an empty API type just returns FALSE
    if (empty($apiType)) return FALSE;
    if (preg_match ("/api$/i", $apiType)) return FALSE;

    // Dependency
    $allowed = xarConfigGetVar('System.Core.AllowedAPITypes');

    // If no API type restrictions are given, return TRUE
    if (empty($allowed) || count($allowed) == 0) return TRUE;
    return in_array($apiType,$allowed);
}

/**
 * Deprecated Object, DataContainer, ObjectDescriptor classes replaced by xarXXXXXX in core 1.5
 */
 
#class_alias('xarObject', 'Object');
class_alias('xarObjectDescriptor', 'ObjectDescriptor');
class_alias('xarDataContainer', 'DataContainer');

?>
