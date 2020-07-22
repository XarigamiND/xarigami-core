<?php
/**
 * Get menu links
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the DynamicData module development team
 * @return array containing the menulinks for the main menu items.
 */
function dynamicdata_userapi_getmenulinks()
{
    $menulinks = array();
    $sysobs= xarModGetVar('dynamicdata', 'systemobjects');
    try {
            $sysobs = @unserialize($sysobs);
        } catch (Exception $e) {
            $sysobs = array();
        }

    if(xarSecurityCheck('ViewDynamicDataItems')) {

        // get items from the objects table
        $objects = xarMod::apiFunc('dynamicdata','user','getobjects');

        if (!isset($objects)) {
            return $menulinks;
        }
        $mymodid = xarMod::getId('dynamicdata');
        foreach ($objects as $item=> $object) {
            $itemid = $object['objectid'];
            $itemtype = $object['itemtype'];
            // skip the internal objects
            if ($itemid < 3) continue;

            if (!xarSecurityCheck('ViewDynamicDataItems',0, 'Item', "$mymodid:$itemtype:All"))  {
                continue; //don't show objects that people have no access to
            }

            $modid = $object['moduleid'];

            // don't show data "belonging" to other modules for now
            if ($modid != $mymodid) {
                continue;
            }
            // nice(r) URLs
            if ($modid == $mymodid) {
                $modid = null;
            }
            $itemtype = $object['itemtype'];
            if ($itemtype == 0) {
                $itemtype = null;
            }

            $label = $object['label'];
            $checkid = isset($object['objectid']) ?$object['objectid']:'';
            if (in_array($checkid,$sysobs) && !xarSecurityCheck('AdminDynamicDataItem',0,'Item',"$mymodid:$itemid:All"))  continue;
            if (xarSecurityCheck('ViewDynamicDataItems',0, 'Item', "$mymodid:$itemtype:All")) {
                $menulinks[] = Array('url'   => xarModURL('dynamicdata','user','view',
                                                      array('modid' => $modid,
                                                            'itemtype' => $itemtype)),
                                    'title' => xarML('View #(1)', $label),
                                    'label' => $label,
                                    'active' =>array('view','display'),
                                    'activelabels'=>array('',xarML('Display item')),
                                    'itemtype' => $itemtype

                                    );
            }
        }
    }
    return $menulinks;
}

?>