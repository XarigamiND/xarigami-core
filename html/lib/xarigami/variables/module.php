<?php
/**
 * Module variable handling
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
/**
 * Build upon IxarVars to define interface for ModVars
 *
 */
sys::import('xarigami.xarVar');
interface IxarModVars extends IxarVars
{
    static function getID     ($scope, $name);
    //static function delete_all($scope);
}

/**
 * Class to model interface to module variables
 *
 */
class xarModVars extends xarVars implements IxarModVars
{
    const KEYMODVAR = 'Mod.Variables';
    const KEYID  = 'Mod.GetVarID';

    private static $__bPreloaded = array(); // Keep track of what module vars (per module) we already had
    private static $__initDone = FALSE;

    private static $__dbConn = NULL;
    private static $__modvarTable = NULL;
    private static $__moduservarTable = NULL;

    protected static $_cachedMod = array();
   // private static $__useDbCache = FALSE;

    public static function init($args = NULL, $whattoload = NULL)
    {
        // @TODO: handle things with multiple connections passed in args
        // We would need to also dissociate the caching to not mix several configs. We can then use eventually the scope for the connection index.
        if (!self::$__initDone || $args !== NULL) {
            // Configuration init needs to be done first
            if (!class_exists('xarDB')) sys::import('xarigami.xarDB');
            $tables = &xarDB::$tables;
            self::$__modvarTable = $tables['module_vars'];
            self::$__moduservarTable = $tables['module_uservars'];
            self::$__dbConn = xarDB::$dbconn;
            self::$__initDone = TRUE;
            self::$_cache[self::KEYID] = array();
            self::$_iscached[self::KEYID] = array();
            self::$_cachedMod = array();
          //  self::$__useDbCache = xarSystemVars::get(null,'DB.UseADODBCache');
        }
    }

    /**
     * Get a module variable
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @param  mixed  $value If a default value should be returned, it can be passed in.
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     */
    static function get($scope, $name, $value=null)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');
        if (empty($name)) throw new EmptyParameterException('name');

        if (!self::$__initDone) self::init();

        $key = self::KEYMODVAR . '.' . $scope;

        // Fastest response
        if (isset(parent::$_iscached[$key][$name])) return parent::$_cache[$key][$name];

        if (!isset(self::$__bPreloaded[$scope])) {
            self::preload($scope);
            if (isset(parent::$_iscached[$key][$name])) return parent::$_cache[$key][$name];
        }

        if (!isset(parent::$_iscached[$key])) {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }

        // Still no luck, let's do the hard work then
        $modBaseInfo = xarMod::getBaseInfo($scope);
        assert(isset($scope));

        $query = 'SELECT xar_id, xar_name, xar_value FROM '.self::$__modvarTable.' WHERE xar_modid = ? AND xar_name = ?';
        $bindvars = array((int)$modBaseInfo['systemid'], $name);

       // if (self::$__useDbCache){
       //       $result = self::$__dbConn->CacheExecute(3600*24*7,$query,$bindvars);
       // } else {
             $result = self::$__dbConn->Execute($query,$bindvars);
       // }
        if (!$result || $result->EOF) return NULL;
        list($id, $name, $value) = $result->fields;
        $result->close();

        parent::$_cache[$key][$name] = $value;
        parent::$_iscached[$key][$name] = TRUE;

        // Store id in cache too
        $key = $modBaseInfo['systemid'] . '.' . $name;
        parent::$_cache[self::KEYID][$key] = (int)$id;
        parent::$_iscached[self::KEYID][$key] = TRUE;

        return $value;
    }

    /**
     * PreLoad all module variables for a particular module
     *
     * @author Michel Dalle
     *
     * @param  string $scope Module name
     * @return boolean true on success
     * @throws EmptyParameterException
     * @todo  This has some duplication with xarVar.php
     */
    private static function preload($scope)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');

        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return FALSE;

        if (!self::$__initDone) self::init();

        $query = 'SELECT xar_id, xar_name, xar_value FROM '.self::$__modvarTable. ' WHERE xar_modid = ?';
        $result = self::$__dbConn->Execute($query,array($modBaseInfo['systemid']));

        if (!$result) return FALSE;

        $key = self::KEYMODVAR . '.' . $scope;

        while (!$result->EOF) {
            list($id, $name, $value) = $result->fields;
            parent::$_cache[$key][$name] = $value;
            parent::$_iscached[$key][$name] = TRUE;

            $keyid = $modBaseInfo['systemid'] . '.' . $name;
            parent::$_cache[self::KEYID][$keyid] = $id;
            parent::$_iscached[self::KEYID][$keyid] = TRUE;

            $result->MoveNext();
        }
        $result->close();

        self::$__bPreloaded[$scope] = TRUE;
        return TRUE;
    }

    /**
     * Set a module variable
     *
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @param  mixed  $value The value of the variable
     * @return boolean true on success
     * @throws EmptyParameterException
     * @todo  We could delete the item vars for the module with the new value to save space?
     */
    static function set($scope, $name, $value)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');
        if (empty($name)) throw new EmptyParameterException('name');
        //assert(!is_null($value), new BadParameterException('value', null, 'Not allowed to set a variable to NULL value'));

        if (!self::$__initDone) self::init();

        $modBaseInfo = xarMod::getBaseInfo($scope);
        $modid = $modBaseInfo['systemid'];

        // We need the variable id
        $modvarid = self::getId($scope, $name);

        if ($value === FALSE) $value = 0;
        if ($value === TRUE) $value = 1;
        if ($modvarid === NULL || $modvarid === FALSE) {
            $seqId = self::$__dbConn->GenId(self::$__modvarTable);
            $query = 'INSERT INTO '.self::$__modvarTable.'
                             (xar_id, xar_modid, xar_name, xar_value)
                          VALUES (?,?,?,?)';
            $bindvars = array($seqId, $modid, $name, (string)$value);
        } else {
            // Existing one
            $query = 'UPDATE '.self::$__modvarTable.' SET xar_value = ? WHERE xar_id = ?';
            $bindvars = array((string)$value,$modvarid);
        }

       //if (self::$__useDbCache){
        //    $result = self::$__dbConn->CacheFlush();
       // }
        if (!empty($query)) {
            $result = self::$__dbConn->Execute($query,$bindvars);
            if (!$result) return FALSE;
            if ($modvarid === NULL) {
                // We wrote a new var, caching genid is not update to date anymore
                $key = $modid . '.' . $name;
                parent::$_cache[self::KEYID][$key] = self::$__dbConn->PO_Insert_ID(self::$__modvarTable, 'xar_id');
                parent::$_iscached[self::KEYID][$key] = TRUE;
            }
        }

        // Update cache for the variable
        $key = self::KEYMODVAR . '.' . $scope;
        parent::$_cache[$key][$name] = $value;
        parent::$_iscached[$key][$name] = TRUE;

        return TRUE;
    }

    /**
     * Delete a module variable
     *
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @return boolean true on success
     * @throws EmptyParameterException
     * @todo Add caching for item variables?
     */
    static function delete($scope, $name)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');

        if (!self::$__initDone) self::init();
        $modBaseInfo = xarMod::getBaseInfo($scope);

        // Delete all the itemvars derived from this var first
        $modvarid = self::getId($scope, $name);

        if($modvarid !== NULL) {
             $query = 'DELETE FROM '.self::$__moduservarTable.' WHERE xar_mvid = ?';
             $result = self::$__dbConn->Execute($query,array((int)$modvarid));
             if(!$result) return NULL;
        }

        // Now delete the modvar itself
        $query = 'DELETE FROM '.self::$__modvarTable.' WHERE xar_modid = ? AND xar_name = ?';
        $bindvars = array((int)$modBaseInfo['systemid'], $name);
        $result = self::$__dbConn->Execute($query, $bindvars);
        if (!$result) return NULL;

        // Removed it from the cache
        $key = self::KEYMODVAR . '.' . $scope;
        if (isset(parent::$_iscached[$key][$name])) {
            unset(parent::$_cache[$key][$name], parent::$_iscached[$key][$name]);
        }

        $key = $modvarid . '.' . $name;
        if (isset(parent::$_iscached[self::KEYID][$key])) {
            unset(parent::$_cache[self::KEYID][$key], parent::$_iscached[self::KEYID][$key]);
        }
        return TRUE;
    }

    /**
     * Delete all module variables
     *
     * @param  string $scope The name of the module
     * @return boolean true on success
     * @throws EmptyParameterException, SQLException
     * @todo Add caching for item variables?
     */
    static function delete_all($scope, $name=NULL, $itemid=NULL)
    {
        if(empty($scope)) throw new EmptyParameterException('modName');

        $modBaseInfo = xarMod::getBaseInfo($scope);

        if (isset($modBaseInfo)) { //only continue if the module info exists

             if (!self::$__initDone) self::init();

            // PostGres (allows only one table in DELETE)
            // MySql: multiple table delete only from 4.0 up
            // Select the id's which need to be removed
            $sql = 'SELECT '.self::$__modvarTable.'.xar_id FROM '.self::$__modvarTable.' WHERE '.self::$__modvarTable.'.xar_modid = ?';
            $result = self::$__dbConn->Execute($sql, array($modBaseInfo['systemid']));
            if (!$result) return NULL;

            // Seems that at least mysql and pgsql support the scalar IN operator
            $idlist = array();
            while (!$result->EOF) {
                list($id) = $result->fields;
                $result->MoveNext();
                $idlist[] = (int) $id;
            }

            $result->close();
            unset($result);

            // We delete the module vars and the user vars in a transaction, which either succeeds completely or totally fails
            try {
                if(count($idlist) != 0 ) {
                    $idlist = join(', ', $idlist);

                    $sql = 'DELETE FROM '.self::$__moduservarTable.' WHERE '.self::$__moduservarTable.'.xar_mvid IN ('.$idlist.')';
                    $result = self::$__dbConn->Execute($sql);
                    if (!$result) return FALSE;
                    $result->Close();

                    $key = 'ModUser.Variables.' . $scope;
                    if (array_key_exists($key, parent::$_iscached)) unset(parent::$_cache[$key], parent::$_iscached[$key]);
                }
                // Now delete the module vars
                $query = 'DELETE FROM '.self::$__modvarTable.' WHERE xar_modid = ?';
                $result = self::$__dbConn->Execute($query, array($modBaseInfo['systemid']));
                if (!$result) return FALSE;

                $key = self::KEYMODVAR . '.' . $scope;
                if (isset(parent::$_iscached[$key])) unset(parent::$_cache[$key], parent::$_iscached[$key]);
                $key = self::KEYID;
                if (isset(parent::$_iscached[$key])) unset(parent::$_cache[$key], parent::$_iscached[$key]);
                //we have to flush them all?
            } catch (Exception $e) {
                throw $e;
            }
        }
        return TRUE;
    }

    /**
     * Support function for xarMod*UserVar functions
     *
     * private function which delivers a module user variable
     * id based on the module name and the variable name
     *
     *
     * @param  string $scope The name of the module
     * @param  string $name  The name of the variable
     * @return integer identifier for the variable
     * @throws EmptyParameterException
     * @see xarModUserVars::set(), xarModUserVars::get(), xarModUserVars::delete()
     */
    static function getId($scope, $name='')
    {
        if (!self::$__initDone) self::init();

        $cachedMod = &self::$_cachedMod;
        // Module name and variable name are both necesary
        if (empty($scope)) throw new EmptyParameterException('modName');

        // Retrieve module info, so we can decide where to look
        $modBaseInfo = xarMod::getBaseInfo($scope);
        if (!isset($modBaseInfo)) return FALSE; // throw back

        $modid = (int)$modBaseInfo['systemid'];
        $kname = $modid . '.' . $name;

        if (isset(self::$_iscached[self::KEYID][$kname])) {
            return parent::$_cache[self::KEYID][$kname];
        }

        if (!isset($cachedMod[$modid])) {
            $query = 'SELECT xar_name, xar_id FROM '.self::$__modvarTable.' WHERE xar_modid = ?';
            $result = self::$__dbConn->Execute($query, array((int)$modBaseInfo['systemid']));

            if (!$result || $result->EOF) return NULL;

            while (!$result->EOF) {
                list($name, $modvarid) = $result->fields;
                $key = $modid . '.' . $name;
                parent::$_cache[self::KEYID][$key] = (int)$modvarid;
                parent::$_iscached[self::KEYID][$key] = TRUE;
                $result->MoveNext();
            }

            $result->Close();
            $cachedMod[$modid] = TRUE;
        }

        if (!isset(parent::$_iscached[self::KEYID][$kname])) return NULL;

        return parent::$_cache[self::KEYID][$kname];
    }
}
?>
