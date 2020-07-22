<?php
/**
 * Utility function to retrieve the list of item types
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @return array containing the item types and their description
 */
function blocks_userapi_getitemtypes($args)
{
    $itemtypes = array();

    if (xarSecurityCheck('EditBlock',0)) {
        $showurl = true;
    } else {
        $showurl = false;
    }

    $name = xarML('Block Types');
    $itemtypes[1] = array('label' => xarVarPrepForDisplay($name),
                          'title' => xarVarPrepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarModURL('blocks','admin','view_types') : ''
                         );

    $name = xarML('Block Groups');
    $itemtypes[2] = array('label' => xarVarPrepForDisplay($name),
                          'title' => xarVarPrepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarModURL('blocks','admin','view_groups') : ''
                         );

    $name = xarML('Block Instances');
    $itemtypes[3] = array('label' => xarVarPrepForDisplay($name),
                          'title' => xarVarPrepForDisplay(xarML('Display #(1)',$name)),
                          'url'   => $showurl ? xarModURL('blocks','admin','view_instances') : ''
                         );

    return $itemtypes;
}

?>