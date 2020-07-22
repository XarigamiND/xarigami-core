<?php
/**
 * Import the dynamic properties
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
 * Import the dynamic properties for a module + itemtype from a static table
 */
function dynamicdata_util_importprops()
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}

    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'util', 'importprop', 'dynamicdata');
        throw new BadParameterException(null,$msg);
    }

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) return;

    if (!xarMod::apiFunc('dynamicdata','util','importproperties',
                       array('modid' => $modid,
                             'itemtype' => $itemtype,
                             'table' => $table,
                             'objectid' => $objectid))) {
        return;
    }

    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                  array('modid' => $modid,
                                        'itemtype' => $itemtype)));
}

?>