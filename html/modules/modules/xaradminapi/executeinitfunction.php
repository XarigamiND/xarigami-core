<?php
/**
 * Loads xarinit or pninit and executes the given function
 *
 * @copyright (C) 2005-2008 The Digital Development Foundation
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Loads xarinit and executes the given function
 *
 * @param $args['regid'] the id of the module
 * @param $args['function'] name of the function to be called
 * @returns bool
 * @return true on success, false on failure in the called function
 * @throws BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_executeinitfunction ($args)
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Argument check
    if (!isset($args['regid'])) {
        $msg = xarML('Missing module regid.');
        throw new BadParameterException('regid',$msg);
    }

    // Get module information
    $modInfo = xarMod::getInfo($args['regid']);

    if (!isset($modInfo['osdirectory']) ||
        empty($modInfo['osdirectory']) ||
        !is_dir('modules/'. $modInfo['osdirectory'])) {

        $msg = xarML('Module (regid: #(1) - directory: #(2) does not exist.', $args['regid'], $modInfo['osdirectory']);
        throw new ModuleNotFoundException($args['regid'],$msg);
    }

    // Get module database info, they might be needed in the function to be called
    xarMod::loadDbInfo($modInfo['name'], $modInfo['osdirectory']);

    $xarinitfile = '';
    if (file_exists(sys::code().'modules/'. $modInfo['osdirectory'] .'/xarinit.php')) {
        $xarinitfile = sys::code().'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    }

    if (empty($xarinitfile)) {
        /*
        $msg = xarML('No Initialization File Found for Module "#(1)"', $modInfo['name']);
        throw new ModuleNotFoundException($args['regid'],$msg);
        */
        $func = isset($func)?$func:'';
        xarlogMessage("executeinitfunction file '$func' not found, skipping");
        //Return gracefully
        return true;
    }

    ob_start();
    $r = sys::import('modules.'.$modInfo['osdirectory'].'.xarinit');
    $error_msg = strip_tags(ob_get_contents());
    ob_end_clean();

    if (empty($r) || !$r) {
        $msg = xarML("Could not load file: [#(1)].\n\n Error Caught:\n #(2)", $xarinitfile, $error_msg);
        throw new ModuleNotFoundException($args['regid'],$msg);
    }

    if (!xarMLSLoadTranslations($xarinitfile)) {
        return;
    }

    $func = $modInfo['name'] . '_'.$args['function'];
    $funcname = $args['function'];

    if (function_exists($func)) {
        xarlogMessage("executeinitfunction $func");

        if ($args['function'] == 'upgrade') {
            // pass the old version as argument to the upgrade function
            $result = $func($modInfo['version']);
        } else {
            $result = $func();
        }

        xarlogMessage("executeinitfunction \$result = $result");

        if ($result === false) {
            $msg = xarML('While changing state of the #(1) module, the function #(2) returned a false value when executed.', $modInfo['name'], $func);
             throw new Exception($msg);
        } elseif ($result != true) {
            $msg = xarML('An error ocurred while changing state of the #(1) module, executing function #(2)', $modInfo['name'], $func);
             throw new Exception($msg);
        }
        //enforce init function but not others
    } elseif ($funcname =='init') {
        // A lot of init files dont have the function, mainly activate...
        // Should we enforce them to have it?
        xarlogMessage("executeinitfunction function '$funcname' not found, skipping");
        // file exists, but function not found. Exception!
        $msg = xarML('Module initialisation failed because the module did not include a function: #(1) ',  $funcname);
        throw new Exception($msg);
    } else {
        $modname= $modInfo['name'];
        xarlogMessage("executeinitfunction function '$funcname' not found for module '$modname', skipping");
    }

    return true;
}

?>