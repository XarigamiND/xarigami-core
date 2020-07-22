<?php
/**
 * Deactivate a module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Deactivate a module
 *
 * @author Xarigami Development Team
 * Loads module admin API and calls the setstate
 * function to actually perfrom the deactivation,
 * then redirects to the list function with a status
 * message and returns true.
 *
 * @access public
 * @param id the module id to deactivate
 * @return bool true on success
 */
function modules_admin_deactivate ()
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

    // set the target location (anchor) to go to within the page
    $minfo=xarMod::getInfo($id);
    $target=$minfo['name'];
    $actionargs = array();
    $actionargs['id'] = $id;
    $actionargs['authid'] = xarSecGenAuthKey();
    $actionargs['state']= 0;
    if (isset($startnum)) $actionargs['startnum']= $startnum;
    if (isset($numitems)) $actionargs['numitems']= $numitems;
    if (isset($order)) $actionargs['order']= $order;
    if (isset($sort)) $actionargs['sort']= $sort;
    // If we haven't been to the deps GUI, check that first
    if (!$command) {
        //First check the modules dependencies
        // FIXME: double check this line and the line with deactivatewithdependents below,
        // they can NOT be called in the same request due to the statics used in there, the logic
        // needs to be reviewed, it's not solid enough.
        $dependents = xarMod::apiFunc('modules','admin','getalldependents',array('regid'=>$id));

        if(count($dependents['active']) > 1) {
            //Let's make a nice GUI to show the user the options
            $data = array();
            $data['id'] = $id;
            //They come in 2 arrays: active, initialised
            //Both have $name => $modInfo under them foreach
            $data['authid']       = xarSecGenAuthKey();
            $data['dependencies'] = $dependents;
            return $data;
        } else {
            // No dependents, we can deactivate the module
            if(!xarMod::apiFunc('modules','admin','deactivate',array('regid' => $id)))  return;

            xarResponseRedirect(xarModURL('modules', 'admin', 'list', $actionargs, NULL, $target));
        }
    }

    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_ACTIVE) {
        //Deactivate with dependents, first dependents
        //then the module itself
        if (!xarMod::apiFunc('modules','admin','deactivatewithdependents',array('regid'=>$id))) {
            //Call exception
            return;
        } // Else
    }


    xarResponseRedirect(xarModURL('modules', 'admin', 'list', $actionargs, NULL, $target));

    return true;
}

?>