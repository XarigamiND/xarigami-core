<?php
/**
 * Get all modules in the database
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Get all modules in the database
 *
 * @param $args['regid'] - optional regid to retrieve
 * @returns array
 * @return array of modules in the database
 */
function modules_adminapi_getdbmodules($args)
{
    $dbconn = xarDB::$dbconn;
    // Get arguments
    extract($args);

    // Check for $regId
    $modregid = 0;
    if (isset($regId)) {
        $modregid = $regId;
    }

    $xartable = xarDBGetTables();

    $dbModules = array();

    // Get all modules in DB
    $sql = "SELECT xar_regid, xar_name, xar_directory, xar_class, xar_category, xar_version, xar_mode, xar_state, xar_admin_capable, xar_user_capable
              FROM $xartable[modules] ";

    if ($modregid) {
        $sql .= " WHERE xar_regid = $modregid";
    }

    $result = $dbconn->Execute($sql);
    if (!$result) return;

    while(!$result->EOF) {
        list($regid, $name, $directory, $class, $category, $version, $mode, $state, $admincapable, $usercapable) = $result->fields;

        // If returning one module, then push array without name index
        if ($modregid) {
            $dbModules = array('name'    => $name,
                               'regid'   => $regid,
                               'version' => $version,
                               'class'   => $class,
                               'category' => $category,
                               'mode'    => $mode,
                               'state'   => $state,
                               'directory'=>$directory,
                               'admin_capable' => $admincapable,
                               'user_capable' => $usercapable
                                );
        } else {
            //Push it into array (should we change to index by regid instead?)
            $dbModules[$name] = array('name'    => $name,
                                      'regid'   => $regid,
                                      'version' => $version,
                                      'class'   => $class,
                                      'category' => $category,
                                      'mode'    => $mode,
                                      'state'   => $state,
                                      'directory'=>$directory,
                                      'admin_capable' => $admincapable,
                                      'user_capable' => $usercapable
                                      );
        }
        $result->MoveNext();
    }
    $result->Close();

    return $dbModules;
}

?>