<?php
/**
 * Utility function to retrieve the list of item types of this module (if any)
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array containing the item types and their description
 */
function roles_userapi_getitemtypes($args)
{
    $itemtypes = array();

// TODO: use 1 and 2 instead of 0 and 1 for itemtypes - cfr. bug 3439

/* this is the default for roles at the moment - select ALL in hooks if you want this
    $itemtypes[0] = array('label' => xarML('Users'),
                          'title' => xarML('View Users'),
                          'url'   => xarModURL('roles','user','view')
                         );
*/
    $itemtypes[1] = array('label' => xarML('Groups'),
                          'title' => xarML('View Groups'),
                          'url'   => xarModURL('roles','user','viewtree')
                         );
    return $itemtypes;
}

?>
