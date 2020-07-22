<?php
/**
 * Upgrade a module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
 /**
 * Upgrade a module
 *
 * @author Xaraya Development Team
 * @param regid registered module id
 * @returns bool
 * @return
 * @throws BAD_PARAM
 */
function modules_adminapi_upgrade($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        throw new BadParameterException('regid',$msg);
    }

    // Get module information
    //jojo - do we need this here? it is also in the executeinitfunction
    $modInfo = xarMod::getInfo($regid);
    if (empty($modInfo)) {
        xarSession::setVar('errormsg', xarML('No such module'));
        return false;
    }

    // Module deletion function
    if (!xarMod::apiFunc('modules', 'admin', 'executeinitfunction',
                       array('regid'    => $regid,
                             'function' => 'upgrade'))) {
        //Raise an Exception
        return;
    }

    // Update state of module
    $res = xarMod::apiFunc('modules', 'admin', 'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));
                              
    if (!isset($res)) return;

    // Get the new version information...
    $modFileInfo = xarMod::getFileInfo($modInfo['osdirectory']);
    
    if (!isset($modFileInfo)) return;

    // Bug 1671 - Invalid SQL
    // If the module fields returned from xarMod::getFileInfo()
    // are set to false, then they must be set to  some valid value
    // or a SQL error will occur due to null and zero length fields. 
    if (!$modFileInfo['admin_capable'])
        $modFileInfo['admin_capable'] = 0;
    if (!$modFileInfo['user_capable'])
        $modFileInfo['user_capable'] = 0;
    if (!$modFileInfo['class'])
        $modFileInfo['class'] = 'Miscellaneous';
    if (!$modFileInfo['category'])
        $modFileInfo['category'] = 'Miscellaneous';

    // Note the changes in the database...
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $sql = "UPDATE $xartable[modules]
            SET xar_version = ?, xar_admin_capable = ?, xar_user_capable = ?,
                xar_class = ?, xar_category = ?
            WHERE xar_regid = ?";
    $bindvars = array($modFileInfo['version'], $modFileInfo['admin_capable'],
                      $modFileInfo['user_capable'],$modFileInfo['class'],
                      $modFileInfo['category'], $regid);
    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    // Message to display in the module list view (only for core modules atm)
    if(!xarSession::getVar('statusmsg')){
        if(substr($modFileInfo['class'], 0, 4)  == 'Core'){
            xarSession::setVar('statusmsg', $modInfo['name']);
        }
    } else {
        if(substr($modFileInfo['class'], 0, 4)  == 'Core'){
            xarSession::setVar('statusmsg', xarSession::getVar('statusmsg') . ', '. $modInfo['name']);
        }
    }
    // Success
    return true;
}

?>
