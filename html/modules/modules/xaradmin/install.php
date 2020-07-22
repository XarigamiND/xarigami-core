<?php
/**
 * Installs a module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Installs a module
 *

 * Loads module admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * @author Xarigami Development Team
 *
 * @param int id the module id to initialise
 * @return bool true on success
 */
function modules_admin_install()
{
    // Security and sanity checks
    //jojo - this sec check is not going to work and will raise an error on second return
    //review this when time
   // if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('numitems', 'isset', $numitems,  80, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc', $sort, '',  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('order',    'str:0:', $order,    '', XARVAR_NOT_REQUIRED)) return;
    //First check the modules dependencies
    $dependencyok = false;
    $dependencyok = xarMod::apiFunc('modules','admin','verifydependency',array('regid'=>$id));
    if (!$dependencyok || is_array($dependencyok)) {
        //Oops, we got problems...
        //Checking if the user has already passed thru the GUI:
        xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);
    } else {
        //No dependencies problems, jump dependency GUI
        $command = TRUE;
    }

    if ($command !== TRUE) {
        //Let's make a nice GUI to show the user the options
        $data = array();
        if (isset($dependencyok) && is_array($dependencyok)) {
            $data= $dependencyok;

        } // else {

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

            $data['errorstack'] = isset($errorstack)?$errorstack:'';
            $data['authid']       = xarSecGenAuthKey();
            $data['dependencies'] = !isset($data['dependencies']) || empty($data['dependencies'])?xarMod::apiFunc('modules','admin','getalldependencies',array('regid'=>$id)) : $data['dependencies'];
            $data['displayname'] = $thisinfo['displayname'];
            $data['homepage'] = isset($thisinfo['homepage'])?$thisinfo['homepage']:'';
      //  }

        return $data;
    }

    // See if we have lost any modules since last generation
    $checkmissing = xarMod::apiFunc('modules', 'admin', 'checkmissing');
    if (!$checkmissing) {
        return;
    }

    $minfo=xarMod::getInfo($id);

    //Bail if we've lost our module
    //jojo - TODO - review this and handle appropriately  - not handled yet as required
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_INACTIVE) {
        //Installs with dependencies, first initialise the necessary dependecies
        //then the module itself
        $testdependency = xarMod::apiFunc('modules','admin','installwithdependencies',array('regid'=>$id));

    }

    if (!$testdependency || is_array($testdependency)) {

        //Let's make a nice GUI to show the user the options
        $data = array();
        if (isset($testdependency) && is_array($testdependency)) {
            $data = $testdependency;
        }  else {
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
            $data['homepage'] = isset($thisinfo['homepage'])?$thisinfo['homepage']:'';
            $data['errorstack'] = isset($errorstack)?$errorstack:'';
            $data['authid']       = xarSecGenAuthKey();
            $data['dependencies'] = xarMod::apiFunc('modules','admin','getalldependencies',array('regid'=>$id));
            $data['displayname'] = $thisinfo['displayname'];
        }
        return $data;
     }
    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];
    if (function_exists('xarOutputFlushCached')) {
        xarOutputFlushCached('base');
        xarOutputFlushCached('base-block');
    }

    // The module might have properties, after installing, flush the property cache otherwise you will
    // get errors on displaying the property.
    // jojo - there might be problems with properties here. We need to allow most properties to load
    //if there is an error , return the error and let the user correct it
    //we do not need the list of properties here, we just need to flush it (Why? can't we just load the one module prop?)

    $flushcache = xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush' => true,'returnerrors'=>true));
    xarLogMessage('MODULES: Module with Registered ID '.$id.' was installed by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    $actionargs = array();
        $actionargs['id'] = $id;
        $actionargs['authid'] = xarSecGenAuthKey();
        $actionargs['state']= 0;
        $actionargs['invalid']= $flushcache;
        if (isset($startnum)) $actionargs['startnum']= $startnum;
        if (isset($numitems)) $actionargs['numitems']= $numitems;
        if (isset($order)) $actionargs['order']= $order;
        if (isset($sort)) $actionargs['sort']= $sort;

    xarResponseRedirect(xarModURL('modules', 'admin', 'list', $actionargs, NULL, $target));

    return true;
}

?>
