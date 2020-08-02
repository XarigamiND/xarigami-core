<?php
/**
 * Module handling subsystem
 *
 * @package Xarigami core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @ xarigami Modules subsystem
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The base info for module "#(1)" could not be found.');
        else
            return $this->xarML('The base info for a module could not be found.');
    }
}

class ModuleNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        return $this->xarML('A module is missing, the module name could not be determined in the current context.');
    }
}

class ModuleNotActiveException extends xarExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The module "#(1)" was called, but it is not active.');
        else
            return $this->xarML('A module was called, but it is not active.');
    }
}

/**
 * State of modules
 */
define('XARMOD_STATE_UNINITIALISED', 1);
define('XARMOD_STATE_INACTIVE', 2);
define('XARMOD_STATE_ACTIVE', 3);
define('XARMOD_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARMOD_STATE_UPGRADED', 5);
// This isn't a module state, but only a convenient definition to indicates,
// where it's used, that we don't care about state, any state is good
define('XARMOD_STATE_ANY', 0);
// <andyv> added an extra superficial state, need it for the list filter because
// some ppl requested not to see modules which are not initialised FR/BUG #252
// now we define 'Installed' state as all except 'uninitialised'
// in fact we dont even need a record as it's an exact reverse of state 1
// tell me if there is something wrong with my (twisted) logic ;-)
define('XARMOD_STATE_INSTALLED', 6);
define('XARMOD_STATE_MISSING_FROM_INACTIVE', 7);
define('XARMOD_STATE_MISSING_FROM_ACTIVE', 8);
define('XARMOD_STATE_MISSING_FROM_UPGRADED', 9);
// Bug 1664 - Add  module states for modules that have a db version
// that is greater than the file version
define('XARMOD_STATE_ERROR_UNINITIALISED', 10);
define('XARMOD_STATE_ERROR_INACTIVE', 11);
define('XARMOD_STATE_ERROR_ACTIVE', 12);
define('XARMOD_STATE_ERROR_UPGRADED', 13);
// Module states for modules which have a core requirement
// that is incompatible with current core version (added in 1.2.0)
define('XARMOD_STATE_CORE_ERROR_UNINITIALISED', 14);
define('XARMOD_STATE_CORE_ERROR_INACTIVE', 15);
define('XARMOD_STATE_CORE_ERROR_ACTIVE', 16);
define('XARMOD_STATE_CORE_ERROR_UPGRADED', 17);

/**
 * Define the theme here for now as well
 *
 */
define('XARTHEME_STATE_UNINITIALISED', 1);
define('XARTHEME_STATE_INACTIVE', 2);
define('XARTHEME_STATE_ACTIVE', 3);
define('XARTHEME_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARTHEME_STATE_UPGRADED', 5);
define('XARTHEME_STATE_ANY', 0);
define('XARTHEME_STATE_INSTALLED', 6);
define('XARTHEME_STATE_MISSING_FROM_INACTIVE', 7);
define('XARTHEME_STATE_MISSING_FROM_ACTIVE', 8);
define('XARTHEME_STATE_MISSING_FROM_UPGRADED', 9);
// Bug 1664 - As for modules , add states for themes that have a db version
// that is greater than the file version
define('XARTHEME_STATE_ERROR_UNINITIALISED', 10);
define('XARTHEME_STATE_ERROR_INACTIVE', 11);
define('XARTHEME_STATE_ERROR_ACTIVE', 12);
define('XARTHEME_STATE_ERROR_UPGRADED', 13);
/**
 * Flags for loading APIs
 */
define('XARMOD_LOAD_ONLYACTIVE', 1);
define('XARMOD_LOAD_ANYSTATE', 2);

// jojo - retain for now
define('XARMOD_MODE_SHARED', 1);
define('XARMOD_MODE_PER_SITE', 2);
define('XARTHEME_MODE_SHARED', 1);
define('XARTHEME_MODE_PER_SITE', 2);


/*
    Bring in the module variables to maintain interface compatibility for now
*/
sys::import('xarigami.variables.module');
sys::import('xarigami.variables.moduser');

/*
 * Wrapper functions to support Xaraya 1 API for modvars and moduservars
*/
function xarModGetVar($modName, $name, $prep = NULL){ return xarModVars::get($modName, $name); }
function xarModSetVar($modName, $name,$value)       { return xarModVars::set($modName, $name, $value); }
function xarModDelVar($modName, $name)              { return xarModVars::delete($modName, $name); }
function xarModDelAllVars($modName)                 { return xarModVars::delete_all($modName);}
function xarModGetVarId($modName, $name)            { return xarModVars::getId($modName, $name);  }
function xarModDelUserVar($modName, $name, $uid=NULL) { return xarModUserVars::delete($modName, $name, $uid);}
function xarModSetUserVar($modName, $name, $value, $uid=NULL) { return xarModUserVars::set($modName, $name, $value, $uid);  }
function xarModGetUserVar($modName, $name, $uid = NULL)       { return xarModUserVars::get($modName, $name, $uid); }
function xarModGetVarsByName($varName, $type = 'module')      { return xarMod::getVarsByName($varName, $type);}
 function xarMod_getVarsByModule($modName, $type = 'module')  { return  xarMod::getVarsByModule($modName, $type);}
function xarMod__URLencode($data, $type = 'getname')          { return xarUrl::encode($data, $type);}
function xarMod__URLnested($args, $prefix)                    { return xarUrl::nested($args, $prefix);}
function xarMod__URLaddParametersToPath($args, $path, $pini, $psep)
{    return xarUrl::addParametersToPath($args, $path, $pini, $psep);}
function xarModURL($modName=NULL, $modType='user', $funcName='main', $args=array(), $generateXmlUrl=NULL, $fragment=NULL, $entrypoint=array())
{    return xarUrl::url($modName, $modType, $funcName, $args, $generateXmlUrl, $fragment, $entrypoint);}
// (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity
/**
 * Wrapper functions to support Xaraya 1 API for module managment
 *
 */
function xarModGetIDFromName($modName, $type = 'module', $system = FALSE)
    { return xarMod::getId($modName, $type, $system); }
function xarModGetNameFromDir($osDir)
    { return xarMod::getNameFromDir($osDir); }
function xarModGetSystemIDFromName($modName, $type = 'module')
    { return xarMod::getId($modName, $type, TRUE); }
function xarModGetNameFromID($regid, $type='module', $system=FALSE)
    { return xarMod::getName($regid,$type, $system); }
function xarModGetName()
    { return xarMod::getName(); }
function xarModGetDirFromName($modName, $type = 'module')
    { return xarMod::getDirFromName($modName, $type); }
function xarModPrivateLoad($modName, $modType, $flags = 0, $throwException=1)
    { return xarMod::privateLoad($modName, $modType, $flags, $throwException);}
function xarModGetDisplayableName($modName = NULL, $type = 'module')
    { return xarMod::getDisplayableName($modName, $type); }
function xarModGetDisplayableDescription($modName = NULL, $type = 'module')
    { return xarMod::getDisplayableDescription($modName,$type); }
function xarModGetInfo($modRegId, $type = 'module', $system=FALSE)
    { return xarMod::getInfo($modRegId, $type, $system); }
function xarMod_getBaseInfo($modName, $type = 'module')
    { return xarMod::getBaseInfo($modName, $type); }
function xarMod_getFileInfo($modOsDir, $type = 'module')
    { return xarMod::getFileInfo($modOsDir, $type); }
function xarMod__loadDbInfo($modName, $modDir)
    { return xarMod::loadDbInfo($modName, $modDir); }
function xarModDBInfoLoad($modName, $modDir = NULL, $type = 'module')
    { return xarMod::loadDbInfo($modName, $modDir, $type); }
function xarMod_getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
    { return xarMod::getState($modRegId, $modMode, $type); }
function xarModIsAvailable($modName, $type = 'module')
    { return xarMod::isAvailable($modName, $type); }
function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    { return xarMod::guiFunc($modName, $modType, $funcName, $args); }
function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array(), $throwException = 1)
    { return xarMod::apiFunc($modName, $modType, $funcName, $args, $throwException); }
function xarModLoad($modName, $modType = 'user') {   return xarMod::load($modName, $modType); }
function xarModAPILoad($modName, $modType = 'user') {   return xarMod::apiLoad($modName, $modType); }
function xarModRegisterHook($hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName)
    { return xarMod::registerHook($hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName);}
function xarModUnregisterHook($hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName)
    { return xarMod::unregisterHook($hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName);}
function xarModIsHooked($hookModName, $callerModName = NULL, $callerItemType = '')
    { return xarMod::isHooked($hookModName, $callerModName, $callerItemType);}
function xarModGetHookList($callerModName, $hookObject, $hookAction, $callerItemType = '')
    { return xarMod::getHookList($callerModName, $hookObject, $hookAction, $callerItemType);}
function xarModCallHooks($hookObject, $hookAction, $hookId, $extraInfo, $callerModName = NULL, $callerItemType = '')
    { return xarMod::callHooks($hookObject, $hookAction, $hookId, $extraInfo, $callerModName, $callerItemType);}
function xarModGetAlias($var)  { return xarMod::getAlias($var);}
function xarModSetAlias($alias, $modName) { return xarMod::setAlias($alias, $modName);}
function xarModDelAlias($alias, $modName) { return xarMod::delAlias($alias, $modName);}
/**
 * Interface declaration for xarMod
 * @todo this is very likely to change
 */
interface IxarMod
{

}

/**
 * Preliminary class to model xarMod interface
 *
 */
class xarMod extends xarObject implements IxarMod
{
    static $genShortUrls = FALSE;
    static $genXmlUrls   = TRUE;
    static $MLSEnabled = TRUE;

    /**
     * Initialize
     *
     */
     static function init(Array $args=array())
    {
        self::$genShortUrls = $args['enableShortURLsSupport'];
        self::$genXmlUrls   = $args['generateXMLURLs'];
        self::$MLSEnabled = xarConfigGetVar('Site.MLS.Enabled');
        // Register the events for this subsystem
        xarEvents::register('ModLoad');
        xarEvents::register('ModAPILoad');

        // Modules Support Tables
        $prefix = xarDB::$prefix;

        // How we want it
        $tables['modules']         = $prefix . '_modules';
        $tables['module_vars']     = $prefix . '_module_vars';
        $tables['module_uservars'] = $prefix . '_module_uservars';
        $tables['hooks']           = $prefix . '_hooks';
        $tables['themes']          = $prefix . '_themes';
        $tables['theme_vars']     = $prefix . '_theme_vars';

        xarDB::importTables($tables);
        return TRUE;
    }
    static function getName($regid=NULL, $type='module', $system=FALSE)
    {
         $msg = xarML('No valid module found');
        if(!isset($regid)) {
             list($modName) = xarRequest::getInfo();
        } else {
            $modinfo = self::getInfo($regid,$type,$system);
            $modName =  $modinfo['name'];
              $msg = xarML('No valid module found for ');
        }
        if (!isset($modName))
        {
            throw new BadParameterException(NULL,$msg);
        }
        return $modName;
    }
    /**
     * Get module registry ID by name
     *
     * @access public
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     */
    static function getRegId($modName, $type = 'module')
    {

        return self::getId($modName,$type);
    }
    /**
     * Get module registry ID by name
     *
     * @access public
     * @param modName string The name of the module
     * @param type determines theme or module
     * @param system determines regid if FALSE (default for compat) or systemid if TRUE
     * @return string The module id either regid or systemid
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
     */
    static function getId($modName, $type = 'module', $system=FALSE)
    {
        if (empty($modName)) {
            $msg = xarML('Module or Theme Name #(1) is empty.', '$modName');
             throw new EmptyParameterException($msg);
        }

        switch($type) {
            case 'module':
                default:
                $modBaseInfo = self::getBaseInfo($modName);
                break;
            case 'theme':
                $modBaseInfo = self::getBaseInfo($modName, 'theme');
                break;
        }

        if (!isset($modBaseInfo)) return; // throw back
        if ($system == TRUE) {
            $modid =  $modBaseInfo['systemid'];
        }else{
            $modid =  $modBaseInfo['regid'];
        }
        return $modid;
    }


    /**
     * Get module or theme directory by name
     *
     * @access public
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
     */
    static function getDirFromName($modName, $type = 'module')
    {
        if (empty($modName)) {
            $msg = xarML('Module or Theme Name #(1) is empty.', '$modName');
             throw new EmptyParameterException($msg);
        }

        switch($type) {
            case 'module':
                default:
                $modBaseInfo = self::getBaseInfo($modName);
                break;
            case 'theme':
                $modBaseInfo = self::getBaseInfo($modName, 'theme');
                break;
        }

        if (!isset($modBaseInfo)) return; // throw back

        return $modBaseInfo['directory'];
    }
    /**
     * Get information on a module
     *
     * @access public
     * @param modRegId string module id
     * @param type determines theme or module
     * @param system indicates use of regid if FALSE (default for compat) or systemid if TRUE
     * @return array of module information
     * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
     */
    static function getInfo($modRegId, $type = 'module', $system=FALSE)
    {
        if (empty($modRegId) || $modRegId == 0) {
            $msg = xarML('Empty RegId (#(1)) or RegId is equal to 0.', $modRegId);
            throw new IDNotFoundException($msg);
        }
        if (!$system) {
            switch($type) {
                case 'module':
                    default:
                    if (xarCoreCache::isCached('Mod.Infos', $modRegId)) {
                        return xarCoreCache::getCached('Mod.Infos', $modRegId);
                    }
                    break;
                case 'theme':
                    if (xarCoreCache::isCached('Theme.Infos', $modRegId)) {
                        return xarCoreCache::getCached('Theme.Infos', $modRegId);
                    }
                    break;
            }
            xarLogMessage("xarMod::getInfo ". $modRegId ." / " . $type);
            $where = 'WHERE xar_regid = ?';
        } else {
            $where = 'WHERE xar_id = ?';
        }

        $dbconn = xarDB::$dbconn;
        $tables = &xarDB::$tables;

        switch($type) {
            case 'module':
            default:
                $the_table = $tables['modules'];
                $query = "SELECT xar_id,
                                xar_name,
                                xar_regid,
                                xar_directory,
                                xar_mode,
                                xar_version,
                                xar_class,
                                xar_category,
                                xar_admin_capable,
                                xar_user_capable,
                                xar_state
                          FROM $the_table $where";
                break;
            case 'theme':
                $the_table = $tables['themes'];
                $query = "SELECT
                                xar_id,
                                xar_name,
                                xar_regid,
                                xar_directory,
                                xar_mode,
                                xar_version,
                                xar_class,
                                xar_state
                           FROM $the_table $where";
                break;
        }
        $result = $dbconn->Execute($query,array($modRegId));
        if (!$result) return;

        if ($result->EOF) {
            $result->Close();
            throw new IDNotFoundException($modRegId);
        }

        switch($type) {
            case 'module':
            default:
                list($modInfo['systemid'],
                     $modInfo['name'],
                     $modInfo['regid'],
                     $modInfo['directory'],
                     $mode,
                     $modInfo['version'],
                     $modInfo['class'],
                     $modInfo['category'],
                     $modInfo['admincapable'],
                     $modInfo['usercapable'],
                     $modInfo['state']) = $result->fields;
                break;
            case 'theme':
                list($modInfo['systemid'],
                     $modInfo['name'],
                     $modInfo['regid'],
                     $modInfo['directory'],
                     $mode,
                     $modInfo['version'],
                     $modInfo['class'],
                     $modInfo['state']) = $result->fields;
                break;
        }
        $result->Close();

        //jojo - supplement with the module file info not kept in the database
        // Grab the info once instead of using the xarModGetDisplayable--- functions
        $modfileinfo = self::getFileInfo($modInfo['name'],$type);
        $modBaseInfo['displayname'] = $modfileinfo['displayname'];
        $modBaseInfo['description'] = $modfileinfo['description'];

        // Shortcut for os prepared directory
        $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

        switch($type) {
            case 'module':
                default:
                if (!isset($modInfo['state'] )) $modInfo['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
                $modFileInfo = self::getFileInfo($modInfo['osdirectory']);
                break;
            case 'theme':
                if (!isset( $modInfo['state']))  $modInfo['state'] = XARTHEME_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
                $modFileInfo = self::getFileInfo($modInfo['osdirectory'], $type = 'theme');
                break;
        }

        if (!isset($modFileInfo)) {
            // We couldn't get file info, fill in unknowns.
            // The exception for this is logged in getFileInfo
            //jojo - clean up these vars - make them consistent with xarversion.php  and xartheme.php too
            $modFileInfo['class'] = xarML('Unknown');
            $modFileInfo['description'] = xarML('This module is not installed properly. Not all info could be retrieved');
            $modFileInfo['category'] = xarML('Unknown');
            $modFileInfo['displayname'] = '';
            $modFileInfo['directory'] = xarML('Unknown');
            $modFileInfo['admin'] = xarML('Unknown');
            $modFileInfo['user'] = xarML('Unknown');
            $modFileInfo['dependency'] = array(); //deprecated
            $modFileInfo['dependencyinfo'] = array();
            $modFileInfo['extensions'] = array();
            $modFileInfo['xar_version'] = xarML('Unknown');
            $modFileInfo['bl_version'] = xarML('Unknown');
            $modFileInfo['author'] = xarML('Unknown');
            $modFileInfo['contact'] = xarML('Unknown');
            $modFileInfo['credits'] = xarML('Unknown');
            $modFileInfo['help'] = xarML('Unknown');
            $modFileInfo['changelog'] = xarML('Unknown');
            $modFileInfo['license'] = xarML('Unknown');
        }

        $modInfo = array_merge($modFileInfo, $modInfo);

        switch($type) {
            case 'module':
                default:
                xarCoreCache::setCached('Mod.Infos', $modInfo['regid'], $modInfo);
                break;
            case 'theme':
                xarCoreCache::setCached('Theme.Infos', $modInfo['regid'], $modInfo);
                break;
        }
        return $modInfo;
    }

    function getNameFromDir($osDir)
    {
        $modinfo = self::getFileInfo($osDir);
        return $modinfo['name'];
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @access private
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @param flags number - flags to modify function behaviour
     * @return mixed
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
     */
    private static function privateLoad($modName, $modType, $flags = 0, $throwException=1)
    {
        static $loadedModuleCache = array();
        if (empty($modName)) {
            return xarResponse::NotFound();
        }
        // Make sure we access the cache with lower case key, return TRUE when we already loaded
        $cacheKey = strtolower($modName.$modType);
        // Make sure we access the cache with lower case key
        // Q: Why to repeat a functionality already present in the PHP functions?
        // A: because the function may be missing, and we only want to try once.
        if (isset($loadedModuleCache[$cacheKey])) {
            // Already loaded (or tried to) from somewhere else.
            return TRUE;
        }

        xarLogMessage("xarModLoad: loading $modName:$modType");

        $modBaseInfo = self::getBaseInfo($modName);
        if (!isset($modBaseInfo)) {
            return xarResponse::NotFound();
        }

        if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE && !($flags & XARMOD_LOAD_ANYSTATE) ) {
            return xarResponse::NotFound();
        }

        // Load the module files
        $modDir = $modBaseInfo['directory'];
        $fileName = sys::codeAbs().'modules/'.$modDir.'/xar'.$modType.'.php';
        //jojo - hard coded for now. TODO Look at fall back dependent on http_server and script file location.
        // also see xarMod::callFunc
        $altName = sys::sites().'global/modules/'.$modDir.'/xar'.$modType.'.php';
        // Removed the exception.  Causing some wierd results with modules without an api.
        // <nuncanada> But now we wont know if something was loaded or not!
        // <nuncanada> We need some way to find it out.
        if (file_exists($altName)) {
            include_once "$altName";
            sys::hasImported('modules.'.$modDir.'.xar'.$modType); // To prevent sys::import to eventually load the original version
            // @TODO (from Lakys): see whether we should or should not have some override methods in sys::import itself

            // Make sure we access the case with lower case key
            $loadedModuleCache[$cacheKey] = TRUE;
        }elseif (file_exists($fileName)) {
            sys::import('modules.'.$modDir.'.xar'.$modType);
            // Make sure we access the case with lower case key
            $loadedModuleCache[$cacheKey] = TRUE;

        } elseif (is_dir(sys::code().'modules/'.$modDir.'/xar'.$modType)) {
            // this is OK too - do nothing
            $loadedModuleCache[$cacheKey] = TRUE;
        } else {
            // this is not OK - we don't have this -> set cache to FALSE

            // Make sure we access the case with lower case key
            $loadedModuleCache[$cacheKey] = FALSE;
        }

        // Load the module translations files (common functions, uncut functions etc.)
        if(file_exists($fileName) &&  self::$MLSEnabled) {
            if(!xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:', $modType)) return;
        }

        // Load database info
        self::loadDbInfo($modBaseInfo['name'], $modDir);

        // Module loaded successfully, trigger the proper event
        xarEvents::trigger('ModLoad', $modBaseInfo['name']);

        return TRUE;
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @access public
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @return mixed
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function load($modName, $modType = 'user')
    {
        return self::privateLoad($modName, $modType);
    }

    /**
     * Load the modType API for module identified by modName.
     *
     * @access public
     * @param modName string registered name of the module
     * @param modType string type of functions to load
     * @return mixed TRUE on success
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function apiLoad($modName, $modType = 'user', $throwException = 1)
    {
        return self::privateLoad($modName, $modType.'api', XARMOD_LOAD_ANYSTATE, $throwException);
    }

   /**
     * Call a module GUI function.
     *
     * @access public
     * @param modName string registered name of module
     * @param modType string type of function to run
     * @param funcName string specific function to run
     * @param args array
     * @return mixed The output of the function, or raise an exception
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    private static function callFunc($modName, $modType, $funcName, $args, $funcType = '', $throwException=1)
    {
        if (empty($modName)) {
             return xarResponse::NotFound();
        }
        if (empty($funcName)) {
            throw new EmptyParameterExceptions('funcName');
        }

        // good thing this information is cached :)
        $modBaseInfo = self::getBaseInfo($modName);
        if (!isset($modBaseInfo)) {
             return xarResponse::NotFound();
        }
        // Build function name and call function
        $modFunc = "{$modName}_{$modType}{$funcType}_{$funcName}";
        $found = TRUE;
        $isLoaded = TRUE;
        $altfile = FALSE;
        //jojo - hard coded for now. TODO Look at fall back dependent on http_server and script file location.
        $altCode = sys::sites().'/global/';
        $msg='';
        if (!function_exists($modFunc)) {
            if ($funcType == 'api') {
                self::apiLoad($modName, $modType,$throwException);
            } else {
                self::load($modName,$modType);
            }
            // let's check for that function again to be sure
            if (!function_exists($modFunc)) {
                 $funcFile = sys::codeAbs() . 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.$funcType.'/'.strtolower($funcName).'.php';
                 $altFile = $altCode . 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.$funcType.'/'.strtolower($funcName).'.php';
                if (file_exists($altFile)) $altfile = TRUE;
                if (!file_exists($funcFile) && !file_exists($altFile)) {
                    if ($funcType == 'api') {
                        $found = FALSE;
                    } else {
                          return xarResponse::NotFound();
                    }
                } else {

                     ob_start();
                    if ($altfile === TRUE) {
                        $r = include_once($altCode . 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.$funcType.'/'.strtolower($funcName).'.php');
                        sys::hasImported(('modules.'.$modName.'.xar'.$modType.$funcType.'.'.strtolower($funcName))); // Prevent any call from sys::import
                    } else {
                        $r =   sys::import('modules.'.$modName.'.xar'.$modType.$funcType.'.'.strtolower($funcName));
                    }

                    $error_msg = strip_tags(ob_get_contents());
                    ob_end_clean();

                    if (empty($r) || !$r) {
                        $msg = xarML("Could not load function file: [#(1)].", $funcFile) . "\n\n";
                        $msg .= xarML("Error Caught:") . "\n";
                        $msg .= $error_msg;
                        $isLoaded = FALSE;
                    }

                    if (!function_exists($modFunc)) {
                        $found = FALSE;
                    }
                }

            }

            if ($found &&  self::$MLSEnabled) {
                // Load the translations file - only if we have successfuly loaded the module function and MLS system is active
                //if(!xarMLSLoadTranslations('modules/'.$modBaseInfo['name']."/xar${modType}/${funcName}.php")) return;
                 if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType.$funcType, $funcName) === NULL) {return;}
            }
        }

        if (!$found) {
            if ($throwException) {
                if (!$isLoaded || empty($msg)) {
                    $msgfunc = $modName.'_'.$modType.'_'.$funcName;
                    $msg = xarML('Module API function #(1) does not exist or could not be loaded.', $msgfunc);
                }
                return xarResponse::NotFound();
            }
            return;
        }
        $funcResult = $modFunc($args);

        return $funcResult;
    }

    static function guiFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        // Get a cache key for this module function if it's suitable for module caching
        $cacheKey = xarCache::getModuleKey($modName, $modType, $funcName, $args);

        // Check if the module function is cached
        if (!empty($cacheKey) && xarModuleCache::isCached($cacheKey)) {
            // Return the cached module function output
            return xarModuleCache::getCached($cacheKey);
        }
        $tplData = self::callFunc($modName,$modType,$funcName,$args);
        // If we have a string of data, we assume someone else did xarTpl* for us
        if (!is_array($tplData)) {
            // Set the output of the module function in cache
           if (!empty($cacheKey)) {
                xarModuleCache::setCached($cacheKey, $tplData);
            }
            return $tplData;
        }

        // See if we have a special template to apply
        $templateName = NULL;
        if (isset($tplData['_bl_template'])) $templateName = $tplData['_bl_template'];

        // Create the output.
        $tplOutput = xarTpl::module($modName, $modType, $funcName, $tplData, $templateName);

        // Set the output of the module function in cache
        if (!empty($cacheKey)) {
            xarModuleCache::setCached($cacheKey, $tplOutput);
        }

        return $tplOutput;
    }
    /**
     * Call a module API function.
     *
     * Using the modules name, type, func, and optional arguments
     * builds a function name by joining them together
     * and using the optional arguments as parameters
     * like so:
     * Ex: modName_modTypeapi_modFunc($args);
     *
     * @access public
     * @param modName string registered name of module
     * @param modType string type of function to run
     * @param funcName string specific function to run
     * @param args array arguments to pass to the function
     * @param throwException boolean optional flag to throw an exception if the function doesn't exist or not (default = 1)
     * @return mixed The output of the function, or FALSE on failure
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    static function apiFunc($modName, $modType = 'user', $funcName = 'main', $args = array(),  $throwException = 1)
    {
       if (empty($modName)) throw new EmptyParameterException('modName');
        return self::callfunc($modName, $modType, $funcName, $args, 'api',$throwException);
    }


    /**
     * Get the displayable name for modName
     * jojo - Do not use this function, want to deprecated it
     * The displayable name is sensible to user language.
     *
     * @access public
     * @param modName string registered name of module
     * @return string the displayable name
     */
    static function getDisplayableName($modName = NULL, $type = 'module')
    {

        if (empty($modName)) {
            $modName = xarMod::getName();
        }

        $modInfo = self::getFileInfo($modName, $type);

        return $modInfo['displayname'];

    }

    /**
     * Get the displayable description for modName
     * jojo - Do not use this function, want to deprecated it
     * The displayable description is sensible to user language.
     *
     * @access public
     * @param modName string registered name of module
     * @return string the displayable description
     */

    static function getDisplayableDescription($modName = NULL, $type = 'module')
    {

        if (empty($modName)) {
            $modName = xarModGetName();
        }
        $modInfo = self::getFileInfo($modName, $type);

        return $modInfo['description'];

    }

    /**
     * Check if a module is installed and its state is XARMOD_STATE_ACTIVE
     *
     * @access public
     * @static modAvailableCache array
     * @param modName string registered name of module
     * @param type determines theme or module
     * @return mixed TRUE if the module is available
     * @throws DATABASE_ERROR, BAD_PARAM
     */
    static function isAvailable($modName, $type = 'module')
    {
        static $modAvailableCache = array();

        if (empty($modName)) {
            $msg = xarML('Empty Module or Theme Name (#(1)).', '$modName');
            throw new BadParameterExceptions($msg);
        }

        // Get the real module details.
        // The module details will be cached anyway.
        $modBaseInfo = self::getBaseInfo($modName, $type);

        // Return NULL if the result wasn't set
        if (!isset($modBaseInfo)) return FALSE; // throw back

        if (!empty($GLOBALS['xarMod_noCacheState']) || !isset($modAvailableCache[$modBaseInfo['name']])) {
            // We should be ok now, return the state of the module
            $modState = $modBaseInfo['state'];
            $modAvailableCache[$modBaseInfo['name']] = FALSE;

            if ($modState == XARMOD_STATE_ACTIVE) {
                $modAvailableCache[$modBaseInfo['name']] = TRUE;
            }
        }

        return $modAvailableCache[$modBaseInfo['name']];
    }

    /**
     * Carry out hook operations for module
     * Some commonly used hooks are :
     *   item - display        (user GUI)
     *   item - transform      (user API)
     *   item - new            (admin GUI)
     *   item - create         (admin API)
     *   item - modify         (admin GUI)
     *   item - update         (admin API)
     *   item - delete         (admin API)
     *   item - search         (user GUI)
     *   item - usermenu       (user GUI)
     *   module - modifyconfig (admin GUI)
     *   module - updateconfig (admin API)
     *   module - remove       (module API)
     *
     * @access public
     * @param hookObject string the object the hook is called for - 'item', 'category', 'module', ...
     * @param hookAction string the action the hook is called for - 'transform', 'display', 'new', 'create', 'delete', ...
     * @param hookId integer the id of the object the hook is called for (module-specific)
     * @param extraInfo mixed extra information for the hook, dependent on hookAction
     * @param callerModName string for what module are we calling this (default = current main module)
     *        Note : better pass the caller module via $extrainfo['module'] if necessary, so that hook functions receive it too
     * @param callerItemType string optional item type for the calling module (default = none)
     *        Note : better pass the item type via $extrainfo['itemtype'] if necessary, so that hook functions receive it too
     * @return mixed output from hooks, or NULL if there are no hooks
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
     * @todo <marco> #1 add BAD_PARAM exception
     * @todo <marco> #2 check way of hanlding exception
     * @todo <marco> <mikespub> re-evaluate how GUI / API hooks are handled
     * @todo add itemtype (in extrainfo or as additional parameter)
     */
    static function callHooks($hookObject, $hookAction, $hookId, $extraInfo = array(), $callerModName = NULL, $callerItemType = '')
    {

        // allow override of current module if necessary (e.g. modules admin, blocks, API functions, ...)
        if (empty($callerModName)) {
            if (isset($extraInfo) && is_array($extraInfo) && !empty($extraInfo['module'])) {
                $modName = $extraInfo['module'];
            } else {
                list($modName) = xarRequest::getInfo();
                $extraInfo['module'] = $modName;
            }
        } else {
            $modName = $callerModName;
        }

        // Eventually $callerItemType gets an array. Implicit type cast is not working well in PHP5.4.
        if (!empty($callerItemType) && is_array($callerItemType)) $callerItemType = (string) reset($callerItemType);

        // retrieve the item type from $extraInfo if necessary (e.g. for articles, xarbb, ...)
        if (empty($callerItemType) && isset($extraInfo) &&
            is_array($extraInfo) && !empty($extraInfo['itemtype'])) {
            $callerItemType = $extraInfo['itemtype'];
        }
        xarLogMessage("xarModCallHooks: getting $hookObject $hookAction hooks for $modName$callerItemType");
        $hooklist = self::getHookList($modName, $hookObject, $hookAction, $callerItemType);

        // TODO: #2
        if (!isset($hooklist) ) {
            throw new EmptyParameterExceptions($hooklist);
        }

        $output = array();
        $isGUI = FALSE;

        // TODO: #3

        // Call each hook
        foreach ($hooklist as $hook) {
            if (self::isAvailable($hook['module'])) {
                if ($hook['area'] == 'GUI') {
                    $isGUI = TRUE;
                    if (!self::load($hook['module'], $hook['type']))  return;
                    // return; Bug 4843 return causes all hooks to fail
                    /* jojodee : it's not the return causing the fail necessarily. In fact there is no logical fail due to api or mod not loading
                        in many cases, as the module or hook function is successfully loaded. The failure is in some modules' hook
                        functions that do not set return after a priv check but return unset. It should return empty, not unset.
                        Only a few of the newer modules or hook functions seem to be doing this and these have been corrected where possible in code.
                        We still need a review of this function - and at each step where there is now an existing return.
                    */

                    $res = self::guiFunc($hook['module'],
                                        $hook['type'],
                                        $hook['func'],
                                        array('objectid' => $hookId,
                                             'extrainfo' => $extraInfo));
                    if (!isset($res)) return;//continue;
                    // Note: hook modules can only register 1 hook per hookObject, hookAction and hookArea
                    //       so using the module name as key here is OK (and easier for designers)
                    $output[$hook['module']] = $res;
                } else {
                    if (!self::apiLoad($hook['module'], $hook['type']))  return; //return;
                    $res = self::apiFunc($hook['module'],
                                         $hook['type'],
                                         $hook['func'],
                                         array('objectid' => $hookId,
                                               'extrainfo' => $extraInfo));
                    if (!isset($res))  return; //return;
                    $extraInfo = $res;
                }
            }
        }

        if ($isGUI || preg_match('/^(display|new|modify|search|usermenu|modifyconfig|formdisplay)$/',$hookAction)) {
            return $output;
        } else {
            return $extraInfo;
        }
    }

    /**
     * Get list of available hooks for a particular module, object and action
     *
     * @access private
     * @param callerModName string name of the calling module
     * @param object string the hook object
     * @param action string the hook action
     * @param callerItemType string optional item type for the calling module (default = none)
     * @return array of hook information arrays, or NULL if database error
     * @throws DATABASE_ERROR
     */
    static function getHookList($callerModName, $hookObject, $hookAction, $callerItemType = '')
    {
        static $hookListCache = array();

        if (empty($callerModName)) {
            throw new EmptyParameterExceptions('callerModName');
        }

        // Eventually $callerItemType gets an array. Implicit type cast is not working well in PHP5.4.
        if (!empty($callerModName) && is_array($callerModName)) $callerModName = (string) reset($callerModName);
        if (!empty($callerItemType) && is_array($callerItemType)) $callerItemType = (string) reset($callerItemType);

        if (isset($hookListCache["$callerModName$callerItemType$hookObject$hookAction"])) {
            return $hookListCache["$callerModName$callerItemType$hookObject$hookAction"];
        }

        // Get database info
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $hookstable = $xartable['hooks'];
        $modulestable = $xartable['modules'];

        // Get applicable hooks
        $query = "SELECT DISTINCT xar_tarea, xar_tmodule, xar_ttype, xar_tfunc, xar_order
                  FROM $hookstable WHERE xar_smodule = ?";
        $bindvars = array($callerModName);

        if (empty($callerItemType)) {
            // Itemtype is not specified, only get the generic hooks
            $query .= " AND xar_stype = ''";
        } else {
            // hooks can be enabled for all or for a particular item type
            $query .= " AND (xar_stype = '' OR xar_stype = ?)";
            $bindvars[] = (string)$callerItemType;

            // FIXME: if itemtype is specified, why get the generic hooks? To save a function call in the modules?
            // Answer: generic hooks apply for *all* itemtypes, so if a caller specifies an itemtype, you
            //         need to check whether hooks are enabled for this particular itemtype or for all
            //         itemtypes here...
        }
        $query .= " AND xar_object = ? AND xar_action = ? ORDER BY xar_order ASC";
        $bindvars[] = $hookObject;
        $bindvars[] = $hookAction;
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;

        $resarray = array();
        while(!$result->EOF) {
            list($hookArea,
                 $hookModName,
                 $hookModType,
                 $hookFuncName,
                 $hookOrder) = $result->fields;

            $tmparray = array('area' => $hookArea,
                              'module' => $hookModName,
                              'type' => $hookModType,
                              'func' => $hookFuncName);

            array_push($resarray, $tmparray);
            $result->MoveNext();
        }
        $result->Close();

        $hookListCache["$callerModName$callerItemType$hookObject$hookAction"] = $resarray;

        return $resarray;
    }

    /**
     * Check if a particular active hook module is hooked to the current module (+ itemtype)
     *
     * @access public
     * @static modHookedCache array
     * @param hookModName string name of the hook module we're looking for
     * @param callerModName string name of the calling module (default = current)
     * @param callerItemType string optional item type for the calling module (default = none)
     * @return mixed TRUE if the module is hooked
     * @throws DATABASE_ERROR, BAD_PARAM
     */
    static function isHooked($hookModName, $callerModName = NULL, $callerItemType = '')
    {
        static $modHookedCache = array();
        if (!self::isAvailable($hookModName)) return;
        if (empty($hookModName)) {
            throw new EmptyParameterExceptions('hookModName');
        }
        if (empty($callerModName)) {
            list($callerModName) = xarRequest::getInfo();
        }

        // Get all hook modules for the caller module once
        if (!isset($modHookedCache[$callerModName])) {
            // Get database info
            $dbconn = xarDB::$dbconn;
            $xartable = &xarDB::$tables;
            $hookstable = $xartable['hooks'];
            $modulestable = $xartable['modules'];
            // Get applicable hooks
            $query = "SELECT DISTINCT xar_tmodule, xar_stype FROM $hookstable WHERE xar_smodule = ?";
            $bindvars = array($callerModName);

            $result = $dbconn->Execute($query,$bindvars);
            if (!$result) return;

            $modHookedCache[$callerModName] = array();
            while(!$result->EOF) {
                list($modname,$itemtype) = $result->fields;
                if (!empty($itemtype)) {
                    $itemtype = trim($itemtype);
                }
                if (!isset($modHookedCache[$callerModName][$itemtype])) {
                    $modHookedCache[$callerModName][$itemtype] = array();
                }
                $modHookedCache[$callerModName][$itemtype][$modname] = 1;
                $result->MoveNext();
            }
            $result->Close();
        }
        if (empty($callerItemType)) {
            if (isset($modHookedCache[$callerModName][''][$hookModName])) {
                // generic hook is enabled
                return TRUE;
            } else {
                return FALSE;
            }
        } elseif (is_numeric($callerItemType)) {
            if (isset($modHookedCache[$callerModName][''][$hookModName])) {
                // generic hook is enabled
                return TRUE;
            } elseif (isset($modHookedCache[$callerModName][$callerItemType][$hookModName])) {
                // or itemtype-specific hook is enabled
                return TRUE;
            } else {
                return FALSE;
            }
        } elseif (is_array($callerItemType) && count($callerItemType) > 0) {
            if (isset($modHookedCache[$callerModName][''][$hookModName])) {
                // generic hook is enabled
                return TRUE;
            } else {
                foreach ($callerItemType as $itemtype) {
                    if (!is_numeric($itemtype)) continue;
                    if (isset($modHookedCache[$callerModName][$itemtype][$hookModName])) {
                        // or at least one of the itemtype-specific hooks is enabled
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }

    /**
     * Get info from xarversion.php for module specified by modOsDir
     *
     * @access protected
     * @param modOSdir the module's directory
     * @param type determines theme or module
     * @return array an array of module file information
     * @throws MODULE_FILE_NOT_EXIST
     */
    static function getFileInfo($modOsDir, $type = 'module')
    {
        if (empty($modOsDir)) {
            $msg = xarML('Directory information #(1) is empty.', '$modOsDir');
            throw new EmptyParameterException($msg);
        }

        if (empty($GLOBALS['xarMod_noCacheState']) && xarCoreCache::isCached('Mod.getFileInfos', $modOsDir)) {
            return xarCoreCache::getCached('Mod.getFileInfos', $modOsDir);
        }
        xarLogMessage("xarMod::getFileInfo ". $modOsDir ." / " . $type);

        switch($type) {
            case 'module':
                default:

                $fileName = sys::code().'modules/' . $modOsDir . '/xarversion.php';
                $part = 'xarversion';
                // If the locale is already present, it means we can make the translations available
                if(!empty($GLOBALS['xarMLS_currentLocale']) && file_exists($fileName) &&  self::$MLSEnabled)
                   // xarMLSLoadTranslations($fileName);
                     xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modOsDir, 'modules:', 'version');
                break;

            case 'theme':
                $fileName = xarConfigVars::get(NULL,'Site.BL.ThemesDirectory'). '/' . $modOsDir . '/xartheme.php';

                break;
        }

        if (!file_exists($fileName) || !is_readable($fileName)) {
            // Don't raise an exception, it is too harsh, but log it tho (bug #295)
            xarLogMessage("xarMod_getFileInfo: Could not find xarversion.php, skipping $modOsDir");
            return;
        }

        include($fileName);

        if (!isset($themeinfo)){
            $themeinfo = array();
        }
        if (!isset($modversion)){
            $modversion = array();
        }
        $version = array_merge($themeinfo, $modversion);

        // name and id are required, assert them, otherwise the module is invalid
        assert('isset($version["name"]) && isset($version["id"]) && !empty($version["name"]) && !empty($version["id"]); /* Both name and id need to be present in xarversion.php */');
        //jojo - do not use xarML calls in this function - add where required in modules
        //we want to ensure 'name' is the short name, not display name but many modules an themes don't abide by this, esp. themes

        $FileInfo['name']           = $version['name'];
        $FileInfo['id']             = (int) $version['id'];
        //bug xgami-000684
        if ($version['id'] != $FileInfo['id'] ) { //in case the version id is too high for the system
            xarLogMessage("xarMod::getFileInfo: BAD ID - skipping $modOsDir");
            return;
        }
        $FileInfo['displayname']    = isset($version['displayname']) ? $version['displayname'] : ucfirst($version['name']);
        $FileInfo['description']    = isset($version['description']) ? $version['description'] : FALSE;
        if (isset($version['displaydescription'])) {
            $FileInfo['displaydescription'] = $version['displaydescription'];
        } else {
            $FileInfo['displaydescription'] = $FileInfo['description'];
        }
        $FileInfo['directory']      = (isset($version['directory']) && !empty($version['directory'])) ? $version['directory'] : $modOsDir; //for now
        $FileInfo['version']        = isset($version['version'])    ? $version['version'] : 0;
        $FileInfo['admin']          = isset($version['admin'])          ? $version['admin'] : FALSE;
        $FileInfo['admin_capable']  = isset($version['admin'])          ? $version['admin'] : FALSE;
        $FileInfo['user']           = isset($version['user'])           ? $version['user'] : FALSE;
        $FileInfo['user_capable']   = isset($version['user'])           ? $version['user'] : FALSE;
        $FileInfo['class']          = isset($version['class'])          ? $version['class'] : FALSE;
        $FileInfo['category']       = isset($version['category'])       ? $version['category'] : FALSE;
        $FileInfo['locale']         = isset($version['locale'])         ? $version['locale'] : 'en_US.utf-8';
        $FileInfo['author']         = isset($version['author'])         ? $version['author'] : FALSE;
        $FileInfo['contact']        = isset($version['contact'])        ? $version['contact'] : FALSE;
        $FileInfo['dependency']     = isset($version['dependency'])     ? $version['dependency'] : array();
        $FileInfo['dependencyinfo'] = isset($version['dependencyinfo']) ? $version['dependencyinfo'] : array();
        $FileInfo['extensions']     = isset($version['extensions'])     ? $version['extensions'] : array();
        $FileInfo['license']        = isset($version['license'])        ? $version['license'] : FALSE;
        $FileInfo['official']       = isset($version['official'])       ? $version['official'] : FALSE;
        $FileInfo['help']           = isset($version['help'])           ? $version['help'] : FALSE;
        $FileInfo['changelog']      = isset($version['changelog'])      ? $version['changelog'] : FALSE;
        $FileInfo['credits']        = isset($version['credits'])        ? $version['credits'] : FALSE;
        $FileInfo['xar_version']    = isset($version['xar_version'])    ? $version['xar_version'] : FALSE;
        $FileInfo['bl_version']     = isset($version['bl_version'])     ? $version['bl_version'] : '1.0';
        //consider theme var that is called 'contact_info' - let's standardize and update elsewhere to 'contact'
        $FileInfo['contact']        = isset($version['contact_info'])?$version['contact_info']:(isset($version['contact']) ? $version['contact'] : FALSE);

        //required in theme vars
        $FileInfo['homepage']       = isset($version['homepage'])       ? $version['homepage'] : FALSE;
        $FileInfo['email']          = isset($version['email'])          ? $version['email'] : FALSE;
        $FileInfo['publish_date']   = isset($version['publish_date'])   ? $version['publish_date'] : FALSE;

        xarCoreCache::setCached('Mod.getFileInfos', $modOsDir, $FileInfo);

        return $FileInfo;
    }

    /**
     * Load a module's base information
     *
     * @access protected
     * @param modName string the module's name
     * @param type determines theme or module
     * @return mixed an array of base module info on success
     * @throws DATABASE_ERROR, MODULE_NOT_EXIST
     */
    static function getBaseInfo($modName, $type = 'module')
    {
        if (empty($modName)) {
            $msg = xarML('Module or Theme Name #(1) is empty.', '$modName');
            throw new EmptyParameterException($msg);
        }

        if ($type != 'module' && $type != 'theme') {
            $msg = xarML('Wrong type, it must be \'module\' or \'theme\': #(1).', $type);
            throw new BadParameterException($msg);
        }
        // FIXME: <MrB> I've seen cases where the cache info is not in sync
        // with reality. I've take a couple ones out, but I haven't tested all
        // the way through.
        // <mikespub> There were some issues where you tried to initialize/activate
        // several modules one after the other during the same page request (e.g. at
        // installation), since the state changes of those modules weren't taken
        // into account. The GLOBALS['xarMod_noCacheState'] flag tells Xarigami *not*
        // to cache module (+state) information in that case...

        if ($type == 'module') {
            $cacheCollection = 'Mod.BaseInfos';
            $checkNoState = 'xarMod_noCacheState';
        } else {
            $cacheCollection = 'Theme.BaseInfos';
            $checkNoState = 'xarTheme_noCacheState';
        }

        if (empty($GLOBALS[$checkNoState]) && xarCoreCache::isCached($cacheCollection, $modName)) {
           return xarCoreCache::getCached($cacheCollection, $modName);
        }
        xarLogMessage("xarMod::getBaseInfo ". $modName ." / ". $type);

        $dbconn = xarDB::$dbconn;
        $tables = &xarDB::$tables;
        $sitePrefix = xarDB::$prefix;
        $modulestable = $sitePrefix.'_'.$type.'s';

        $query = 'SELECT xar_regid, xar_directory,xar_mode,'
            . ' xar_id, xar_state, xar_name, xar_class'
            . ' FROM '.$modulestable.' mods'
            . ' WHERE xar_name = ?';
        $bindvars = array($modName);

        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) return;

        if ($result->EOF) {
            $result->Close();
            return;
        }

        $modBaseInfo = array();

        list($regid, $directory, $mode, $systemid, $state, $name, $class) = $result->fields;
        $result->Close();

        $modBaseInfo['regid']       = (int) $regid;
        $modBaseInfo['mode']        = (int) $mode;
        $modBaseInfo['systemid']    = (int) $systemid;
        $modBaseInfo['state']       = (int) $state;
        $modBaseInfo['name']        = $name;
        $modBaseInfo['directory']   = $directory;
        $modBaseInfo['class']   = $class;
        //Jojo Better to leave it out, and call this function and additional xarMod_getFileInfo
        //by calling function - if required
        /*
        $modfileinfo = xarMod::getFileInfo($name,$type);
        $modBaseInfo['displayname'] = $modfileinfo['displayname'];
        $modBaseInfo['description'] = $modfileinfo['description'];
        */
        // Shortcut for os prepared directory
        $modBaseInfo['osdirectory'] = xarVarPrepForOS($directory);

        xarCoreCache::setCached($cacheCollection, $name, $modBaseInfo);

        return $modBaseInfo;
    }

    /**
     * Get all module variables for a particular module
     *
     * @author Michel Dalle
     * @access protected
     * @param modName string
     * @return mixed TRUE on success
     * @throws DATABASE_ERROR, BAD_PARAM
     */
    static function getVarsByModule($modName, $type = 'module')
    {
        if (empty($modName)) {
            $msg = xarML('Empty theme or module name (#(1)).', '$modName');
            throw new EmtpyParameterExceptions($msg);
        }
        switch($type) {
            case 'module':
                default:
                $modBaseInfo = self::getBaseInfo($modName);
                if (!isset($modBaseInfo)) {
                    return; // throw back
                }
                break;
            case 'theme':
                $modBaseInfo = self::getBaseInfo($modName, $type = 'theme');
                $themeName = $modName;
                if (!isset($modBaseInfo)) {
                    return; // throw back
                }
                break;
        }

        $dbconn = xarDB::$dbconn;
        $tables = &xarDB::$tables;
        $modvars= array();
        switch($type) {
            case 'module':
                default:
                $module_varstable = $tables['module_vars'];

                $query = 'SELECT xar_id, xar_name, xar_value FROM '.$module_varstable
                    . ' WHERE xar_modid = ?';
                $result = $dbconn->Execute($query, array($modBaseInfo['systemid']));
                if (!$result) return;

                while (!$result->EOF) {
                    list($id, $name, $value) = $result->fields;
                    $modvars[$name] =  array('id'=>$id,'name' => $name, 'value' => $value);
                    xarCoreCache::setCached('Mod.Variables.' . $modName, $name, $value);
                    $result->MoveNext();
                }
                $result->Close();

                xarCoreCache::setCached('Mod.GetVarsByModule', $modName, TRUE);
                break;
            case 'theme':
                $theme_varsTable = $tables['theme_vars'];

                $query = 'SELECT xar_id, xar_name, xar_value, xar_description, xar_config'
                    . ' FROM '.$theme_varsTable.' WHERE xar_themeName = ?';
                $result = $dbconn->Execute($query, array($themeName));
                if (!$result) return;

                while (!$result->EOF) {
                    list($id,  $name, $value, $description, $config) = $result->fields;
                    $modvars[$name] = array('id'=>$id,'name' => $name, 'value' => $value, 'description' => $description, 'config'=>$config);
                    xarCoreCache::setCached('Theme.Variables.' . $themeName, $name, $value);
                    $result->MoveNext();
                }
                $result->Close();

                xarCoreCache::setCached('Theme.GetVarsByTheme', $themeName, TRUE);
                break;
        }

        return $modvars;
    }

    /**
     * Get all module variables with a particular name
     *
     * @author Michel Dalle
     * @access protected
     * @param name string
     * @return mixed TRUE on success
     * @throws DATABASE_ERROR, BAD_PARAM
     */
    static function getVarsByName($varName, $type = 'module')
    {
        if (empty($varName)) {
            $msg = xarML('Empty Theme or Module variable name (#(1)).', '$varName');
            throw new EmptyParameterExceptions($msg);
        }

        $dbconn = xarDB::$dbconn;
        $tables = &xarDB::$tables;

        switch($type) {
        case 'module':
        default:
            $module_varstable = $tables['module_vars'];
            $module_table = $tables['modules'];
            $query = "SELECT mods.xar_name, vars.xar_value
                          FROM $module_table mods , $module_varstable vars
                          WHERE mods.xar_id = vars.xar_modid AND
                                vars.xar_name = ?";
            break;
        case 'theme':
            $theme_varsTable = $tables['theme_vars'];
            $query = "SELECT xar_themeName,
                                 xar_value
                          FROM $theme_varsTable
                          WHERE xar_name = ?";
            break;
        }

        $result = $dbconn->Execute($query,array($varName));
        if (!$result) return;

        // Add module variables to cache
        while (!$result->EOF) {
            list($name,$value) = $result->fields;
            xarCoreCache::setCached('Mod.Variables.' . $name, $varName, $value);
            $result->MoveNext();
        }

        $result->Close();
        switch($type) {
            case 'module':
                default:
                xarCoreCache::setCached('Mod.GetVarsByName', $varName, TRUE);
                break;
            case 'theme':
                xarCoreCache::setCached('Theme.GetVarsByName', $varName, TRUE);
                break;
        }

        return TRUE;
    }

    /**
     * Load database definition for a module
     *
     * @access private
     * @param modName string name of module to load database definition for
     * @param modOsDir string directory that module is in
     * @return mixed TRUE on success
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
     */
    static function loadDbInfo($modName, $modDir=NULL, $type = 'module')
    {
        static $loadedDbInfoCache = array();

        if($type == 'theme') return TRUE; // sigh.

        if (empty($modName)) {
            throw new EmptyParameterExceptions('modName');
        }
        // Check to ensure we aren't doing this twice
        if (isset($loadedDbInfoCache[$modName])) {
            return TRUE;
        }

        $modBaseInfo = self::getBaseInfo($modName,$type);

        // Get the directory if we don't already have it
        if (empty($modDir)) {
            if (!isset($modBaseInfo)) return; // throw back
            $modDir = xarVarPrepForOS($modBaseInfo['directory']);
        } else {
            $modDir = xarVarPrepForOS($modDir);
        }
        // Load the database definition if required
        $osxartablefile = sys::code()."modules/$modDir/xartables.php";
        if (!file_exists($osxartablefile)) {
            return FALSE;
        }
        try {
           sys::import('modules.'.$modDir.'.xartables');
        } catch (Exception $e) {
            $loadedDbInfoCache[$modName] = FALSE;
            return FALSE;
        }

        $tablefunc = $modName . '_' . 'xartables';

        if (function_exists($tablefunc)) {
            xarDB::importTables($tablefunc());
        }

        $loadedDbInfoCache[$modName] = TRUE;

        return TRUE;
    }

    /**
     * Get the module's current state
     *
     * @access public
     * @param  integer the module's registered id
     * @param modMode integer the module's site mode
     * @param type determines theme or module
     * @return mixed the module's current state
     * @throws DATABASE_ERROR, MODULE_NOT_EXIST
     * @todo implement the xarMod__setState reciproke
     */
    static function getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
    {
        if ($modRegId < 1) {
            throw new BadParameterException('modRegId');
        }

        $sitePrefix = xarDB::$prefix;
        $dbconn = xarDB::$dbconn;
        $tables = &xarDB::$tables;
        switch($type) {
            case 'module':
                default:
                $moduleTable = $tables['modules'];

                $query = "SELECT xar_state FROM $modules
                          WHERE xar_regid = ?";
                break;
            case 'theme':
                $themeTable = $tables['themes'];

                $query = "SELECT xar_state FROM $themes
                          WHERE xar_regid = ?";

                break;
        }

        $result = $dbconn->Execute($query,array($modRegId));
        if (!$result) return;

        // the module is not in the table
        // set state to XARMOD_STATE_MISSING
        if (!$result->EOF) {
            list($modState) = $result->fields;
            $result->Close();
            return (int) $modState;
        } else {
            $result->Close();
            return (int) XARMOD_STATE_UNINITIALISED;
        }
    }

    /**
     * register a hook function
     *
     * @access public
     * @param hookObject the hook object
     * @param hookAction the hook action
     * @param hookArea the area of the hook (either 'GUI' or 'API')
     * @param hookModName name of the hook module
     * @param hookModType name of the hook type
     * @param hookFuncName name of the hook function
     * @return bool TRUE on success
     * @throws DATABASE_ERROR
     */
    static function registerHook($hookObject,
                               $hookAction,
                               $hookArea,
                               $hookModName,
                               $hookModType,
                               $hookFuncName)
    {

        // Get database info
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $hookstable = $xartable['hooks'];

        // Insert hook
        $query = "INSERT INTO $hookstable (
                  xar_id,
                  xar_object,
                  xar_action,
                  xar_tarea,
                  xar_tmodule,
                  xar_ttype,
                  xar_tfunc)
                  VALUES (?,?,?,?,?,?,?)";
        $seqId = $dbconn->GenId($hookstable);
        $bindvars = array($seqId,$hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName);
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;

        return TRUE;
    }

    /**
     * unregister a hook function
     *
     * @access public
     * @param hookObject the hook object
     * @param hookAction the hook action
     * @param hookArea the area of the hook (either 'GUI' or 'API')
     * @param hookModName name of the hook module
     * @param hookModType name of the hook type
     * @param hookFuncName name of the hook function
     * @return bool TRUE if the unregister call suceeded, FALSE if it failed
     */
    static function unregisterHook($hookObject,
                                 $hookAction,
                                 $hookArea,
                                 $hookModName,
                                 $hookModType,
                                 $hookFuncName)
    {

        // Get database info
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $hookstable = $xartable['hooks'];

        // Remove hook
        $query = "DELETE FROM $hookstable
                  WHERE xar_object = ?
                  AND xar_action = ? AND xar_tarea = ? AND xar_tmodule = ?
                  AND xar_ttype = ?  AND xar_tfunc = ?";
        $bindvars = array($hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName);
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;

        return TRUE;
    }

    /**
     * Resolve a module alias
     * This is only a convenience wrapper fot xarRequest function
     * @todo evaluate dependency consequences
    */
    static function getAlias($var)
    {
        return xarRequest::resolveModuleAlias($var);
    }

    /**
     * Set an alias for a module
     *
     * @todo evalutate dependency consequences
     *
    */
    static function setAlias($alias, $modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return self::apiFunc('modules', 'admin', 'add_module_alias', $args);
    }

    /**
     * Delete an alias for a module
     * @todo evaluate dependency consequences
     * @param string $alias
     * @param string $modName
     */
    static function delAlias($alias, $modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return self::apiFunc('modules', 'admin', 'delete_module_alias', $args);
    }


}
/* todo -- will probably change */

interface IxarUrl
{

}
/**
 * Preliminary class to model xarUrl
 *
 */
class xarUrl extends xarObject implements IxarUrl
{
    /**
     * Encode parts of a URL.
     * This will encode the path parts, the and GET parameter names
     * and data. It cannot encode a complete URL yet.
     *
     * @access private
     * @param data string the data to be encoded (see todo)
     * @param type string the type of string to be encoded ('getname', 'getvalue', 'path', 'url', 'domain')
     * @return string the encoded URL parts
     * @todo this could be made public
     * @todo support arrays and encode the complete array (keys and values)
     */
    static function encode($data, $type = 'getname')
    {
        // Different parts of a URL are encoded in different ways.
        // e.g. a '?' and '/' are allowed in GET parameters, but
        // '?' must be encoded when in a path, and '/' is not
        // allowed in a path at all except as the path-part
        // separators.
        // The aim is to encode as little as possible, so that URLs
        // remain as human-readable as we can allow.

        // We will encode everything first, then restore a select few
        // characters.
        // TODO: tackle it the other way around, i.e. have rules for
        // what to encode, rather than undoing some ecoded characters.
        $data = rawurlencode($data);

        $decode = array(
            'path' => array(
                array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D'),
                array(',', '$', '!', '*', '(', ')', '=')
            ),
            'getname' => array(
                array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D'),
                array(',', '$', '!', '*', '(', ')', '=', '\'', '[', ']')
            ),
            'getvalue' => array(
                array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D', '%3A', '%2F', '%3F', '%3D'),
                array(',', '$', '!', '*', '(', ')', '=', '\'', '[', ']', ':', '/', '?', '=')
            )
        );

        // TODO: check what automatic ML settings have on this.
        // I suspect none, as all multi-byte characters have ASCII values
        // of their parts > 127.
        if (isset($decode[$type])) {
            $data = str_replace($decode[$type][0], $decode[$type][1], $data);
        }

        return $data;
    }

    /**
     * Format GET parameters formed by nested arrays, to support xarModURL().
     * This function will recurse for each level to the arrays.
     *
     * @access private
     * @param args array the array to be expanded as a GET parameter
     * @param prefix string the prefix for the GET parameter
     * @return string the expanded GET parameter(s)
     */
    static function nested($args, $prefix)
    {
        $path = '';
        foreach ($args as $key => $arg) {
            if (is_array($arg)) {
                $path .= xarMod__URLnested($arg, $prefix . '['.xarMod__URLencode($key, 'getname').']');
            } else {
                $path .= $prefix . '['.xarMod__URLencode($key, 'getname').']' . '=' . xarMod__URLencode($arg, 'getvalue');
            }
        }

        return $path;
    }

    /**
     * Add further parameters to the path, ensuring each value is encoded correctly.
     *
     * @access private
     * @param args array the array to be encoded
     * @param path string the current path to append parameters to
     * @param psep string the path seperator to use
     * @return string the path with encoded parameters
     */
    static function addParametersToPath($args, $path, $pini, $psep)
    {
        if (count($args) > 0)
        {
            $params = '';

            foreach ($args as $k=>$v) {
                if (is_array($v)) {
                    // Recursively walk the array tree to as many levels as necessary
                    // e.g. ...&foo[bar][dee][doo]=value&...
                    $params .= xarMod__URLnested($v, $psep . $k);
                } elseif (isset($v)) {
                    // TODO: rather than rawurlencode, use a xar function to encode
                    $params .= (!empty($params) ? $psep : '') . xarMod__URLencode($k, 'getname') . '=' . xarMod__URLencode($v, 'getvalue');
                }
            }

            // Join to the path with the appropriate character,
            // depending on whether there are already GET parameters.
            $path .= (strpos($path, $pini) === FALSE ? $pini : $psep) . $params;
        }

        return $path;
    }


    /**
     * Generates an URL that reference to a module function.
     *
     * @access public
     * @global xarMod_generateShortURLs bool
     * @global xarMod_generateXMLURLs bool
     * @param modName string registered name of module
     * @param modType string type of function
     * @param funcName string module function
     * @param string fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
     * @param args array of arguments to put on the URL
     * @param entrypoint array of arguments for different entrypoint than index.php
     * @return mixed absolute URL for call, or FALSE on failure
     * @todo allow for an alternative entry point (e.g. stream.php) without affecting the other parameters
     */
    static function url($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXmlUrl = NULL, $fragment = NULL, $entrypoint = array())
    {
        // Parameter separator and initiator.
        $psep = '&';
        $pini = '?';
        $pathsep = '/';

        // Initialise the path.
        $path = '';

        // The following allows you to modify the BaseModURL from the config file
        // it can be used to configure Xarigami for mod_rewrite by
        // setting BaseModURL = '' in config.system.php
        $BaseModUrl = xarSystemVars::get(NULL,'BaseModURL',TRUE);
        if (!isset($BaseModUrl)) {
            $BaseModUrl = 'index.php';
        }

        // No module specified - just jump to the home page.
        if (empty($modName)) {
            return xarServer::getBaseURL() . $BaseModUrl;
        }

        // Take the global setting for XML format generation, if not specified.
        if (!isset($generateXmlUrl)) {
            $generateXmlUrl = xarMod::$genXmlUrls;
        }

        // If an entry point has been set, then modify the URL entry point and modType.
        if (!empty($entrypoint)) {
            if (is_array($entrypoint)) {
                $modType = $entrypoint['action'];
                $entrypoint = $entrypoint['entry'];
            }
            $BaseModUrl = $entrypoint;
        }

        // If we have an empty argument (ie NULL => NULL) then set a flag and
        // remove that element.
        // FIXME: this is way too hacky, NULL as a key for an array sooner or later will fail. (php 4.2.2 ?)
        if (is_array($args) && @array_key_exists(NULL, $args) && $args[NULL] === NULL) {
            // This flag means that the GET part of the URL must be opened.
            $open_get_flag = TRUE;
            unset($args[NULL]);
        }

        // Check the global short URL setting before trying to load the URL encoding function
        // for the module. This also applies to custom entry points.
        if (xarMod::$genShortUrls) {
            // The encode_shorturl will be in userapi.
            // Note: if a module declares itself as supporting short URLs, then the encoding
            // API subsequently fails to load, then we want those errors to be raised.
            if ($modType == 'user' && xarModVars::get($modName, 'SupportShortURLs') && xarMod::apiLoad($modName, $modType)) {
                $encoderArgs = $args;
                $encoderArgs['func'] = $funcName;

                // Execute the short URL function.
                // It must exist if the SupportShortURLs variable is set for the module.
                // FIXME: if the function does not exist, then errors are not handled well, often hidden.
                // Ensure a missing short URL encoding function gets written to the log file.
                $short = xarMod::apiFunc($modName, $modType, 'encode_shorturl', $encoderArgs, FALSE);
                if (!empty($short)) {
                    if (is_array($short)) {
                        // An array of path and args has been returned (both optional) - new style.
                        if (!empty($short['path'])) {
                            foreach($short['path'] as $pathpart) {
                                // Use path encoding method, which can differ from
                                // the GET parameter encoding method.
                                if ($pathpart != '') {
                                    $path .= $pathsep . self::encode($pathpart, 'path');
                                }
                            }
                        }
                        // Unconsumed arguments, to be treated as additional GET parameters.
                        // These may actually be additional GET parameters injected by the
                        // short URL function - it makes no difference either way.
                        if (!empty($short['get']) && is_array($short['get'])) {
                            $path = self::addParametersToPath($short['get'], $path, $pini, $psep);
                        } else {
                            $args = array();
                        }
                    } else {
                        // A string URL has been returned - old style - deprecated.
                        $path = $short;
                        $args = array();
                    }

                    // Use xaraya default (index.php) or BaseModURL if provided in config.system.php
                    $path = $BaseModUrl . $path;

                    // Remove the leading / from the path (if any).
                    $path = preg_replace('/^\//', '', $path);

                    // Workaround for bug 3603
                    // why: template might add extra params we dont see here
                    if (!empty($open_get_flag) && !strpos($path, $pini)) {$path .= $pini;}

                    // We now have the short form of the URL.
                    // Further custom manipulation of the URL can be added here.
                    // It may be worthwhile allowing for some kind of hook?
                }
            }
        }
        // If the path is still empty, then there is either no short URL support
        // at all, or no short URL encoding was available for these arguments.
        if (empty($path)) {
            if (!empty($entrypoint)) {
                // Custom entry-point.
                // TODO: allow the alt entry point to work without assuming it is calling
                // ws.php, so retaining the module and type params, and short url.
                // Entry Point comes as an array since ws.php sets a type var.
                // Entry array should be $entrypoint['entry'], $entrypoint['action']
                // e.g. ws.php?type=xmlrpc&args=foo
                // * Can also pass in the 'action' to $modType, and the entry point as
                // a string. It makes sense using existing parameters that way.
                $args = array('type' => $modType) + $args;
            }  else {
                $baseargs = array('module' => $modName);
                if ($modType !== 'user') {
                    $baseargs['type'] = $modType;
                }
                if ($funcName !== 'main') {
                    $baseargs['func'] = $funcName;
                }
                // Standard entry point - index.php or BaseModURL if provided in config.system.php
                $args = $baseargs + $args;
            }

            // Add GET parameters to the path, ensuring each value is encoded correctly.
            $path = self::addParametersToPath($args, $BaseModUrl, $pini, $psep);

            // We have the long form of the URL here.
            // Again, some form of hook may be useful.
        }

        // Add the fragment if required.
        if (isset($fragment)) {
            $path = $path . '#' . urlencode($fragment);
        }

        // Encode the URL if an XML-compatible format is required.
        if ($generateXmlUrl) {
            $path = htmlspecialchars($path);
        }
        // Return the URL.
        return xarServer::getBaseURL() . $path;
    }
}
?>
