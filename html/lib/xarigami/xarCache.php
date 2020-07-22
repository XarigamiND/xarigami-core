<?php
/**
 * Xarigami Caching
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

/* Wrappers for older functions */
function xarCache_init($args = false) { return xarCache::init($args); }
function xarCache_getStorage(array $args = array()) { return xarCache::getStorage($args); }
function xarCache_getParents() { return xarCache::getParents();}

class xarCache extends xarObject
{
    public static $outputCacheIsEnabled    = false;
    public static $coreCacheIsEnabled      = true;
    public static $templateCacheIsEnabled  = true; // currently unused, cfr. xaraya/templates.php
    public static $variableCacheIsEnabled  = false;
    public static $cacheDir                = '';

    /**
     * Initialise the caching options
     *
     * @param string $cacheDir optional cache directory (default is sys::varpath() . '/cache')
     * @return null or exit if session-less page caching finds a hit
     */
    public static function init($cacheDir = null)
    {
        if (empty($cacheDir) || !is_dir($cacheDir)) {
            $cacheDir = sys::varpath() . '/cache';
        }
        self::$cacheDir = $cacheDir;

        // load the caching configuration
        $config = self::getConfig();

        // enable output caching - only active when specifically active through xarCacheManager or direct config file creating/editing
        if (file_exists(self::$cacheDir . '/output/cache.touch')) {
            if (!empty($config)) {
                // initialize the output cache
                sys::import('xarigami.caching.output');
                self::$outputCacheIsEnabled = xarOutputCache::init($config);
                // Note : we may already exit here if session-less page caching is enabled

            } else {
                // if the config file is missing or empty, turn off output caching
                @unlink(self::$cacheDir . '/output/cache.touch');
            }
        }

        // enable core caching which is normal in all sites
        sys::import('xarigami.caching.core');
        self::$coreCacheIsEnabled = xarCoreCache::init($config);

        // enable template caching ? Too early in the process here,
        // enable variable caching (requires activating autoload for serialized objects et al.)
        //variable caching is OFF until this is reviewed
        if (!empty($config['Variable.CacheIsEnabled'])) {
            sys::import('xarigami.caching.variable');
            self::$variableCacheIsEnabled = xarVariableCache::init($config);
        }
    }

    /**
     * Get the current caching configuration
     */
    public static function getConfig()
    {
        // load the caching configuration
        $cachingConfiguration = array();
        if (file_exists(self::$cacheDir . '/config.caching.php')) {
            @include(self::$cacheDir . '/config.caching.php');
        }
        return $cachingConfiguration;
    }

    /**
     * Get a cache key for page output caching
     *
     * @param string $url optional url to be checked if not the current url
     * @return mixed cacheKey to be used with xarPageCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getPageKey($url = null)
    {
        if (xarCache::$outputCacheIsEnabled && xarOutputCache::$pageCacheIsEnabled) {
            return xarPageCache::getCacheKey($url);
        }
    }

    /**
     * Get a cache key for block output caching
     *
     * @param array  $blockInfo block information
     * @return mixed cacheKey to be used with xarBlockCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getBlockKey($blockInfo)
    {
        if (xarCache::$outputCacheIsEnabled && xarOutputCache::$blockCacheIsEnabled) {
            return xarBlockCache::getCacheKey($blockInfo);
        }
    }

    /**
     * Get a cache key for module output caching
     *
     * @param string url optional url to be checked if not the current url
     * @return mixed cacheKey to be used with xarModuleCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getModuleKey($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (xarCache::$outputCacheIsEnabled && xarOutputCache::$moduleCacheIsEnabled) {
            return xarModuleCache::getCacheKey($modName, $modType, $funcName, $args);
        }
    }

    /**
     * Get a cache key for object output caching
     *
     * @param string url optional url to be checked if not the current url
     * @return mixed cacheKey to be used with xarObjectCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getObjectKey($objectName, $methodName = 'view', $args = array())
    {
        if (xarCache::$outputCacheIsEnabled && xarOutputCache::$objectCacheIsEnabled) {
            return xarObjectCache::getCacheKey($objectName, $methodName, $args);
        }
    }

    /**
     * Get a cache key for variable caching
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return mixed cacheKey to be used with xarVariableCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getVariableKey($scope, $name)
    {
        if (xarCache::$variableCacheIsEnabled) {
            return xarVariableCache::getCacheKey($scope, $name);
        }
    }

    /**
     * Disable caching of the current output, e.g. when an authid is generated or if we redirect
     */
    public static function noCache()
    {
        if (!xarCache::$outputCacheIsEnabled) {
            return;
        }
        if (xarOutputCache::$pageCacheIsEnabled) {
            // set the current cacheKey to null
            xarPageCache::$cacheKey = null;
            xarCoreCache::setCached('Page.Caching', 'nocache', true);
        }
        if (xarOutputCache::$blockCacheIsEnabled) {
            // set the current cacheKey to null
            xarBlockCache::$cacheKey = null;
        }
        if (xarOutputCache::$moduleCacheIsEnabled) {
            // set the current cacheKey to null
            xarModuleCache::$cacheKey = null;
        }
        if (xarOutputCache::$objectCacheIsEnabled) {
            // set the current cacheKey to null
            xarObjectCache::$cacheKey = null;
        }
    }

    /**
     * Keep track of some page title for caching - see xarTplSetPageTitle()
     */
    public static function setPageTitle($title = NULL, $module = NULL)
    {
        if (!xarCache::$outputCacheIsEnabled) {
            return;
        }
    // TODO: refactor common code ?
        if (xarOutputCache::$moduleCacheIsEnabled) {
            // set page title for module output
            xarModuleCache::setPageTitle($title, $module);
        }
        if (xarOutputCache::$objectCacheIsEnabled) {
            // set page title for object output
            xarObjectCache::setPageTitle($title, $module);
        }
    }

    /**
     * Keep track of some stylesheet for caching - see xarMod::apiFunc('themes','user','register')
     */
    public static function addStyle(Array $args=array())
    {
        if (!xarCache::$outputCacheIsEnabled) {
            return;
        }
    // TODO: refactor common code ?
        if (xarOutputCache::$moduleCacheIsEnabled) {
            // add stylesheet for module output
            xarModuleCache::addStyle($args);
        }
        if (xarOutputCache::$objectCacheIsEnabled) {
            // add stylesheet for object output
            xarObjectCache::addStyle($args);
        }
    }

    /**
     * Keep track of some javascript for caching - see xarTplAddJavaScript()
     */
    public static function addJavaScript($position, $type, $data, $index = '')
    {
        if (!xarCache::$outputCacheIsEnabled) {
            return;
        }
    // TODO: refactor common code ?
        if (xarOutputCache::$moduleCacheIsEnabled) {
            // add javascript for module output
            xarModuleCache::addJavaScript($position, $type, $data, $index);
        }
        if (xarOutputCache::$objectCacheIsEnabled) {
            // add javascript for object output
            xarObjectCache::addJavaScript($position, $type, $data, $index);
        }
    }

    /**
     * Get a storage class instance for some type of cached data
     *
     *
     * @param string  $storage the storage you want (filesystem, database or memcached)
     * @param string  $type the type of cached data (page, block, template, ...)
     * @param string  $cachedir the path to the cache directory (for filesystem)
     * @param string  $code the cache code (for URL factors et al.) if it's fixed
     * @param integer $expire the expiration time for this data
     * @param integer $sizelimit the maximum size for the cache storage
     * @param string  $logfile the path to the logfile for HITs and MISSes
     * @param integer $logsize the maximum size of the logfile
     * @return object the specified cache storage
     */
    public static function getStorage(array $args = array())
    {
        sys::import('xarigami.caching.storage');
        return xarCache_Storage::getCacheStorage($args);
    }

    /**
     * Get the parent group ids of the current user (with minimal overhead)
     *
     *
     * @return array of parent gids
     * @todo avoid DB lookup by passing groups via cookies ?
     * @todo Note : don't do this if admins get cached too :)
     */
    public static function getParents($currentid = null)
    {
        if (empty($currentid)) {
            $currentid = xarSession::getVar('uid');
        }
        if (xarCoreCache::isCached('User.Variables.'.$currentid, 'parentlist')) {
            return xarCoreCache::getCached('User.Variables.'.$currentid, 'parentlist');
        }
        $rolemembers = xarDB::$prefix . '_rolemembers';
        $dbconn = xarDB::$dbconn;
        $query = "SELECT xar_parentid FROM $rolemembers WHERE xar_uid = ?";

        $result = $dbconn->Execute($query, array($currentid));

        $gidlist = array();
         while(!$result->EOF) {
            list($parentid)= $result->fields;
            $gidlist[] = $parentid;
             $result->MoveNext();
        }
        $result->Close();
        xarCoreCache::setCached('User.Variables.'.$currentid, 'parentlist',$gidlist);
        return $gidlist;
    }

    /**
     * Get the output cache directory to access stats and items in cache storage even
     * if output caching is disabled (cfr. xarcachemanager admin stats/view/flushcache)
     */
    public static function getOutputCacheDir()
    {
        // make sure xarOutputCache is initialized
        if (!xarCache::$outputCacheIsEnabled) {
            // get the caching configuration
            $config = xarCache::getConfig();
            // initialize the output cache
            sys::import('xarigami.caching.output');
            //xarCache::$outputCacheIsEnabled = xarOutputCache::init($config);
            xarOutputCache::init($config);
            // make sure we don't cache here
            xarCache::noCache();
        }
        return xarOutputCache::$cacheDir;
    }
}

?>
