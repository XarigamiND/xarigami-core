<?php
/**
 * Mod User variable handling
 *
 * @package core
 * @subpackage variables
  * @copyright (C) 2007-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
 * @note there is no caching anymore as it was causing too many issues.
 */
/**
 * Interface declaration for module user vars
 *
 */
sys::import('xarigami.xarVar');
sys::import('xarigami.variables.module');

interface IxarModUserVars
{
    static function get       ($scope, $name);
    static function set       ($scope, $name, $value, $itemid= NULL);
    static function delete    ($scope, $name);
    static function delete_all($scope, $name, $itemid= NULL);
}

/**
 * Class to implement the interface to module user vars
 *
 * @todo decide on sessionvars for anonymous users
 * @todo when yes on the previous todo, remember promotion of the vars
 */
class xarModUserVars extends xarModVars implements IxarModUserVars
{
    const KEYMODUSERVAR = 'ModUserVars';
    const ROLESMODNAME = 'roles';
    public static $duvns = array('userhome','primaryparent','passwordupdate','userlastlogin','userlastvisit','usertimezone');

    private static $__initDone = FALSE;

    private static $__dbConn = NULL;
    private static $__moduleUserVarsTable = NULL;
    private static $__moduleTable = NULL;
    private static $__preload = array();
   // private static $__useDbCache = FALSE;


    public static function init($args = NULL, $whattoload = NULL)
    {
        // @TODO: handle things with multiple connections passed in args
        // We would need to also dissociate the caching to not mix several configs. We can then use eventually the scope for the connection index.
        if (!self::$__initDone || $args !== NULL) {
            // Configuration init needs to be done first
            if (!class_exists('xarDB')) sys::import('xarigami.xarDB');
            $tables = &xarDB::$tables;
            self::$__moduleUserVarsTable = $tables['module_uservars'];
            self::$__moduleTable = $tables['modules'];
            self::$__dbConn = xarDB::$dbconn;
            self::$__initDone = TRUE;
            self::reset();
           // self::$__useDbCache = xarSystemVars::get(null,'DB.UseADODBCache');
        }
    }

    /**
     * Get a user variable for a module
     *
     * This is basically the same as xarModVars::set(), but this
     * allows for getting variable values which are tied to
     * a specific item for a certain module. Typical usage
     * is storing user preferences.
     *
     *
     * @param  string  $scope   The name of the theme
     * @param  string  $name    The name of the variable to get
     * @param  integer $itemid  User uid for which value is to be retrieved
     * @return mixed The value of the variable or void if variable doesn't exist.
     * @see  xarModUserVars::get()
     */
    static function get($scope, $name, $itemid = NULL)
    {
        // If id not specified take the current user
        if ($itemid == NULL) $itemid = xarUserGetVar('uid');

        // Anonymous user always uses the module default setting
        if ($itemid == _XAR_ID_UNREGISTERED) return parent::get($scope, $name);

        if (!self::$__initDone) self::init();


        $bindvars = array();
        $modvarid = parent::getId($scope, $name);
        if (!$modvarid) return NULL;

        $key = "$modvarid.$itemid";
        if (isset(self::$_iscached[self::KEYMODUSERVAR][$key])) {
            return self::$_cache[self::KEYMODUSERVAR][$key];
        }

        if (!isset(self::$__preload[$itemid])) {
            self::preload($itemid);
            if (isset(self::$_iscached[self::KEYMODUSERVAR][$key])) {
                return self::$_cache[self::KEYMODUSERVAR][$key];
            }
        }

        $query = 'SELECT xar_value FROM ' . self::$__moduleUserVarsTable .
                ' WHERE xar_mvid = ? AND xar_uid = ?';
        $bindvars = array((int)$modvarid, (int)$itemid);
        $result = self::$__dbConn->Execute($query,$bindvars);
        if (!$result) return NULL;

        if ($result->EOF) {
            $result->Close();
            // return global setting
             //jojo - this should not be the case?
            //for bool vars this is a problem.
            return parent::get($scope, $name);
        }
        list($value) = $result->fields;
        $result->Close();

        // cache the result
        self::$_cache[self::KEYMODUSERVAR][$key] = $value;
        self::$_iscached[self::KEYMODUSERVAR][$key] = TRUE;

        return $value;
    }

    /**
     * Return all dynamic vars for a given user
     * @param interger $uid
     * @return array(varname => value,...)
     */
    static function getDynVars($itemid)
    {
        static $rolesModId = -1;
        if (!isset(self::$__preload[$itemid])) self::preload($itemid);

        if ($rolesModId === -1) {
            $modBaseInfo = xarMod::getBaseInfo(self::ROLESMODNAME);
            $rolesModId = (int)$modBaseInfo['systemid'];
        }

        // Quick test to determine whether modvar IDs have to be preloaded for the roles module.
        if (isset(parent::$_cachedMod[$rolesModId])) parent::getId(self::ROLESMODNAME);

        $duvs = array();

        foreach (self::$duvns as $duvkey) {
            $keyid = $rolesModId . '.' . $duvkey;
            if (isset(self::$_iscached[self::KEYID][$keyid])) {
                $modvarid = self::$_cache[self::KEYID][$keyid];
                $key = "$modvarid.$itemid";
                if (isset(self::$_iscached[self::KEYMODUSERVAR][$key])) {
                    $duvs[$duvkey] = self::$_cache[self::KEYMODUSERVAR][$key];
                } else {
                    $duvs[$duvkey] = NULL;
                }
            } else {
                // @TODO: Lakys - study what should be the default if a DUV is missing in DB? Maybe better to not create the element?
                $duvs[$duvkey] = NULL;
            }
        }
        return $duvs;
    }

    /**
     * Set a user variable for a module
     *
     * This allows for setting variable values which are tied to
     * a specific user for a certain module. Typical usage
     * is storing user preferences.
     * Only deviations from the module vars are stored.
     *
     *
     * @param  string  $scope   The name of the module to set a user variable for
     * @param  string  $name    The name of the variable to set
     * @param  mixed   $value   Value to set the variable to.
     * @param  integer $itemid  User uid for which value needs to be set
     * @return boolean true on success false on failure
     * @throws EmptyParameterException
     * @see xarModVars::set()
     * @todo Add caching?
     */
    static function set($scope, $name, $value, $itemid = NULL)
    {
        // If no uid specified assume current user
        if ($itemid === NULL) $itemid = xarUserGetVar('uid');

        // For anonymous users no preference can be set
        // MrB: should we raise an exception here?
        if ($itemid == _XAR_ID_UNREGISTERED) return FALSE;

        $modBaseInfo = xarMod::getBaseInfo($scope);

        if (!isset($modBaseInfo)) return FALSE; // throw back
        if (!self::$__initDone) self::init();

        // Get the default setting to compare the value against.
        $modsetting = xarModVars::get($scope, $name);

        // We need the variable id
        unset($modvarid);
        $modvarid = xarModVars::getId($scope, $name);

        if(!$modvarid) return FALSE;
         // First delete it.
        xarModUserVars::delete($scope,$name,$itemid);

        // Only store setting if different from global setting
        if ($value != $modsetting) {
            $query = 'INSERT INTO ' . self::$__moduleUserVarsTable .
                       ' (xar_mvid, xar_uid, xar_value)
                      VALUES (?,?,?)';
            $bindvars = array($modvarid,$itemid,$value);
        }


        if (!empty($query)){
            $result = self::$__dbConn->Execute($query,$bindvars);
            if (!$result) return FALSE;
        }

        $key = "$modvarid.$name.$itemid";
        self::$_cache[self::KEYMODUSERVAR][$key] = $value;
        self::$_iscached[self::KEYMODUSERVAR][$key] = TRUE;
        return TRUE;
    }

    /**
     * Delete a user variable for a module
     *
     * This is the same as xarModVars::delete() but this allows
     * for deleting a specific user variable, effectively
     * setting the value for that user to the default setting
     *
     *
     * @param  string  $scope The name of the module to set a variable for
     * @param  string  $name  The name of the variable to set
     * @param  integer $itemid User id of the user to delete the variable for.
     * @return boolean true on success
     * @see xarModVars::delete()
     * @todo Add caching?
     */
    static function delete($scope, $name, $itemid = NULL)
    {
        // If id is not set assume current user
        if ($itemid === NULL) $itemid = xarUserGetVar('uid');

        // Deleting for anonymous user is useless return true

        if ($itemid == _XAR_ID_UNREGISTERED ) return TRUE;
        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return FALSE; // throw back
        if (!self::$__initDone) self::init();

        // We need the variable id
        $modvarid = xarModVars::getId($scope, $name);
        if (!$modvarid) return FALSE;

        $query = 'DELETE FROM ' . self::$__moduleUserVarsTable . ' WHERE xar_mvid = ? AND xar_uid = ?';
        $bindvars = array((int)$modvarid, (int)$itemid);
        $result = self::$__dbConn->Execute($query, $bindvars);
        if (!$result) return FALSE;

        $cachename = $itemid . $name;

        // Suppress from cache
        $key = "$modvarid.$itemid";
        if (array_key_exists($key, self::$_cache[self::KEYMODUSERVAR])) {
            unset(self::$_cache[self::KEYMODUSERVAR][$key], self::$_iscached[self::KEYMODUSERVAR][$key]);
        }
        return TRUE;
    }

    static function reset()
    {
        self::$_cache[self::KEYMODUSERVAR] = array();
        self::$_iscached[self::KEYMODUSERVAR] = array();
        self::$__preload = array();
    }

    /**
     * Delete all user module variables for
     *  1. A given variable for all users
     *  2. All uservariables for a given user
     * @param  string $scope The name of the module
     * @param  ing $itemid The uid of the user
     * @return boolean true on success
     * @throws EmptyParameterException, Exception
     * @todo Add caching for item variables?
     */
    static function delete_all($scope, $name=NULL, $itemid=NULL)
    {
        if(empty($scope)) throw new EmptyParameterException('modName');

        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return FALSE;

        if (!self::$__initDone) self::init();

        // We need the variable id
        $modvarid = xarModVars::getId($scope, $name);
        if(!$modvarid) return FALSE;

        // Suppress cache
        self::reset();

        //if $itemid null then we want to delete all mod user vars for that specific variable name  mvid
        if ($itemid === NULL) {
            $query = 'DELETE FROM ' . self::$__moduleUserVarsTable .' WHERE xar_mvid = ?';
            $bindvars = array((int)$modvarid);
        } else {
        // if itemid is not null we want to delete all users variables for a given user
            $query = 'DELETE FROM ' . self::$__moduleUserVarsTable .' WHERE xar_uid = ?';
            $bindvars = array((int)$itemid);
        }
        $result = self::$__dbConn->Execute($query, $bindvars);
        if (!$result) return FALSE;
        return TRUE;
    }

    /**
     * Preload all the module user var for a given user
     * @param  int $itemid The uid of the user
     */
    static function preload($itemid)
    {
        if (empty($itemid)) throw new EmptyParameterException('itemid');
        if (!self::$__initDone) self::init();

        $query = 'SELECT xar_mvid, xar_value '.
                 'FROM '.self::$__moduleUserVarsTable.' '.
                 'WHERE xar_uid = ?';
        $bindvars = array((int)$itemid);
        $result = self::$__dbConn->Execute($query,$bindvars);
         while (!$result->EOF) {
            list($modvarid, $value) = $result->fields;
            $key = "$modvarid.$itemid";
            parent::$_cache[self::KEYMODUSERVAR][$key] = $value;
            parent::$_iscached[self::KEYMODUSERVAR][$key] = TRUE;
            $result->MoveNext();
        }
        $result->close();
        self::$__preload[$itemid] = TRUE;
    }
}
?>
