<?php
/**
 * Upgrade a module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Upgrade a module
 *
 * Loads module admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 * @param id the module id to upgrade
 * @returns
 * @return
 */
function modules_admin_upgrade()
{
    //$success = true;

    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) {return;}
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('numitems', 'isset', $numitems,  80, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc', $sort, '',  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('order',    'str:0:', $order,    '', XARVAR_NOT_REQUIRED)) return;

    //First check the modules dependencies
    if (!xarMod::apiFunc('modules','admin','verifydependency',array('regid'=>$id))) {

        //Checking if the user has already passed thru the GUI:
        xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);
    } else {
        //No dependencies problems, jump dependency GUI
        $command = true;
    }

    if (!$command) {
        //Let's make a nice GUI to show the user the options
        $data = array();
        $data['id'] = (int) $id;
        //They come in 3 arrays: satisfied, satisfiable and unsatisfiable
        //First 2 have $modInfo under them foreach module,
        //3rd has only 'regid' key with the ID of the module

        // get any dependency info on this module for a better message if something is missing
        $thisinfo = xarMod::getInfo($id);

        if (isset($thisinfo['dependencyinfo'])) {
            $data['dependencyinfo'] = $thisinfo['dependencyinfo'];
        } else {
            $data['dependencyinfo'] = array();
        }

        $data['authid']       = xarSecGenAuthKey();
        $data['dependencies'] = xarMod::apiFunc('modules','admin','getalldependencies',array('regid'=>$id));
        $data['displayname'] = $thisinfo['displayname'];
        $data['homepage'] = isset($thisinfo['homepage'])?$thisinfo['homepage']:'';
        return $data;
    }

    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    $success = true;
    $minfo=xarMod::getInfo($id);
    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_UPGRADED) {
        // Upgrade module
        $upgraded = xarMod::apiFunc(
            'modules', 'admin', 'upgrade',
            array('regid' => $id)
        );

        // Don't throw back - handle it here.
        // Bug 1222: check for exceptions in the exception stack.
        // If there are any, then return NULL to display them (even if
        // the upgrade worked).
        if(!isset($upgraded)) {
            // Flag a failure.
            $success = false;
        }

        // Bug 1669
        // Also check if module upgrade returned false
        if (!$upgraded) {
            $msg = xarML('Module failed to upgrade');
            //xarErrorSet(XAR_SYSTEM_EXCEPTION, 'SYSTEM_ERROR',
            //                new SystemException($msg));
            // Flag a failure.
            $success = false;
        }
    }

    if (!$success) {
        // Upgrade failed
        // Send the full error stack to the upgrade template for rendering.
        // (The hope is that all errors can be rendered like this eventually)

            //Let's make a nice GUI to show the user the options
            $data = array();
            $data['id'] = (int) $id;

            $data['displayname'] = $minfo['name'];
             $data['homepage'] = isset($minfo['homepage'])?$minfo['homepage']:'';
            // Return the stack for rendering.
            $data['errorstack'] = $errorstack;
            return $data;

    }

    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    // The module might have new or updated properties, after upgrading, flush the
    // property cache otherwise you will get errors on displaying the property.
    if(!xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush' => true))) {
        return false; //FIXME: Do we want an exception here if flushing fails?
    }
     $actionargs = array();
    $actionargs['id'] = $id;
    $actionargs['authid'] = xarSecGenAuthKey();
    $actionargs['state']= 0;
    if (isset($startnum)) $actionargs['startnum']= $startnum;
    if (isset($numitems)) $actionargs['numitems']= $numitems;
    if (isset($order)) $actionargs['order']= $order;
    if (isset($sort)) $actionargs['sort']= $sort;

  xarLogMessage('MODULES: Module with Registered ID '.$id.' was upgraded by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    // it certainly depends on the implementation of xarModUrl
    xarResponseRedirect(xarModURL('modules', 'admin', 'list', $actionargs, NULL, $target));

    return true;
}

?>
