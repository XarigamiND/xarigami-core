<?php
/**
 * Configuration variable handling
 *
 * @package core
 * @subpackage variables
  * @copyright (C) 2007-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
sys::import('xarigami.xarVar');

function  xarConfigGetVar($name, $prep = NULL) { return xarConfigVars::get(NULL, $name); }
function  xarConfigDelVar($name)               { return xarConfigVars::delete(NULL, $name); }
function  xarConfigSetVar($name, $value)       { return xarConfigVars::set(NULL, $name, $value); }

/**
 * ConfigVars class
 *
 * @todo if core was module 0 this could be a whole lot simpler by derivation (or if all config variables were moved to a module)
 */
class xarConfigVars extends xarVars implements IxarVars
{
    private static $__key = 'Config.Variables'; // const cannot be private :-(
    private static $__bPreloaded = FALSE;
    private static $__initDone = FALSE;

    private static $__dbConn = NULL;
    private static $__configTable = NULL;

    public static function init($args = NULL, $whattoload = NULL)
    {
        // @TODO: handle things with multiple connections passed in args
        // We would need to also dissociate the caching to not mix several configs. We can then use eventually the scope for the connection index.
        if (!self::$__initDone || $args !== NULL) {
            // Configuration init needs to be done first
            if (!class_exists('xarDB')) sys::import('xarigami.xarDB');
            $tables = array('config_vars' => xarDB::$prefix . '_config_vars');
            xarDB::importTables($tables);
            self::$__configTable = $tables['config_vars'];
            self::$__dbConn = xarDB::$dbconn;
            self::$__initDone = TRUE;
        }
        return true;
    }

    /**
     * Sets a configuration variable.
     *
     * @param  string $name the name of the variable
     * @param  mixed  $value (array,integer or string) the value of the variable
     * @return boolean true on success, or false if you're trying to set unallowed variables
     * @todo return states that it should return false if we're setting
     *       unallowed variables.. there is no such code to do that in the function
     */
    public static function set($scope, $name, $value)
    {
        self::delete(NULL, $name);

        if (!self::$__initDone) self::init();

        //Here we serialize the configuration variables
        //so they can effectively contain more than one value
        $serialvalue = serialize($value);

        $seqId = self::$__dbConn->GenId(self::$__configTable);
        $query = 'INSERT INTO '.self::$__configTable.'
                      (xar_id, xar_name, xar_value)
                      VALUES (?,?,?)';
        $bindvars = array($seqId, $name, $serialvalue);
        if (!empty($query)){
             $result = self::$__dbConn->Execute($query,$bindvars);
            if (!$result) return FALSE;
        }

        $key = self::$__key;
        parent::$_cache[$key][$name] = $serialvalue;
        parent::$_iscached[$key][$name] = TRUE;
        return TRUE;
    }

    /**
     * Gets a configuration variable.
     *
     * @param string $scope not used
     * @param string $name  the name of the variable
     * @return mixed value of the variable(string), or void if variable doesn't exist
     * @todo do we need these aliases anymore ?
     * @todo the vars which are not in the database should probably be systemvars, not configvars
     * @todo bench the preloading
     */
    public static function get($scope, $name, $value=null)
    {
        // Preload the config vars once
        if(!self::$__bPreloaded) self::preload();

        // Configvars which are not in the database (either in config file or in code defines)
        switch($name) {
            case 'Site.DB.TablePrefix':
                return xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
                break;
            case 'System.Core.Generation':
                return xarCore::GENERATION;
                break;
            case 'System.Core.VersionNumber':
                return xarCore::VERSION_NUM;
                break;
            case 'System.Core.VersionId':
                return xarCore::VERSION_ID;
                break;
            case 'System.Core.VersionSub':
                return xarCore::VERSION_SUB;
                break;
            case 'prefix':
                // FIXME: Can we do this another way (dependency)
                return xarDB::$prefix;
                break;
        }

        $key = self::$__key /*. '.' . $scope*/;

        if (isset(parent::$_iscached[$key][$name])) {
            return unserialize(parent::$_cache[$key][$name]);
        }

        if (!isset(parent::$_iscached[$key])) {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }

        $cache = &parent::$_cache[$key];
        $iscached = &parent::$_iscached[$key];

        if (empty($iscached)) self::preload($scope);

        // From the cache
        if (isset($iscached[$name])) {
            return unserialize($cache[$name]);
        }

        if (!self::$__initDone) self::init();

        $query = 'SELECT xar_name, xar_value FROM '.self::$__configTable.' WHERE xar_name=?';

        $bindvars = array($name);
        //if (xarSystemVars::get(null,'DB.UseADODBCache')) {
       //      $result = self::$__dbConn->CacheExecute(3600*24*7,$query,$bindvars);
       // } else {
            $result = self::$__dbConn->Execute($query,$bindvars);
       // }
        if (!$result) return NULL;

        if ($result && $result->fields) {
            list($name, $value) = $result->fields;
            // Found it, retrieve and cache it
            $cache[$name] = $value;
            $iscached[$name] = TRUE;
            $result->close();
            $value = unserialize($value);
            return $value;
        }
        // @todo: We found nothing, return the default if we had one
        if ($value !== NULL) return $value;
        throw new VariableNotFoundException($name, "Variable #(1) not found");
    }

    public static function delete($scope, $name)
    {
        if (!self::$__initDone) self::init();

        $query = 'DELETE FROM '.self::$__configTable.' WHERE xar_name = ? ';

        // We want to make the next two statements atomic
        $bindvars = array($name);
        $result = self::$__dbConn->Execute($query,$bindvars);

        $key = self::$__key;
        if (isset(parent::$_iscached[$key][$name])) {
            unset(parent::$_cache[$key][$name], parent::$_iscached[$key][$name]);
        }
        return TRUE;
    }

    /**
     * Pre-load site configuration variables
     *
     *
     * @return boolean true on success, or void on database error
     * @todo We need some way to delete configuration (useless without a certain module) variables from the table!!!
     * @todo look into removing the serialisation, creole does this when needed, automatically (well, almost)
     */
    private static function preload()
    {
        if (!self::$__initDone) self::init();

        $query = 'SELECT xar_name, xar_value FROM '.self::$__configTable;

        //jojo - is it worth checking adodb cache for preload? Use without checking will lead to errors
        // if (xarSystemVars::get(null,'DB.UseADODBCache')) {
        //        $result = self::$__dbconn->CacheExecute(3600*24*7,$query);
        //   } else {
        $result = self::$__dbConn->Execute($query);
        //   }
        $key = self::$__key;
        while (!$result->EOF) {
            list($name,$value) = $result->fields;
            parent::$_cache[$key][$name] = $value;
            parent::$_iscached[$key][$name] = TRUE;
            $result->MoveNext();
        }
        $result->close();

        self::$__bPreloaded = TRUE;
        return TRUE;
    }
}
?>
