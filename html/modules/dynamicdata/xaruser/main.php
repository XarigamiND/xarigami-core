<?php
/**
 * Lists available objects defined in DD
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2013 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * the main user function lists the available objects defined in DD
 *
 */
function dynamicdata_user_main()
{
// Security Check
    if(!xarSecurityCheck('ViewDynamicData',0)) return xarResponseForbidden();

    // Add the user menu to the data array
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','user','getmenulinks');

    if (!xarMod::apiLoad('dynamicdata','user')) return;

    // get items from the objects table
    $objects = xarMod::apiFunc('dynamicdata','user','getobjects');

    $data['items'] = array();
    $mymodid = xarMod::getId('dynamicdata');
    $sysobs= xarModGetVar('dynamicdata', 'systemobjects');
    try {
            $sysobs = @unserialize($sysobs);
        } catch (Exception $e) {
            $sysobs = array();
        }

    foreach ($objects as $item=> $object) {
        // skip the internal objects
        $itemtype = $object['itemtype'];
        $itemid = $object['objectid'];
        if ($itemid < 3) continue; //jojo - fix this hard coded object item issue
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

        $label = $object['label'];

        $data['items'][] = array(
                                 'link'     => xarModURL('dynamicdata','user','view',
                                                         array('modid' => $modid,
                                                               'itemtype' => empty($itemtype) ? null : $itemtype)),
                                 'label'    => $label,
                                 'itemtype' => $itemtype,
                                 'itemid'   =>$itemid
                                );
    }

    $data['count'] = count($data['items']);
    return $data;
}

?>