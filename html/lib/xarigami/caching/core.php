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
 *
 * @author mikespub
 */

sys::import('xarigami.xarVar');

/**
 * Core caching in memory for frequently-used values (within a single HTTP request)
 */
class xarCoreCache extends xarObject
{
    private static $__cacheCollection = array();
    private static $__cacheIsCached = NULL;
    private static $__cacheStorage = NULL;

    /**
     * Initialise the caching options
     *
     * @param array $config caching configuration from config.caching.php
     * @return boolean
     * @todo configure optional second-level cache here?
    **/
    public static function init($config = array())
    {
        if (self::$__cacheIsCached === NULL) {
            // isCached is placed in the cache collection. So all the data are contained in the collection.
            self::$__cacheCollection['iscached'] = array();
            self::$__cacheIsCached = &self::$__cacheCollection['iscached'];
        }
        xarVars::setCache(self::$__cacheCollection, self::$__cacheIsCached);
        return TRUE;
    }

    /**
     * Check if a variable value is cached
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return boolean TRUE if the variable is cached, FALSE if not
    **/
    public static function isCached($scope, $name)
    {
        if (isset(self::$__cacheIsCached[$scope][$name])) {
            return TRUE;

        // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
        } elseif (isset(self::$__cacheStorage) && self::$__cacheStorage->isCached($scope.':'.$name)) {
            // pre-fetch the value from second-level cache here (if we don't load from bulk storage)
            self::$__cacheCollection[$scope][$name] = self::$__cacheStorage->getCached($scope.':'.$name);
            self::$__cacheIsCached[$scope][$name] = TRUE;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Get the value of a cached variable
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return mixed value of the variable, or NULL if variable isn't cached
    **/
    public static function getCached($scope, $name)
    {
        if (!isset(self::$__cacheIsCached[$scope][$name])) {
            // don't fetch the value from second-level cache here
            return NULL;
        }
        return self::$__cacheCollection[$scope][$name];
    }

    /**
     * Set the value of a cached variable
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @param string $value the new value for that variable
     * @return NULL
    **/
    public static function setCached($scope, $name, $value)
    {
        self::$__cacheCollection[$scope][$name] = $value;
        self::$__cacheIsCached[$scope][$name] = TRUE;
    }

    /**
     * Delete a cached variable
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return NULL
    **/
    public static function delCached($scope, $name)
    {
        if (isset(self::$__cacheIsCached[$scope][$name])) {
            unset(self::$__cacheCollection[$scope][$name], self::$__cacheIsCached[$scope][$name]);
        }
    }

    /**
     * Flush a particular cache (e.g. for session initialization)
     *
     * @param string $scope the scope identifying which part of the cache you want to wipe out
     * @return NULL
    **/
    public static function flushCached($scope)
    {
        if (isset(self::$__cacheCollection[$scope])) {
            unset(self::$__cacheCollection[$scope], self::$__cacheIsCached[$scope][$name]);
        }
    }

    /**
     * Set second-level cache storage if you want to keep values for longer than the current HTTP request
     *
     * @param object $__cacheStorage  the cache storage instance you want to use (typically in-memory like apc, memcached, xcache, ...)
     * @param int    $cacheExpire   how long do you want to keep values in second-level cache storage (if the storage supports it)
     * @param bool   $__isBulkStorage do we load/save all variables in bulk by scope or not ?
     * @return NULL
    **/
    public static function setCacheStorage($__cacheStorage, $cacheExpire = 0, $__isBulkStorage = 1)
    {
        self::$__cacheStorage = $__cacheStorage;
        self::$__cacheStorage->setExpire($cacheExpire);
        // Make sure we use type 'core' for the cache storage here
        if (empty(self::$__cacheStorage->type) || self::$__cacheStorage->type != 'core') {
            self::$__cacheStorage->type = 'core';
            // Update the global namespace and prefix of the cache storage
            self::$__cacheStorage->setNamespace(self::$__cacheStorage->namespace);
        }
        // see what's going on in the cache storage ;-)
        //self::$__cacheStorage->logfile = sys::varpath() . '/logs/core_cache.txt';
        // FIXME: some in-memory cache storage requires explicit garbage collection !?

        // load from second-level cache storage here
        self::loadBulkStorage();
        // save to second-level cache storage at shutdown
        register_shutdown_function(array('xarCoreCache','saveBulkStorage'));
    }

/**
 * CHECKME: work with bulk load / bulk save per scope instead of individual gets per scope:name ?
 *          But what about concurrent updates in bulk then (+ unserialize & autoload too early) ?
 *          There doesn't seem to be a big difference in performance using bulk or not, at least with xcache
 */

    public static function loadBulkStorage()
    {
        if (!isset(self::$__cacheStorage)) return;
        // get the list of scopes
        if (!self::$__cacheStorage->isCached('__scopelist__')) return;
        $scopelist = self::$__cacheStorage->getCached('__scopelist__');
        if (empty($scopelist)) return;
        // load each scope from second-level cache
        foreach ($scopelist as $scope) {
            self::$__cacheCollection[$scope] = self::$__cacheStorage->getCached($scope);
        }
    }

    public static function saveBulkStorage()
    {
        if (!isset(self::$__cacheStorage)) return;
        // get the list of scopes
        $scopelist = array_keys(self::$__cacheCollection);
        self::$__cacheStorage->setCached('__scopelist__', $scopelist);
        // save each scope to second-level cache
        foreach ($scopelist as $scope) {
            $value = serialize(self::$__cacheCollection[$scope]);
            self::$__cacheStorage->setCached($scope, $value);
        }
    }
}

?>