<?php
/**
 * Xarigami HTTP Protocol Server/Request/Response utilities
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @subpackage server
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Initializes the HTTP Protocol Server/Request/Response utilities
 *
 * @access protected
 * @global bool xarRequest_allowShortURLs
 * @global array xarRequest_defaultModule
 * @global array xarRequest_shortURLVariables
 * @param bool args['generateShortURLs']
 * @param string args['defaultModuleName']
 * @param string args['defaultModuleName']
 * @param string args['defaultModuleName']
 * @param integer whatElseIsGoingLoaded
 * @return bool TRUE
 */
function xarSerReqRes_init($args, $whatElseIsGoingLoaded)
{
    // Use $args to configure the classes
    xarServer::init($args);
    xarRequest::init($args);
    // TODO: we will migrate the whatelseblah out later on, to keep the init interface clean, we trick it a bit for now.
    $args['whatelseisgoingloaded'] = $whatElseIsGoingLoaded;
    xarResponse::init($args);

    // Register the ServerRequest event
    xarEvents::register('ServerRequest');

    return TRUE;
}

/**
 * Wrapper functions to support Xaraya 1 API Server functions
 *
 */
function xarServerGetVar($name) { return xarServerBone::getVar($name);}
function xarServerGetBaseURI()  { return xarServer::getBaseURI();  }
function xarServerGetHost()     { return xarServer::getHost();     }
function xarServerGetProtocol() { return xarServer::getProtocol(); }
function xarServerGetBaseURL()  { return xarServer::getBaseURL();  }
function xarServerGetCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL)
    { return xarServer::getCurrentURL($args, $generateXMLURL, $target);}
function xarServer__array2query($args=array(), $prefix = '')
    { return xarServer::__array2query($args, $prefix); }
/**
 * Wrapper function to support Xaraya 1 API Request functions
 *
 */
function xarRequestGetVar($name, $allowOnlyMethod = NULL) { return xarRequest::getVar($name, $allowOnlyMethod); }
function xarRequestGetClientURI()                         { return xarRequest::getClientUri();}
function xarRequestGetServerURI($pathinfo = TRUE, $withquery = FALSE) { return xarRequest::getServerUri($pathinfo, $withquery) ;}
function xarRequestVar($name, $allowOnlyMethod = NULL)    { return xarRequest::getVar($name, $allowOnlyMethod); }
function xarRequestGetInfo()                              { return xarRequest::getInfo(); }
function xarRequestIsLocalReferer()                       { return xarRequest::isLocalReferer(); }
function xarRequestIsAJAX()                               { return xarRequest::isAjax(); }
function xarServer__resolveModuleAlias($var)              { return xarRequest::resolveModuleAlias($var); }
/**
 * Wrapper functions to support Xaraya 1 API Response functions
 *
 */
function xarResponseRedirect($redirectURL, $httpResponse=NULL) 
    { return xarResponse::Redirect($redirectURL, $httpResponse); }
function xarResponseNotFound($msg = '', $modName = 'base', $modType = 'message', $funcName = 'notfound', $templateName = NULL)
    { return xarResponse::NotFound($msg, $modName, $modType, $funcName, $templateName); }
function xarResponseForbidden($msg = '', $modName = 'base', $modType = 'message', $funcName = 'forbiddenoperation', $templateName = NULL)
    { return xarResponse::Forbidden($msg, $modName, $modType, $funcName, $templateName); }
function xarResponseServiceUnavailable($msg = '', $modName = 'base', $modType = 'message', $funcName = 'serviceunavailable', $templateName = NULL)
    { return xarResponse::ServiceUnavailable($msg, $modName, $modType, $funcName, $templateName); }

/**
 * Convenience classes
 *
 */
class xarServer extends xarServerBone
{
    static private $__allowShortUrls = TRUE;
    static private $__generateXmlUrls = TRUE;
    
    // static vars for quick caching
    static protected $_baseUri = NULL;
    static protected $_currentUri = NULL;
    static protected $_baseUrl = NULL;
    static protected $_host = NULL;
    static protected $_protocol = NULL;
    
    /**
     * Initialize
     *
     */
    static public function init($args)
    {
        self::$__allowShortUrls = $args['enableShortURLsSupport'];
        self::$__generateXmlUrls = $args['generateXMLURLs'];
        self::$_baseUri = NULL;
        self::$_currentUri = NULL;
        self::$_baseUrl = NULL;
        self::$_host = NULL;
        self::$_protocol = NULL;
        xarEvents::register('ServerRequest');
    }

    /**
     * Get base URI for Xaraya
     *
     * @access public
     * @return string base URI for Xaraya
     * @todo remove whatever may come after the PHP script - TO BE CHECKED !
     * @todo See code comments.
     */
    static public function getBaseURI()
    {
        if (self::$_baseUri === NULL) {
            // Allows overriding the Base URI from config.system.php
            // it can be used to configure Xaraya for mod_rewrite by
            // setting BaseURI = '' in config.system.php
            if (!sys::isInstall()) {
                $baseUri =  xarSystemVars::get(NULL,'BaseURI',TRUE);
                if(!xarMLSVirtualPathsIsEnabled() && isset($baseUri)) {
                    // If BaseURI set, just use it
                    $path = $baseUri;
                } else {
                    // Attempt to get REQUEST_URI (which is client browser's one)
                    $path = xarRequest::getClientUri();
                }
    
                // We should already have caught the URI for Apache or IIS Rewrite, and possibly some other web servers
                // To do: support more paths such fr.domain.com or domain.fr
    
                // Handles MLS Virtual Sites / Paths
                if (xarMLSVirtualPathsIsEnabled()) {
                    // Virtual Paths are enabled
                    $virtualpath = xarMLSExtractVirtualPath($path, TRUE, TRUE);
                    if (isset($virtualpath))
                        return $virtualpath;
                    // Failed to find a virtual path, then continue...
                }
    
            } else {
                $path = xarRequest::getClientUri();
            }
    
            // In case there is no virtual path and that the Base URI is set from config.php, return it.
            if (isset($baseUri)) return $baseUri;
    
            // Try to get the $path from web server vars
            if (empty($path)) {
                $path = xarRequest::getServerUri(FALSE, FALSE);
            }
    
            // Get rid of the end of the URI, filename, target.
            $path = preg_replace('/[#\?].*/', '', $path);
            $path = preg_replace('/\.php\/.*$/', '', $path);
    
            // The path ends with a slash?
            if (substr($path, -1, 1) == '/') {
                // To find the base directory, we need something like /path/dummy
                $path .= 'dummy';
            }
            $path = dirname($path);
    
            // Just in case dirname return backslashes
            // dirbase('/test') on PHP5 Windows returns '\'
            $path = str_replace('\\', '/', $path);
    
            // Handles URI like /a//b///c
            $path = preg_replace('%/{2,}%', '/', $path);
    
            // Paths are returned without ending slash.
            // The root must be the same.
            if ($path == '/') {
                $path = '';
            }
            self::$_baseUri = $path;
        }
        return self::$_baseUri;
    }

    /**
     * Gets the host name
     *
     * Returns the server host name fetched from HTTP headers when possible.
     * The host name is in the canonical form (host + : + port) when the port is different than 80.
     *
     * @access public
     * @return string HTTP host name
     */
    static public function getHost()
    {
        if (self::$_host === NULL) {
            self::$_host = parent::getVar('HTTP_HOST');
            if (empty(self::$_host)) {
                // HTTP_HOST is reliable only for HTTP 1.1
                self::$_host = parent::getVar('SERVER_NAME');
                $port = parent::getVar('SERVER_PORT');
                if ($port != '80') self::$_host .= ":$port";
            }
        }
        return self::$_host;
    }

    /**
     * Gets the current protocol
     *
     * Returns the HTTP protocol used by current connection, it could be 'http' or 'https'.
     *
     * @access public
     * @return string current HTTP protocol
     */
    static public function getProtocol()
    {
        if (self::$_protocol === NULL && function_exists('xarConfigGetVar')) {
            if (xarConfigGetVar('Site.Core.EnableSecureServer') == TRUE) {
                $port = xarConfigGetVar('Site.Core.SecureServerPort');
                if (!empty($port) && parent::getVar('SERVER_PORT') == $port) return self::$_protocol = 'https';
                
                $https = parent::getVar('HTTPS');
                if (!empty($https) && $https != 'off') return self::$_protocol = 'https';
                
                $iisSecure = parent::getVar('SERVER_PORT_SECURE');
                if (!empty($iisSecure) && $iisSecure == '1') return self::$_protocol = 'https';
            }
            self::$_protocol = 'http';
        }
        return self::$_protocol !== NULL ? self::$_protocol : 'http';
    }

    /**
     * get base URL for Xaraya
     *
     * @access public
     * @return string base URL for Xaraya
     */
    static public function getBaseURL()
    {
        if (self::$_baseUrl === NULL) {
            $host     = self::$_host       !== NULL ? self::$_host       : self::getHost();
            $protocol = self::$_protocol   !== NULL ? self::$_protocol   : self::getProtocol();
            $baseuri  = self::$_baseUri    !== NULL ? self::$_baseUri    : self::getBaseURI();
            self::$_baseUrl = "$protocol://$host$baseuri/";
        }
        return self::$_baseUrl;
    }

    /**
     * get the elapsed time since this page started
     *
     * @access public
     * @return seconds and microseconds elapsed since the page started
     */
    static public function getPageTime()
    {
        return microtime(TRUE) - $GLOBALS["Xaraya_PageTime"];
    }

    /**
     * Create a query string from an array.
     * @todo For PHP5, this can be handled by http_build_query()
     */
    private static function __array2query($args, $prefix = '')
    {
        $query = '';
        if ($prefix == '') {
            // First time around the loop, i.e. the top level, handling
            // the main parameter names.
            foreach ($args as $k=>$v) {
                if (is_array($v)) {
                    // Recursively walk the array tree to as many levels as necessary
                    // e.g. ...&foo[bar][dee][doo]=value&...
                    $query .= xarServer::__array2query($v, (!empty($query) ? '&' : '') . $k);
                } elseif (isset($v)) {
                    // TODO: rather than rawurlencode, use a xar function to encode
                    $query .= (!empty($query) ? '&' : '') . rawurlencode($k) . '=' . rawurlencode($v);
                }
            }
        } else {
            // Subsequent times around the loop, handling parameter key values.
            // If the keys are sequential numeric, then leave out the keys.
            $i = 0;
            foreach($args as $key => $arg) {
                if ($key >= 0 && $key == $i) {
                    // The keys are in the sequence 0, 1, 2, so use an empty key.
                    $encoded_key = '';
                    $i += 1;
                } else {
                    // The numeric sequence has been broken, so include all the key values now.
                    $encoded_key = rawurlencode($key);
                    $i = -1;
                }

                if (is_array($arg)) {
                    $query .= xarServer::__array2query($arg, $prefix . '['.$encoded_key.']');
                } else {
                    $query .= $prefix . '['.$encoded_key.']' . '=' . rawurlencode($arg);
                }
            }
        }
        return $query;
    }

    
    /**
     * Get and cache current URI (from xarRequest). Remove references.
     *
     * @access public
     * @return string current URI
     */
    static public function getCurrentURI()
    {
        if (self::$_currentUri === NULL) {
            self::$_currentUri = xarRequest::getClientURI();

            if (empty(self::$_currentUri)) self::$_currentUri = xarRequest::getServerURI(TRUE);

            // If the request has a '#' target, then remove it now, since the server
            // is never meant to see the target.
            if (strpos(self::$_currentUri, '#') > 0) {
                self::$_currentUri = substr(self::$_currentUri, 0, strpos(self::$_currentUri, '#'));
            }
        }
        return self::$_currentUri;
    }
    /**
     * Get current URL (and optionally add/replace some parameters)
     *
     * @access public
     * @param args array additional parameters to be added to/replaced in the URL (e.g. theme, ...)
     * @param generateXMLURL boolean over-ride Server default setting for generating XML URLs (TRUE/FALSE/NULL)
     * @param target string add a 'target' component to the URL
     * @return string current URL
     * @todo cfr. BaseURI() for other possible ways, or try PHP_SELF
     */
    static public function getCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL)
    {
        static $callback_isset = NULL;

        $host     = self::$_host       !== NULL ? self::$_host       : self::getHost();
        $protocol = self::$_protocol   !== NULL ? self::$_protocol   : self::getProtocol();
        $request  = self::$_currentUri !== NULL ? self::$_currentUri : self::getCurrentURI();
        $baseurl  = "$protocol://$host";

        // add optional parameters
        if (count($args) > 0) {
            // Parse the current URL, ensure we are not parsing a relative url
            $parsed_url = parse_url($baseurl.$request);

            // Parse the query string into an array of parameters.
            $query = (!empty($parsed_url['query']) ? $parsed_url['query'] : '');
            // CHECKME: parse_str() can return unset variables as NULL, and these will be
            // stripped out later. However, for xarVarFetch(), 'foo=' is quivalent to ''.
            // Is this behaviour desirable?
            parse_str($query, $parsed_query);

            foreach ($args as $k => $v) {
                if (is_array($v)) {
                    // The parameter value is an array.
                    // Replace the existing parameter in the URL outright.
                    $parsed_query[$k] = $v;
                } elseif (preg_match('/\[\]/', $k)) {
                    // Key points to an array element.
                    // Evaluate the element and change just that key.
                    // - If the value is NULL, then remove that element.
                    // - If the key is not set, then merge that element into the array.
                    @parse_str(urlencode($k) . '=' . $v, $array_param);
                    if (!empty($array_param) && isset($v)) {
                        // Merge in this element.
                        // TODO: check for duplicate values - we don't want to add an element
                        // value that is already there.
                        $parsed_query = array_merge_recursive($parsed_query, $array_param);
                    }
                } else {
                    // Value is a scalar.
                    // Do a straight replace. If the value is NULL then these
                    // will be trimmed later.
                    $parsed_query[$k] = $v;
                }
            }

            // Iteratively remove all NULL elements.
            if (!isset($callback_isset)) $callback_isset = create_function('$x', 'return !is_NULL($x);');
            $parsed_query = array_filter($parsed_query, $callback_isset);

            // TODO: convert the array back into a query string and insert back into the URL.
            $new_query = self::__array2query($parsed_query);

            // Strip off any existing query parameters.
            $request = preg_replace('/[?].*/', '', $request);

            // Add on the new query parameters (everything after the first '?').
            if (!empty($new_query)) $request .= '?' . $new_query;
        }

     // Finish up
        if (!isset($generateXMLURL)) $generateXMLURL = self::$__generateXmlUrls;
        if (isset($target)) $request .= '#' . urlencode($target);
        if ($generateXMLURL) $request = htmlspecialchars($request);
        return $baseurl . $request;
    }

    /**
     * Generates an URL that reference to a module function.
     *
     * Cfr. xarModURL() in modules
     */
    static public function getModuleURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL, $fragment = NULL, $entrypoint = array())
    {
        // CHECKME: move xarModURL() and xarMod__URL* stuff here, and leave stub in modules ?
        return xarModURL($modName, $modType, $funcName, $args, $generateXMLURL, $fragment, $entrypoint);
    }

    /**
     * Generates an URL that reference to an object user interface method (TODO).
     */
    static public function getObjectURL($objectName = NULL, $methodName = 'view', $args = array(), $generateXMLURL = NULL, $fragment = NULL, $entrypoint = array())
    {
        // 1. override any existing 'method' in args, and place before the rest
        if (!empty($methodName)) {
            $args = array('method' => $methodName) + $args;
        }
        // 2. override any existing 'object' or 'name' in args, and place before the rest
        if (!empty($objectName)) {
            unset($args['name']);
            // use 'object' here to distinguish from module URLs
            $args = array('object' => $objectName) + $args;
        }
        // 3. remove default method 'view' from URLs
        if ($args['method'] == 'view') {
            unset($args['method']);
        // and remove default method 'display' from URLs with an itemid
        } elseif (!empty($args['itemid']) && $args['method'] == 'display') {
            unset($args['method']);
        }
        // TODO: this is just a temporary fix (until xarRequest supports URLs with ?object=...&method=..., and index.php can call ... ?)
        return xarServer::getModuleURL('dynamicdata', 'object', 'main', $args, $generateXMLURL, $fragment, $entrypoint);
    }
}

class xarRequest extends xarRequestBone
{
    static private $__allowShortUrls = TRUE;
    static private $__defaultRequestInfo = array();
    static private $__shortUrlVariables = array();
    /**
     * Initialize
     */
    static public function init($args)
    {
        self::$__allowShortUrls = $args['enableShortURLsSupport'];
        self::$__shortUrlVariables = array();
        self::$__defaultRequestInfo = array($args['defaultModuleName'],
                                          $args['defaultModuleType'],
                                          $args['defaultModuleFunction']);
    }

    static public function getAllowShortUrls()
    {
        return self::$__allowShortUrls;
    }
    
    static public function getShortUrlVariables()
    {
        return self::$__shortUrlVariables;
    }
    
   /**
     * Get request variable
     *
     * @access public
     * @param name string
     * @param allowOnlyMethod string
     * @return mixed
     * @todo change order (POST normally overrides GET)
     * @todo have a look at raw post data options (xmlhttp postings)
     */
    static public function getVar($name, $allowOnlyMethod = NULL)
    {
        if ($allowOnlyMethod == 'GET') {
            // Short URLs variables override GET variables
            if (self::$__allowShortUrls && isset(self::$__shortUrlVariables[$name])) {
                $value = self::$__shortUrlVariables[$name];
            } elseif (isset($_GET[$name])) {
                // Then check in $_GET
                $value = $_GET[$name];
            } else {
                // Nothing found, return void
                return;
            }
            $method = $allowOnlyMethod;
        } elseif ($allowOnlyMethod == 'POST') {
            if (isset($_POST[$name])) {
                // First check in $_POST
                $value = $_POST[$name];
            } else {
                // Nothing found, return void
                return;
            }
            $method = $allowOnlyMethod;
        } else {
            if (self::$__allowShortUrls && isset(self::$__shortUrlVariables[$name])) {
                // Short URLs variables override GET and POST variables
                $value = self::$__shortUrlVariables[$name];
                $method = 'GET';
            } elseif (isset($_POST[$name])) {
                // Then check in $_POST
                $value = $_POST[$name];
                $method = 'POST';
            } elseif (isset($_GET[$name])) {
                // Then check in $_GET
                $value = $_GET[$name];
                $method = 'GET';
            } else {
                // Nothing found, return void
                return;
            }
        }

        $value = xarMLS_convertFromInput($value, $method);
        //DEPRECATED 5.3 REMOVE AT PHP6.0
        if (version_compare(PHP_VERSION,'5.3.0','<')) {
            if (get_magic_quotes_gpc()) {
                xarVar_stripSlashes($value);
            }
        }
        return $value;
    }

    /**
     * Get client request URI for Xaraya
     * That's the URI before being rewritten and sent by the client browser
     * @access private
     * @return string URI for Xaraya
     * @todo See code comments.
     */
    static public function getClientUri()
    {
        // Attempt to extract URI from IIS ISAPI Rewrite 3
        $request = xarServerBone::getVar('HTTP_X_REWRITE_URL');

        // IIS ISAPI Rewrite is not existing
        if (empty($request)) {
            // Try to get URI from the standard REQUEST_URI server var.
            // Works for Apache Mod_Rewrite
            $request = xarServerBone::getVar('REQUEST_URI');
        }

        // TODO: support more rewriting modules

        return $request;
    }

    /**
     * Get server request URI for Xaraya
     * That's the URI before after being rewritten seen by the server
     * @access private
     * @return string URI for Xaraya
     * @todo See code comments.
     */
    static public function getServerUri($withpathinfo = TRUE, $withquery = FALSE)
    {
        // References:
        // http://bugs.php.net/bug.php?id=42198
        // https://www.lakeworks.com/forums/script-name-path-info-php-self-t951.html

        // adapted patch from Chris van de Steeg for IIS
        $scriptname = xarServerBone::getVar('SCRIPT_NAME');
        
        if (empty($scriptname) || $withpathinfo) {
            $pathinfo = xarServerBone::getVar('PATH_INFO');
        } else {
            $pathinfo = '';
        }
        if ($pathinfo == $scriptname) $pathinfo = '';
        if (!empty($scriptname)) {
            $request = $scriptname . $pathinfo;
        }
        else {
            $request = '/';
        }
        // no query string wanted
        if (!$withquery) return $request;

        // get the query string
        $querystring = xarServerBone::getVar('QUERY_STRING');
        if (!empty($querystring)) {
            $request .= '?' . $querystring;
        }
        return $request;
    }
    
    /**
     * Gets request info for current page.
     *
     * Example of short URL support :
     *
     * index.php/<module>/<something translated in xaruserapi.php of that module>, or
     * index.php/<module>/admin/<something translated in xaradminapi.php>
     *
     * We rely on function <module>_<type>_decode_shorturl() to translate PATH_INFO
     * into something the module can work with for the input variables.
     * On output, the short URLs are generated by <module>_<type>_encode_shorturl(),
     * that is called automatically by xarModURL().
     *
     * Short URLs are enabled/disabled globally based on a base configuration
     * setting, and can be disabled per module via its admin configuration
     *
     * TODO: evaluate and improve this, obviously :-)
     * + check security impact of people combining PATH_INFO with func/type param
     *
     * @return array requested module, type and func
     * @todo <marco> Do we need to do a preg_match on $params[1] here?
     * @todo <mikespub> you mean for upper-case Admin, or to support other funcs than user and admin someday ?
     * @todo <marco> Investigate this aliases thing before to integrate and promote it!
     */
    static public function getInfo()
    {
        static $requestInfo = NULL;
        static $loopHole = NULL;
        if (is_array($requestInfo)) {
            return $requestInfo;
        } elseif (is_array($loopHole)) {
            // FIXME: Security checks in functions used by decode_shorturl cause infinite loops,
            //        because they request the current module too at the moment - unnecessary ?
            xarLogMessage('Avoiding loop in xarRequest::getInfo()');
            return $loopHole;
        }
        // Get variables
        xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $modName, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('type', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $modType, 'user');
        xarVarFetch('func', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $funcName, 'main');

        //CGI-PHP support patch (inc subdirectory install)
        $path = xarServerBone::getVar('PATH_INFO');
        $scriptname=xarServerBone::getVar('SCRIPT_NAME');

        if (sys::isInstall()) return self::$__defaultRequestInfo;

        if (strlen(xarSystemVars::get(NULL,'BaseModURL',TRUE))==0) {
            $basemodurl='index.php';
        } else {
            $basemodurl=xarSystemVars::get(NULL,'BaseModURL',TRUE);
        }

        if ($path == '') $path = substr(xarServerBone::getVar('REDIRECT_URL'),strlen(xarSystemVars::get(NULL,'BaseURI',TRUE)));

        $basefix = str_replace($path,'',xarServerBone::getVar('SCRIPT_NAME')); //Fix for win-apache

        if (self::$__allowShortUrls && empty($modName) && $path != ''
        //end CGI-PHP support patch
            // IIS fix and win-apache
            && $path != xarServerBone::getVar('SCRIPT_NAME') && $basefix !=$basemodurl) {
            /*
            Note: we need to match anything that might be used as module params here too ! (without compromising security)
            preg_match_all('|/([a-z0-9_ .+-]+)|i', $path, $matches);

            The original regular expression prevents the use of titles, even when properly encoded,
            as parts of a short-url path -- because it wouldn't not permit many characters that would
            in titles, such as parens, commas, or apostrophes.  Since a similiar "security" check is not
            done to normal URL params, I've changed this to a more flexable regex at the other extreme.

            This also happens to address Bug 2927

            TODO: The security of doing this should be examined by someone more familiar with why this works
            as a security check in the first place.
            */
            preg_match_all('|/([^/]+)|i', $path, $matches);

            $params = $matches[1];
            if (count($params) > 0) {
                $modName = $params[0];
                // if the second part is not admin, it's user by default
                if (isset($params[1]) && $params[1] == 'admin') $modType = 'admin';
                else $modType = 'user';
                // Check if this is an alias for some other module
                $modName = self::resolveModuleAlias($modName);

                // Call the appropriate decode_shorturl function
                if (xarMod::isAvailable($modName) && xarModGetVar($modName, 'SupportShortURLs') && xarMod::apiLoad($modName, $modType)) {
                    $loopHole = array($modName,$modType,$funcName);
                   // don't throw exception on missing file or function anymore
                    $res = xarMod::apiFunc($modName, $modType, 'decode_shorturl', $params, 0);
                    if (isset($res) && is_array($res)) {
                        list($funcName, $args) = $res;
                        if (!empty($funcName)) { // bingo
                            // Forward decoded args to xarRequestGetVar
                            if (isset($args) && is_array($args)) {
                                $args['module'] = $modName;
                                $args['type'] = $modType;
                                $args['func'] = $funcName;
                                self::$__shortUrlVariables = $args;
                            } else {
                                self::$__shortUrlVariables = (array('module' => $modName,'type' => $modType,'func' => $funcName));
                            }
                        }
                    }
                    $loopHole = NULL;
                }
            }
        }

        if (!empty($modName)) {
            // Check if this is an alias for some other module
            $modName = self::resolveModuleAlias($modName);
            // Cache values into info static var
            $requestInfo = array($modName, $modType, $funcName);
        } else {
            // If $modName is still empty we use the default module/type/func to be loaded in that such case
            $requestInfo = self::$__defaultRequestInfo;
        }
        return $requestInfo;
    }

    /**
     * Check to see if this is a local referral
     *
     * @access public
     * @return bool TRUE if locally referred, FALSE if not
     */
    static public function isLocalReferer()
    {
        $server = xarServer::getHost();
        $referer = xarServerBone::getVar('HTTP_REFERER');

        if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }


    /**
     * Checks if a module name is an alias for some other module
     *
     * @access private
     * @param aliasModName name of the module
     * @return string containing the module name
     * @throws BAD_PARAM
     */

    static public function resolveModuleAlias($var)
    {
        $aliasesMap = xarConfigGetVar('System.ModuleAliases');
        return (!empty($aliasesMap[$var])) ? $aliasesMap[$var] : $var;
    }

    /**
     * Check to see if this request is AJAX
     *
     * @access public
     * @return bool TRUE if AJAX request, FALSE otherwise
     */
    static public function isAjax()
    {
        static $isAjax = NULL;

        if ($isAjax === NULL) {
            // Most JS frameworks send a 'X-Requested-With' header with a value of
            // 'XMLHttpRequest' when the request is made in an AJAX context.
            //
            // For those that don't, we catch a URL param named 'xhr',  which when
            // present and not NULL, also signifies AJAX context.
            if (xarServerBone::getVar('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') {
                $isAjax = TRUE;
            } elseif (xarRequest::getVar('xhr') !== NULL) {
                $isAjax = TRUE;
            } else {
                $isAjax = FALSE;
            }
        }

        return $isAjax;
    }
}

class xarResponse extends xarResponseBone
{
    static private $__closeSession = TRUE;    // we usually will have sessions

    /**
     * initialize
     **/
    static public function init($args)
    {
        // Is this still useful? reassess in light of changes in xarCore esp after variable scenario merge
        self::$__closeSession = $args['whatelseisgoingloaded'] & XARCORE_SYSTEM_SESSION;
    }

    /**
     * Carry out a redirect
     *
     * @access public
     * @param redirectURL string the URL to redirect to
     */
    static public function Redirect($redirectUrl, $httpStatusCode = NULL)
    {
        if (headers_sent()) return FALSE;
        
        if ($httpStatusCode === NULL) $httpStatusCode = 302;

        // Remove &amp; entites to prevent redirect breakage
        $redirectUrl = str_replace('&amp;', '&', $redirectUrl);

        if (substr($redirectUrl, 0, 4) != 'http') {
            // Removing leading slashes from redirect url
            $redirectUrl = preg_replace('!^/*!', '', $redirectUrl);

            // Get base URL
            $baseurl = xarServer::getBaseURL();
            $redirectUrl = $baseurl.$redirectUrl;
        }

        parent::sendStatusCode($httpStatusCode, $redirectUrl);
        //this exit should be the only place we do this explicitly except for index.php end
        exit();
    }
    
    /**
     * Send an http status code header and call the corresponding message-xxxxxxx.xd templates from the base module
     * @access protected
     * @param int $statusCode
     * @param string $msg
     * @param string  $modName
     * @param string  $modType
     * @param string  $funcName
     * @param string  $templateName
     */
    static protected function _sendError($statusCode, $msg = '', $modName, $modType, $funcName, $templateName)
    {
        parent::sendStatusCode($statusCode);
        if (function_exists('xarTplSetPageTitle')) {
            // If we don't use the core template bit, it crashes the core. 
            xarTplSetPageTitle(parent::$_codeSent . ' ' . parent::$_arrStatusCodes[parent::$_codeSent]);

            $theme_dir = xarTplGetThemeDir();
            $data = array('msg'=>$msg);

            if(file_exists($theme_dir . '/modules/base/'.$modType.'-'.$templateName . '.xt')) {
                $errorfile = $theme_dir . '/modules/base/'.$modType.'-'.$templateName . '.xt';
            } elseif(file_exists($theme_dir . '/modules/base/'.$modType.'-'.$funcName . '.xt')) {
                $errorfile = $theme_dir . '/modules/base/'.$modType.'-'.$funcName . '.xt';
            } else {
                $errorfile = 'modules/base/xartemplates/'.$modType.'-'.$funcName . '.xd';
            }
            $output = xarTplFile($errorfile, $data);
        } else {
            $output = '<html><head><title>'.parent::$_codeSent . ' ' . parent::$_arrStatusCodes[parent::$_codeSent].'</title></head><body><h1>'.parent::$_codeSent . ' ' . parent::$_arrStatusCodes[parent::$_codeSent].'</h1><p>'.$msg.'</p></body></html>';
        }
        return $output;
    }

    /**
     * Return a 404 Not Found header, and fill in the template message-notfound.xd from the base module
     * Usage in GUI functions etc.:
     *    if (something not found, e.g. item $id) {
     *        $msg = xarML("Sorry, item #(1) is not available right now", $id);
     *        return xarResponseNotFound($msg);
     *    }
     *    ...
     * @access public
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string the template message-notfound.xd from the base module filled in
     */
    static public function NotFound($msg = '', $modName = 'base', $modType = 'message', $funcName = 'notfound', $templateName = NULL)
    {
        //jojo - we want to display the page here so people know what the URL is
        return self::_sendError(404, $msg, $modName, $modType, $funcName, $templateName);
    }

    /**
     * Return a 403 Forbidden header, and fill in the message-forbiddenoperation.xd template from the base module
     * Usage in GUI functions etc.:
     *
     *    if (something not allowed, e.g. edit item $id) {
     *        $msg = xarML("Sorry, you are not allowed to edit item #(1)", $id);
     *        return xarResponseForbidden($msg);
     *    }
     *    ...
     * @access public
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string the template message-forbiddenoperation.xd from the base module filled in
     */
    static public function Forbidden($msg = '', $modName = 'base', $modType = 'message', $funcName = 'forbiddenoperation', $templateName = NULL)
    {
        //jojo - we cannot rely on the calling function to protect the page content
        //force a redirect here and stop the specific page from loading further.
        return self::_sendError(403, $msg, $modName, $modType, $funcName, $templateName);
    }
    
    /**
     * Return a 503 Service Unavailable, and fill in the template message-serviceunavailable.xd from the base module
     * Usage in GUI functions etc.:
     *    if (something not found, e.g. item $id) {
     *        $msg = xarML("Sorry, item #(1) is not available right now", $id);
     *        return xarResponseServiceUnavailable($msg);
     *    }
     *    ...
     * @access public
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string the template message-serviceunavailable.xd from the base module filled in
     */
    static public function ServiceUnavailable($msg = '', $modName = 'base', $modType = 'message', $funcName = 'serviceunavailable', $templateName = NULL)
    {
        self::_sendError(503, $msg, $modName, $modType, $funcName, $templateName);
    }
}
?>
