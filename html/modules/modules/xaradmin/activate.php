<?php
/**
 * Activate a module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * Activate a module
 *
 * @author Xarigami Development Team
 * Loads module admin API and calls the activate
 * function to actually perform the activation,
 * then redirects to the list function with a
 * status message and returns true.
 *
 * @param id the module id to activate
 * @return bool true on success
 */
function modules_admin_activate()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('numitems', 'isset', $numitems,  80 , XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc', $sort, '',  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('order',    'str:0:', $order,    '', XARVAR_NOT_REQUIRED)) return;

    // Activate
    $activated = xarMod::apiFunc('modules',
                              'admin',
                              'activate',
                              array('regid' => $id));

    //throw back
    if (!isset($activated)) return;
    $minfo=xarMod::getInfo($id);
    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];
    $authid       = xarSecGenAuthKey();
    $actionargs['id'] = $id;
    $actionargs['authid'] = $authid;
    $actionargs['state']= 0;
    if (isset($startnum)) $actionargs['startnum']= $startnum;
    if (isset($numitems)) $actionargs['numitems']= $numitems;
    if (isset($order)) $actionargs['order']= $order;
    if (isset($sort)) $actionargs['sort']= $sort;
    xarResponseRedirect(xarModURL('modules', 'admin', 'list', $actionargs, NULL, $target));

    return true;
}

?>