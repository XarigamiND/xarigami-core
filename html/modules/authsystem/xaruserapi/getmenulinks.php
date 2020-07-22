<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects
 * @author Xarigami Team
 */

/**
 * Utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @return array containing the menulinks for the main menu items.
 */
function authsystem_userapi_getmenulinks()
{

    $menulinks[] = array('url' => xarModURL('authsystem','user','showloginform'),
        /* In order to display the tool tips and label in any language,
         * we must encapsulate the calls in the xarML in the API.
         */
        'title' => xarML('Login'),
        'label' => xarML('Login'),
        'active' => array('showloginform')

        );
    if (empty($menulinks)) {
        $menulinks = '';
    }
    return $menulinks;
}
?>