<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects
 * @author Xarigami Team
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @return array containing the menulinks for the main menu items.
 */
function authsystem_adminapi_getmenulinks()
{
    $menulinks = array();

    if (xarSecurityCheck('AdminAuthsystem',0)) {
        $menulinks[] = Array('url'   => xarModURL('authsystem','admin','modifyconfig'),
                              'title' => xarML('Modify the Authsystem authentication configuration'),
                              'label' => xarML('Modify Config'),
                              'active' => array('modifyconfig'),
                              );
    }
    return $menulinks;
}

?>