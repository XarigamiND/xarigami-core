<?php
/**
 * Initialise a module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2009-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Initialise a module
  * @param regid registered module id
 * @returns bool
 * @return
 * @throws BAD_PARAM, MODULE_NOT_EXIST
 */
function modules_adminapi_initialise($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
       $msg = xarML('Missing module regid (#(1)).', $regid);
         throw new EmptyParameterException('regid',$msg);
    }

    // Get module information
    $modInfo = xarMod::getInfo($regid);
    if (!isset($modInfo)) {
          throw new ModuleNotFoundException($regid);
    }
    //Checks module dependency
    if (!xarMod::apiFunc('modules','admin','verifydependency',array('regid'=>$regid))) {
        $msg = xarML('The dependencies to initialise the module "#(1)" were not met.', $modInfo['displayname']);
        throw new Exception($msg);
    }
    $dipslayname = $modInfo['displayname'];
    // Module install
    if (!xarMod::apiFunc('modules','admin','executeinitfunction',
                       array('regid'    => $regid,
                             'function' => 'init'))) {
        $msg = xarML("There was a problem in executeinitfunction for module $displayname with id $regid");
        //Raise an Exception
        throw new Exception($msg);
    }

    // Update state of module
    $set = xarMod::apiFunc('modules', 'admin','setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));


    if (!isset($set)) {
        $msg = xarML('Module state change failed');
        throw new Exception($msg);
    }
    // Success
    return true;
}
?>