<?php
/**
 * Activate a module
 *
 * @package modules
 * @copyright (C) 2005-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Activate a module if it has an active function, otherwise just set the state to active
 *
 * @author Xarigami Development Team
 * @access public
 * @param int regid module's registered id
 * @return bool true on successful activation
 * @throws BAD_PARAM
 */
function modules_adminapi_activate($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $modInfo = xarMod::getInfo($regid);
    $modname = $modInfo['displayname'];

    // Module activate function
    if (!xarMod::apiFunc('modules', 'admin','executeinitfunction',
                 array('regid'    => $regid,
                       'function' => 'activate'))) {
        $msg = xarML('Unable to execute "activate" function in the xarinit.php file of module (#(1))', $modInfo['displayname']);
        throw new Exception($msg);
    }

    // Update state of module
    $res = xarMod::apiFunc('modules','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_ACTIVE));

    if (function_exists('xarOutputFlushCached') && function_exists('xarModGetName') && xarMod::getName() != 'installer') {
        xarOutputFlushCached('base');
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }
    xarLogMessage('MODULES: Module with Registered ID '.$regid.' was activated by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}
?>