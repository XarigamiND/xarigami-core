<?php
/**
 * Utility function to pass individual menu items
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Jim McDonald, Paul Rosania
 * @return array containing the menulinks for the main menu items.
 */
function blocks_adminapi_getmenulinks()
{
    $menulinks = array();
    if (xarSecurityCheck('EditBlock',0,'Block', 'All:All:All')) {
 
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'view_instances'),
            'title' => xarML('View and edit all block instances'),
            'label' => xarML('List blocks'),
            'active' => array('view_instances','modify_instance','delete_instance'),
            'activelabels' => array('',xarML('Modify block'),xarML('Delete block'))
        );
    }
    if (xarSecurityCheck('AddBlock',0,'Block', 'All:All:All')) {
 
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'new_instance'),
            'title' => xarML('Add a new block instances'),
            'label' => xarML('Add new block'),
            'active' => array('new_instance'),
            'activelabels' => array('',xarML('Add new block'))
        );
    }
    if (xarSecurityCheck('EditBlockGroup',0,'BlockGroup', 'All:All')) {    
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'view_groups'),
            'title' => xarML('View the defined block groups'),
            'label' => xarML('List Block Groups'),
            'active' => array('new_group','modify_group','delete_group','view_groups'),
            'activelabels' => array(xarML('Add block group'),xarML('Modify block group'),xarML('Delete block group'),'')           
        );
    }

    if (xarSecurityCheck('EditBlock', 0)) {
        $menulinks[] = array(
            'url'   => xarModURL('blocks', 'admin', 'view_types'),
            'title' => xarML('View block types'),
            'label' => xarML('View Block Types'),
            'active' => array('view_types','new_type','update_type_info','delete_type'),
            'activelabels' => array('',xarML('Add block type'),xarML('Modify block type'),xarML('Delete Block type'))                            
        );

    if (xarSecurityCheck('AdminBlock',0,'Block', 'All:All:All')) {        
        $menulinks[] = array(
            'url'   => xarModURL('blocks','admin','modifyconfig'),
            'title' => xarML('Modify Blocks configuration values'),
            'label' => xarML('Modify Config'),
            'active' => array('modifyconfig')            
        );
    }
    }
    return $menulinks;
}

?>