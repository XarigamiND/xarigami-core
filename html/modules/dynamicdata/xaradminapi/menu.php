<?php
/**
 * Generate a common admin menu
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * @ DEPRECATED march 09
 * generate the common admin menu configuration
 * @return array
 */
function dynamicdata_adminapi_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();
    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarML('Dynamic Data Administration');
    // Preset some status variable
    $menu['status'] = '';
    // Return the array containing the menu configuration
    return $menu;
}
?>