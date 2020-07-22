<?php
/**
 * utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Modules module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function modules_adminapi_getmenulinks()
{
    // Security Check
    $menulinks = array();
    if (xarSecurityCheck('AdminModules',0)) {

        // these links will only be shown to those who can admin the modules
        $menulinks[] = Array('url'  => xarModURL('modules','admin','list'),
                                'title' => xarML('View list of all installed modules on the system'),
                                'label' => xarML('View All'),
                                'active' => array('list','modifyproperties','modify','modinfonew'),
                                'activelabels' => array(xarML('List modules'),xarML('Modify properties'),xarML('Modify Hooks'),xarML('Module information'))                                 
                                );
        
        $menulinks[] = Array('url'  => xarModURL('modules','admin','hooks'),
                            'title' => xarML('Extend the functionality of your modules via hooks'),
                            'label' => xarML('Configure Hooks'),
                            'active' => array('hooks')                                
                            );
        
        
        $menulinks[] = Array('url'  => xarModURL('modules','admin','modifyconfig'),
                            'title' => xarML('Modify Configuration'),
                            'label' => xarML('Modify Config'),
                            'active' => array('modifyconfig')                                
                            );
    }
    return $menulinks;
}

?>