<?php
/**
 * Generate the common admin menu configuration
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * generate the common admin menu configuration
 */
function roles_adminapi_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();

    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarML('Roles Administration');

    // Preset some status variable
    $menu['status'] = '';

    // Return the array containing the menu configuration
    return $menu;
}

?>