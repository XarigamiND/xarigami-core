<?php
/**
 * Xarigami Caching
 *
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
/**
 * Cache data in the database using the xar_cache_data table
 * @TODO - jojo - rework this for our system
 */

class xarCache_Database_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public $table = '';
    public $lastkey = null;
    public $lastid = null;
    public $value = null;
    private $dbconn = null;

    public function __construct(Array $args = array())
    {
        parent::__construct($args);
        $this->storage = 'database';
    }

    public function getTable()
    {
        if (!empty($this->table)) {
            return $this->table;

        } elseif (class_exists('xarDB')) {
            $this->dbconn = xarDB::$dbconn;
            $this->table = xarDB::$prefix . '_cache_data';
            return $this->table;

        } else {

            // can't use this storage until the core is loaded !
        }
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {

        if (empty($expire)) {
            $expire = $this->expire;
        }
         $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($table)) return false;

        // we actually retrieve the value here too
        $query = "SELECT xar_id, xar_time, xar_size, xar_check, xar_data
                  FROM $table
                  WHERE xar_type = ? AND xar_key = ? AND xar_code = ?";
        $bindvars = array($this->type, $key, $this->code);
        // Prepare it once.
         $result = $dbconn->Execute($query,$bindvars);

        $this->lastkey = $key;

        if ($result) {
            $result->close();
            $this->lastid = null;
            $this->value = null;
            if ($log) $this->logStatus('MISS', $key);
            return false;
        }
        list($id,$time,$size,$check,$data) = $result->fields;
        $result->close();

        // TODO: process $size and $check if compressed ?

        $this->lastid = $id;
        if (!empty($expire) && $time < time() - $expire) {
            $this->value = null;
            if ($log) $this->logStatus('MISS', $key);
            return false;
        } else {
            $this->value = $data;
            $this->modtime = $time;
            if ($log) $this->logStatus('HIT', $key);
            return true;
        }
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {

        if (empty($expire)) {
            $expire = $this->expire;
        }
        if ($key == $this->lastkey && isset($this->value)) {
            $this->lastkey = null;
            if ($output) {
                // output the value directly to the browser
                echo $this->value;
                return true;
            } else {
                return $this->value;
            }
        }
          $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($table)) return;

        $query = "SELECT xar_id, xar_time, xar_size, xar_check, xar_data
                  FROM $table
                  WHERE xar_type = ? AND xar_key = ? AND xar_code = ?";
        $bindvars = array($this->type, $key, $this->code);

         $result = $dbconn->Execute($query,$bindvars);

        $this->lastkey = $key;

        if ($result) {
            $result->close();
            $this->lastid = null;
            $this->value = null;
            return;
        }
        list($id,$time,$size,$check,$data) = $result->fields;
        $result->close();

        // TODO: process $size and $check if compressed ?

        $this->lastid = $id;
        if (!empty($expire) && $time < time() - $expire + 10) { // take some margin here
            return;
        } elseif ($output) {
            // output the value directly to the browser
            echo $data;
            return true;
        } else {
            return $data;
        }
    }

    public function setCached($key = '', $value = '', $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $time = time();
        $size = strlen($value);
        if ($this->compressed) {
            $check = crc32($value);
        } else {
            $check = '';
        }
        die($key);

          $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($table)) return;

        // TODO: is a transaction warranted here?
        // Since we catch the exception if someone beat us to it, a transaction could
        // cause a deadlock here?
        if ($key == $this->lastkey && !empty($this->lastid)) {
            $query = "UPDATE $table
                         SET xar_time = ?,
                             xar_size = ?,
                             xar_check = ?,
                             xar_data = ?
                       WHERE xar_id = ?";
            $bindvars = array($time, $size, $check, $value, (int) $this->lastid);
              $result = $dbconn->Execute($query,$bindvars);
        } else {
            try {
                $query = "INSERT INTO $table (xar_type, xar_key, xar_code, xar_time, xar_size, xar_check, xar_data)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
                $bindvars = array($this->type, $key, $this->code, $time, $size, $check, $value);
                  $result = $dbconn->Execute($query,$bindvars);
            } catch (Exception $e) {
                // someone else beat us to it - ignore error
            }
        }
        $this->lastkey = null;
    }

    public function delCached($key = '')
    {
          $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($table)) return;

        if ($key == $this->lastkey && !empty($this->lastid)) {
            $query = "DELETE FROM $table    WHERE xar_id = ?";
            $bindvars = array((int) $this->lastid);
        } else {
            $query = "DELETE FROM $table
                            WHERE xar_type = ? AND xar_key = ? AND xar_code = ?";
            $bindvars = array($this->type, $key, $this->code);
        }
         $result = $dbconn->Execute($query,$bindvars);
        $this->lastkey = null;
    }

    public function flushCached($key = '')
    {
        //$table = $this->getTable();
         $dbconn = xarDB::$dbconn;
            $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($key)) {
            $query = "DELETE FROM $table WHERE xar_type = ?";
            $bindvars = array($this->type);
        } else {
            $key = '%'.$key.'%';
            $query = "DELETE FROM $table  WHERE xar_type = ? AND xar_key LIKE ?";
            $bindvars = array($this->type,$key);
        }
        $result = $dbconn->Execute($query,$bindvars);

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
        $this->lastkey = null;
    }

    public function doGarbageCollection($expire = 0)
    {
         $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($table)) return;

        $time = time() - ($expire + 60); // take some margin here

        $query = "DELETE FROM $table
                        WHERE xar_type = ? AND xar_time < ?";
        $bindvars = array($this->type, $time);
          $result = $dbconn->Execute($query,$bindvars);

        $this->lastkey = null;
    }

    public function getCacheInfo()
    {
        $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;


        $query = "SELECT SUM(xar_size), COUNT(xar_id), MAX(xar_time)
                   FROM $table
                   WHERE xar_type = ?";
        $bindvars = array($this->type);
        $result = $dbconn->Execute($query,$bindvars);
         if ($result) {
            list($size,$count,$time) = $result->fields;
            $this->size = $size;
            $this->items = $count;
            $this->modtime = $time;
        }
        $result->close();

        return array('size'    => $this->size,
                     'items'   => $this->items,
                     'hits'    => $this->hits,
                     'misses'  => $this->misses,
                     'modtime' => $this->modtime);
    }

    public function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) return;

        if ($key == $this->lastkey && isset($this->value)) {
            $value = $this->value;
        } else {
            $value = $this->getCached($key);
        }
        if (empty($value)) return;

        $tmp_file = $filename . '.tmp';

        $fp = @fopen($tmp_file, "w");
        if (!empty($fp)) {
            @fwrite($fp, $value);
            @fclose($fp);
            // rename() doesn't overwrite existing files in Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @copy($tmp_file, $filename);
                @unlink($tmp_file);
            } else {
                @rename($tmp_file, $filename);
            }
        }
    }

    public function getCachedList()
    {
          $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($table)) return false;

        // we actually retrieve the value here too
        $query = "SELECT xar_id, xar_time, xar_key, xar_code, xar_size, xar_check
                  FROM $table
                  WHERE xar_type = ?";
        $bindvars = array($this->type);
          $result = $dbconn->Execute($query,$bindvars);

        $list = array();
         while(!$result->EOF) {
            list($id,$time,$key,$code,$size,$check) = $result->fields;
            $list[$id] = array('key'   => $key,
                               'code'  => $code,
                               'time'  => $time,
                               'size'  => $size,
                               'check' => $check);
             $result->close();
        }
        $result->close();
        return $list;
    }

    public function getCachedKeys()
    {
         $dbconn = xarDB::$dbconn;
        $table = xarDB::$prefix . '_cache_data';
        if (empty($table)) return;

        if (empty($table)) return false;

        $query = "SELECT DISTINCT xar_key
                  FROM $table
                  WHERE xar_type = ?";
        $bindvars = array($this->type);
         $result = $dbconn->Execute($query,$bindvars);

        $list = array();
          while(!$result->EOF) {
            list($key) = $result->fields;
            $list[] = $key;
             $result->close();
        }
        $result->close();
        return $list;
    }
}

?>