<?php
/**
 * Xarigami Caching
 *
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 *
 * Interfaces for cache storage
 */

interface ixarCache_Storage
{
    public function __construct(Array $args = array());
    public function setNamespace($namespace = '');
    public function setCode($code = '');
    public function getCacheKey($key = '');
    public function setExpire($expire = 0);
    public function getLastModTime();
    public function isCached($key = '', $expire = 0, $log = 1);
    public function getCached($key = '', $output = 0, $expire = 0);
    public function setCached($key = '', $value = '', $expire = 0);
    public function delCached($key = '');
    public function flushCached($key = '');
    public function cleanCached($expire = 0);
    public function doGarbageCollection($expire = 0);
    public function getCacheInfo();
    public function getCacheSize($countitems = false);
    public function getCacheItems();
    public function sizeLimitReached();
    public function logStatus($status = 'MISS', $key = '');
    public function saveFile($key = '', $filename = '');
    public function getCachedList();
    public function getCachedKeys();
}
?>