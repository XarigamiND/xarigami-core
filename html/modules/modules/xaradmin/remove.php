<?php
/**
 * Remove a module
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
 * Remove a module
 *
 * Loads module admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @author Xarigami Development Team
 * @access public
 * @param  id the module id
 * @returns mixed
 * @return true on success
 */

// Remove/Deactivate/Install GUI functions are basically copied and pasted versions...
// Refactor later on
function modules_admin_remove ()
{
     // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('numitems', 'isset', $numitems,  80 , XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc', $sort, '',  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('order',    'str:0:', $order,    '', XARVAR_NOT_REQUIRED)) return;

    //Checking if the user has already passed thru the GUI:
    xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);

    $minfo=xarMod::getInfo($id);

    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];
    $actionargs = array();
    $actionargs['id'] = $id;
    $actionargs['authid'] = xarSecGenAuthKey();
    $actionargs['state']= 0;
    if (isset($startnum)) $actionargs['startnum']= $startnum;
    if (isset($numitems)) $actionargs['numitems']= $numitems;
    if (isset($order)) $actionargs['order']= $order;
    if (isset($sort)) $actionargs['sort']= $sort;
    if(!$command) {
        // not been thru gui yet, first check the modules dependencies
        // FIXME: double check this line and the line with removeewithdependents below,
        // they can NOT be called in the same request due to the statics used in there, the logic
        // needs to be reviewed, it's not solid enough.
        $dependents = xarMod::apiFunc('modules','admin','getalldependents',array('regid'=>$id));
        if (!(count($dependents['active']) > 0 || count($dependents['initialised']) > 1 )) {
            //No dependents, just remove the module
            if(!xarMod::apiFunc('modules','admin','remove',array('regid' => $id)))  return;
            xarResponseRedirect(xarModURL('modules', 'admin', 'list', $actionargs, NULL, $target));
        } else {
            // There are dependents, let's build a GUI
            $data                 = array();
            $data['id']           = $id;
            $data['authid']       = xarSecGenAuthKey();
            $data['dependencies'] = $dependents;
            return $data;
        }
    }

    // User has seen the GUI
    // Removes with dependents, first remove the necessary dependents then the module itself
    if (!xarMod::apiFunc('modules','admin','removewithdependents',array('regid'=>$id))) {
        //Call exception
        xarLogMessage('Missing module since last generation!');
        return;
    } // Else


    xarResponseRedirect(xarModURL('modules', 'admin', 'list', $actionargs, NULL, $target));

    // Never reached
    return true;
}

?>