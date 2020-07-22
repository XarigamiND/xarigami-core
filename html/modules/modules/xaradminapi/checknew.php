<?php
/**
 * Checks for new modules added to the filesystem
 *
 * @copyright (C) 2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Checks for new modules added to the filesystem, and adds any found to the database
 *
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @throws NO_PERMISSION
 */
function modules_adminapi_checknew()
{
    static $check = false;
    if ($check) return true;

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // Get all modules in the filesystem
    $fileModules = xarMod::apiFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Get all modules in DB
    $dbModules = xarMod::apiFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    //Setup database object for module insertion
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $modules_table = $xartable['modules'];

    // See if we have gained any modules since last generation,
    foreach ($fileModules as $name => $modinfo) {
        // Check matching name and regid values
        foreach ($dbModules as $dbmodule) {
            // Bail if 2 modules have the same regid but not the same name
            if (($modinfo['regid'] == $dbmodule['regid']) &&
               ($modinfo['name'] != $dbmodule['name'])) {
                $msg = xarML('The same registered ID (#(1)) was found belonging to a #(2) module in the file system and a registered #(3) module in the database. Please correct this and regenerate the list.', $dbmodule['regid'], $modinfo['name'], $dbmodule['name']);
                throw new DuplicateException(array('regid',$modinfo['regid']),$msg);
                return;
            }

            // Bail if 2 modules have the same name but not the same regid
            if (($modinfo['name'] == $dbmodule['name']) &&
               ($modinfo['regid'] != $dbmodule['regid'])) {
                $msg = xarML('The module #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.', $modinfo['name'], $modinfo['regid'], $dbmodule['regid']);
                 throw new DuplicateException(array($modinfo['name'],$modinfo['id'],$dbmodule['regid']), $msg);
                return;
            }
        }

        // If this is a new module, i.e. not in the db list, add it
        assert('$modinfo["regid"] != 0; /* Reg id for the module is 0, something seriously wrong, probably corruption of files */');
        if (empty($dbModules[$name])) {
            xarLogMessage("modules-checknew: adding new module ".$name);
            // New module - might as well set the status here
            $modId = $dbconn->GenId($modules_table);
            $sql = "INSERT INTO $modules_table
                      (xar_id,
                       xar_name,
                       xar_regid,
                       xar_directory,
                       xar_version,
                       xar_mode,
                       xar_class,
                       xar_category,
                       xar_admin_capable,
                       xar_user_capable,
                       xar_state)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

            $params = array(
                $modId,
                $modinfo['name'],
                (int)$modinfo['regid'],
                $modinfo['directory'],
                $modinfo['version'],
                (int)$modinfo['mode'],
                $modinfo['class'],
                $modinfo['category'],
                (int)$modinfo['admin_capable'],
                (int)$modinfo['user_capable'],
                 XARMOD_STATE_UNINITIALISED
            );

            $result = $dbconn->Execute($sql, $params);

            if (!$result) return;

            // @TODO: check core dependency here?
           /* $set = xarMod::apiFunc(
                'modules', 'admin', 'setstate',
                array(
                    'regid' => $modinfo['regid'],
                    'state' => XARMOD_STATE_UNINITIALISED
                )
            );
            if (!isset($set)) {return;}
            */
        }
    }
    $check = true;
    return true;
}
?>