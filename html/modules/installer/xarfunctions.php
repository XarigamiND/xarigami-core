<?php
/**
 * Call an installer function
 *
 * @package Installer
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Installer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Call an installer function.
 *
 * @author Xarigami Core Development Team
 * This function is similar to xarModFunc but simplified.
 * We need this because during install we cant have the module
 * subsystem online directly, so we need a direct way of calling
 * the admin functions of the installer. The actual functions
 * called adhere to normal Xarigami module functions, so we can use
 * the installer later on when xarigami is installed
 *
 * @access public
 * @param funcName specific function to run
 * @param args argument array
 * @returns mixed
 * @return The output of the function, or false on failure
 * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarInstallFunc($funcName = 'main', $args = array())
{
    $modName = isset($modName)?$modName:'installer';
    $modType = isset($modType)?$modType:'admin';

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";

    if (!function_exists($modFunc)) {
        // try to load it
         xarInstallLoad($funcName);
        if(!function_exists($modFunc)) throw new FunctionNotFoundException($modFunc);
    }

    // Load the translations file
    $file = sys::code().'modules/'.$modName.'/xar'.$modType.'/'.strtolower($funcName).'.php';
    if (!xarMLSLoadTranslations($file)) return;

    $tplData = $modFunc($args);
    if (!is_array($tplData)) {
        return $tplData;
    }

    // <mrb> Why is this here?
    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    return xarTplModule($modName, $modType, $funcName, $tplData, $templateName);
}

function xarInstallAPIFunc($funcName = 'main', $args = array())
{
    extract($args);
    $modName = isset($modName)?$modName:'installer';
    $modType = isset($modType)?$modType:'admin';

    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    if (!function_exists($modAPIFunc) && $modName =='installer') {
        // attempt to load the install api
        xarInstallAPILoad();
        // let's check for the function again to be sure
        if (!function_exists($modAPIFunc)) throw new FunctionNotFoundException($modAPIFunc);
    }

    // Load the file
    $file = sys::code().'modules/'.$modName.'/xar'.$modType.'api/'.strtolower($funcName).'.php';
    if (file_exists($file)) {
        //load it
         sys::import('modules.'.$modName.'.xar'.$modType.'api.'.strtolower($funcName));
    }
    if ($modName == 'installer') {
        $func = $modAPIFunc($args);
    } elseif ($file) {

        $func =  $modAPIFunc($args);
    }
    return $func;
}

/**
 * Loads the modType API for installer identified by modName.
 *
 * @access public
 * @param modName registered name of the module
 * @param modType type of functions to load
 * @returns bool
 * @return true on success
 * @throws BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarInstallAPILoad()
{
    static $loadedAPICache = array();

    $modName    = 'installer';
    $modOsDir   = 'installer';
    $modType  = 'admin';

    if (isset($loadedAPICache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }

    $modOsType = xarVarPrepForOS($modType);

    $osfile = sys::code() . "modules/$modOsDir/xar{$modOsType}api.php";
    if (!file_exists($osfile)) throw new FileNotFoundException($osfile);


    // Load the file
    include $osfile;
    $loadedAPICache[strtolower("$modName$modType")] = true;

    return true;
}

/**
 * Loads the modType of installer identified by modName.
 *
 * @access public
 * @returns string
 * @return true
 * @throws BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarInstallLoad($func)
{
    static $loadedModuleCache = array();

    $modName = 'installer';
    $modType = 'admin';

    if (empty($modName)) throw new EmptyParameterException('modName');

    if (isset($loadedModuleCache[strtolower("$modName$modType")])) {
        // Already loaded from somewhere else
        return true;
    }

    // Load the module files
    $modOsType = xarVarPrepForOS($modType);
    $modOsDir = 'installer';

    $osfile = sys::code() . "modules/$modOsDir/xar$modOsType/$func.php";
    if (!file_exists($osfile)) throw new FileNotFoundException($osfile);

    // Load file
    include $osfile;
    $loadedModuleCache[strtolower("$modName$modType")] = true;

    // Load the module translations files
    $res = xarMLSLoadTranslations($osfile);
    return true;
}
/**
 * Grab config vars independently
 *
 * @return var
 */
function xarUpgradeGetVar($name)
{
       $systemArgs = array('userName'          => xarSystemVars::get(sys::CONFIG, 'DB.UserName'),
                            'password'          => xarSystemVars::get(sys::CONFIG, 'DB.Password'),
                            'databaseHost'      => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                            'databaseType'      => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'      => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'persistent'        => null,
                            'systemTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                            'siteTablePrefix'   => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix')
                            );
        // Connect to database
    sys::import('xarigami.xarDB');
    xarDB::init($systemArgs,1);
    $dbconn = xarDB::$dbconn;

    $bindvars = array();
    $config_varsTable = $systemArgs['systemTablePrefix'].'_config_vars';

    $query = "SELECT xar_value FROM $config_varsTable WHERE xar_name=?";
    $bindvars = array($name);

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result->EOF) {
        list($value) = $result->fields;
        $value = @unserialize($value);

         return $value;
    }
    $result->close();
    return;
}
function xarUpgradeSetVar($name,$value)
{
       $systemArgs = array('userName'          => xarSystemVars::get(sys::CONFIG, 'DB.UserName'),
                            'password'          => xarSystemVars::get(sys::CONFIG, 'DB.Password'),
                            'databaseHost'      => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                            'databaseType'      => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'      => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'persistent'        => null,
                            'systemTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                            'siteTablePrefix'   => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix')
                            );
    // Connect to database
    sys::import('xarigami.xarDB');
    xarDB::init($systemArgs,1);
    $dbconn = xarDB::$dbconn;
    $config_varsTable = $systemArgs['systemTablePrefix'].'_config_vars';
    $serialvalue = serialize($value);
    $bindvars = array();
    //try and delete
    $query = "DELETE FROM $config_varsTable WHERE xar_name = ?";
            $bindvars = array($name);
            $result = $dbconn->Execute($query,$bindvars);

    $seqId = $dbconn->GenId($config_varsTable);
            $query = "INSERT INTO $config_varsTable
                      (xar_id, xar_name, xar_value)
                      VALUES (?,?,?)";
            $bindvars = array($seqId, $name, $serialvalue);

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
    $result->close();
    return true;
}
function xarUpgradeSetModVar($module,$name,$value)
{
       $systemArgs = array('userName'          => xarSystemVars::get(sys::CONFIG, 'DB.UserName'),
                            'password'          => xarSystemVars::get(sys::CONFIG, 'DB.Password'),
                            'databaseHost'      => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                            'databaseType'      => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'      => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'persistent'        => null,
                            'systemTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                            'siteTablePrefix'   => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix')
                            );
    // Connect to database
    sys::import('xarigami.xarDB');
    xarDB::init($systemArgs,1);
    $dbconn = xarDB::$dbconn;
    $modvarstable = $systemArgs['systemTablePrefix'].'_module_vars';
    $modBaseInfo = xarUpgradeGetModInfo($module,'module');
    $modid = $modBaseInfo['systemid'];
    $bindvars = array();
    //try and delete
    $query = "DELETE FROM $modvarstable
               WHERE xar_modid = ? and xar_name= ?";
                $bindvars = array($modid, $name);
                 $result = $dbconn->Execute($query,$bindvars);
    //Now add it back
    $seqId = $dbconn->GenId($modvarstable);
                $query = "INSERT INTO $modvarstable
                             (xar_id, xar_modid, xar_name, xar_value)
                          VALUES (?,?,?,?)";
                $bindvars = array($seqId, $modid, $name,(string)$value);

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
    $result->close();
    return true;
}
function xarUpgradeGetModVar($module,$name)
{
       $systemArgs = array('userName'          => xarSystemVars::get(sys::CONFIG, 'DB.UserName'),
                            'password'          => xarSystemVars::get(sys::CONFIG, 'DB.Password'),
                            'databaseHost'      => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                            'databaseType'      => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'      => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'persistent'        => null,
                            'systemTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                            'siteTablePrefix'   => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix')
                            );
    // Connect to database
    sys::import('xarigami.xarDB');
    xarDB::init($systemArgs,1);
    $dbconn = xarDB::$dbconn;
    $modvarstable = $systemArgs['systemTablePrefix'].'_module_vars';
    $modBaseInfo = xarUpgradeGetModInfo($module,'module');
    $modid = $modBaseInfo['systemid'];
    $bindvars = array();
    //try and grab the var
    $query = "SELECT xar_name, xar_value FROM $modvarstable WHERE xar_modid = ? and xar_name = ?";
                $bindvars = array($modid, $name);
    $result = $dbconn->Execute($query,$bindvars);

    if (!$result) return;
    $vars = array();
    while (!$result->EOF) {
        list($name, $value) =  $result->fields;
                $vars[] = array('name'=>$name, 'value'=>$value);
        $result->MoveNext();
    }
    //we really need to fix things more generally for booleans etc - this is horrible
    //we need to get a value back tho for checking here
    if (!empty($vars)) {
        if (count($vars) == 1) {
            if (empty( $vars['value']) ||  $vars['value'] ===NULL) {
                 $vars['value'] = '0';
            } else {
                $vars = $vars['value'];
            }
        } else {
            //we have a problem but it's not the time now to fix it
            //if an array is returned we need to act on it somehow
        }
    }  else {
        $vars = NULL;
    }
    $result->close();
    return $vars;
}
//returns select info only
function xarUpgradeGetModInfo($module,$type)
{
  $systemArgs = array('userName'          => xarSystemVars::get(sys::CONFIG, 'DB.UserName'),
                            'password'          => xarSystemVars::get(sys::CONFIG, 'DB.Password'),
                            'databaseHost'      => xarSystemVars::get(sys::CONFIG, 'DB.Host'),
                            'databaseType'      => xarSystemVars::get(sys::CONFIG, 'DB.Type'),
                            'databaseName'      => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
                            'persistent'        => null,
                            'systemTablePrefix' => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix'),
                            'siteTablePrefix'   => xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix')
                            );
    $dbconn = xarDB::$dbconn;
    $modulestable = $systemArgs['systemTablePrefix'].'_modules';

    $query = 'SELECT xar_regid,xar_directory, xar_mode,'
        . ' xar_id, xar_name,xar_class'
        . ' FROM '.$modulestable
        . ' WHERE xar_name = ?';

    $bindvars = array($module);
    $result = $dbconn->Execute($query, $bindvars);
    if (!$result) return;
    if ($result->EOF) {
        $result->Close();
        return;
    }

    $modBaseInfo = array();

    list($regid, $directory, $mode, $systemid, $name, $class) = $result->fields;
    $result->Close();

    $modBaseInfo['regid']       = (int) $regid;
    $modBaseInfo['mode']        = (int) $mode;
    $modBaseInfo['systemid']    = (int) $systemid;
    $modBaseInfo['name']        = $name;
    $modBaseInfo['directory']   = $directory;
    $modBaseInfo['class']   = $class;
    $modBaseInfo['osdirectory'] = xarVarPrepForOS($directory);

    return ($modBaseInfo);
}
?>