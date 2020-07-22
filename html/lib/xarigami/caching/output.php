<?php
/**
 * Xaraya Output Cache
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 *
 * @author mikespub
 */

class xarOutputCache extends xarObject
{
    public static $cacheDir             = 'var/cache/output';
    public static $cacheTheme           = '';
    public static $cacheSizeLimit       = 2097152;
    public static $cacheCookie          = 'XARIGAMISID';
    public static $cacheLocale          = 'en_US.utf-8';

    public static $pageCacheIsEnabled   = false;
    public static $blockCacheIsEnabled  = false;
    public static $moduleCacheIsEnabled = false;
    public static $objectCacheIsEnabled = false;

    /**
     * Initialise the caching options
     *
     * @param array $config caching configuration from config.caching.php
     * @return boolean
     * @todo consider the use of a shutdownhandler for cache maintenance
     */
    public static function init(array $config = array())
    {
        if (empty($config)) {
            return false;
        }

        // specify the output cache directory
        if (empty($config['Output.CacheDir']) || !is_dir($config['Output.CacheDir'])) {
            $config['Output.CacheDir'] = xarCache::$cacheDir . '/output';
        }
        self::$cacheDir       = realpath($config['Output.CacheDir']);
        self::$cacheTheme     = isset($config['Output.DefaultTheme']) ?
            $config['Output.DefaultTheme'] : '';
        self::$cacheSizeLimit = isset($config['Output.SizeLimit']) ?
            $config['Output.SizeLimit'] : 2097152;
        self::$cacheCookie    = isset($config['Output.CookieName']) ?
            $config['Output.CookieName'] : 'XARIGAMISID';
        self::$cacheLocale    = isset($config['Output.DefaultLocale']) ?
            $config['Output.DefaultLocale'] : 'en_US.utf-8';

        if (file_exists(self::$cacheDir . '/cache.pagelevel')) {
            sys::import('xarigami.caching.output.page');
            // Note : we may already exit here if session-less page caching is enabled
            self::$pageCacheIsEnabled = xarPageCache::init($config);
        }

        if (file_exists(self::$cacheDir . '/cache.blocklevel')) {
            sys::import('xarigami.caching.output.block');
            self::$blockCacheIsEnabled = xarBlockCache::init($config);
        }

        if (file_exists(self::$cacheDir . '/cache.modulelevel')) {
            sys::import('xarigami.caching.output.module');
            self::$moduleCacheIsEnabled = xarModuleCache::init($config);
        }

        if (file_exists(self::$cacheDir . '/cache.objectlevel')) {
            sys::import('xarigami.caching.output.object');
            self::$objectCacheIsEnabled = xarObjectCache::init($config);
        }

        return true;
    }

}

?>
