<?php
/**
 * Utility to get menu links
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Dynamic Data module development team
 * @return array containing the menulinks for the main menu items.
 */
function dynamicdata_adminapi_getmenulinks()
{
    $menulinks = array();

    if (xarSecurityCheck('ModerateDynamicData',0)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata', 'admin','view'),
                              'title' => xarML('View module objects using dynamic data'),
                              'label' => xarML('Manage Data Objects'),
                              'active' => array('view','new','modify','modifyprop','showpropval','delete','display','form'),
                              'activelabels' => array('',xarML('New item'),xarML('Modify item'),xarML('Modify properties'),xarML('Edit validation'),xarML('Delete item'),xarML('Display'),'')
                              );
    }
    if (xarSecurityCheck('AdminDynamicData',0)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata','admin','manageproplist'),
                              'title' => xarML('Mange the default dynamic data property types'),
                              'label' => xarML('Manage Data Properties'),
                              'active' => array('manageproplist')
                              );
        $menulinks[] = Array('url'   => xarModURL('dynamicdata','admin','utilities'),
                              'title' => xarML('Import/export and other utilities'),
                              'label' => xarML('Utilities'),
                              'active' => array('utilities','export','query','import','relations','migrate'),
                              'activelabels' => array('',xarML('Export data'),xarML('Query tables and objects'),xarML('Import data'),xarML('Relationships'),xarML('Migrate data'))
                              );
        $menulinks[] = Array('url'   => xarModURL('dynamicdata','util','meta'),
                              'title' => xarML('Table operations'),
                              'label' => xarML('Table operations'),
                              'active' => array('meta','statictabledelete','statictablerename','statictablenew','staticfieldnew','staticfieldedit','staticfielddelete','static'),
                              'activelabels' => array('',xarML('Delete table'),xarML('Rename table'),xarML('Create new table'),xarML('New column'),xarML('Edit column'),xarML('Delete column'))
                              );
            $menulinks[] = Array('url'   => xarModURL('dynamicdata','admin','modifyconfig'),
                              'title' => xarML('Modify general configuration'),
                              'label' => xarML('Modify Config'),
                              'active' => array('modifyconfig')
                              );

    }
    return $menulinks;
}
?>