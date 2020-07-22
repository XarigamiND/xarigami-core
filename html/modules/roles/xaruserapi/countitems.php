<?php
/**
 * Utility function to count the number of users
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * utility function to count the number of users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns integer
 * @return number of items held by this module
 */
function roles_userapi_countitems()
{
    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolestable = $xartable['roles'];

    // Get user
    $query = "SELECT COUNT(1)
            FROM $rolestable";
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

?>