<?php
/**
 * Configure hooks by hook module
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
 * Configure hooks by hook module
 *
 * @param $args['curhook'] current hook module (optional)
 * @param $args['return_url'] URL to return to after updating the hooks (optional)
 *
 */
function modules_admin_hooks($args)
{
// Security Check
    if(!xarSecurityCheck('AdminModules',0)) return xarResponseForbidden();

    if (!xarVarFetch('hook', 'isset', $curhook, '', XARVAR_NOT_REQUIRED)) {return;}
    extract($args);

    // Get the list of all hook modules, and the current hooks enabled for all modules
    $hooklist = xarMod::apiFunc('modules','admin','gethooklist');
    $data = array();
    $data['savechangeslabel'] = xarML('Save Changes');
    $data['hookmodules'] = array();
    $data['hookedmodules'] = array();
    $data['curhook'] = '';
    $data['hooktypes'] = array();
    $data['authid'] = '';

    // via arguments only, for use in BL tags :
    // <xar:module main="false" module="modules" type="admin" func="hooks" curhook="hitcount" return_url="$thisurl" />
    if (empty($return_url)) {
        $return_url = '';
    }
    $data['return_url'] = $return_url;

    if (!empty($curhook)) {
        // Get list of modules likely to be "interested" in hooks
        //$modList = xarModGetList(array('Category' => 'Content'));
        $modList = xarMod::apiFunc('modules', 'admin', 'getlist',
                          array('orderBy'     => 'category/name'));
        //throw back
        if (!isset($modList)) return;

        $oldcat = '';
        $catmodlist = array();

        for ($i = 0, $max = count($modList); $i < $max; $i++) {
            $modList[$i]['checked'] = '';
            $modcat = $modList[$i]['category'];
            if (!isset($catmodlist[$modcat])) {
                $catmodlist[$modcat] = array();
            }
            // Get the list of all item types for this module (if any)
            $itemtypes = xarMod::apiFunc($modList[$i]['name'],'user','getitemtypes',
                                       // don't throw an exception if this function doesn't exist
                                       array(), 0);
            if (isset($itemtypes)) {
                $modList[$i]['itemtypes'] = $itemtypes;
            } else {
                $modList[$i]['itemtypes'] = array();
            }

            $modList[$i]['checked'] = array();
            //$modList[$i]['links'] = array();
            foreach ($hooklist[$curhook] as $hook => $hookedmods) {
                if (!empty($hookedmods[$modList[$i]['name']])) {
                    foreach ($hookedmods[$modList[$i]['name']] as $itemType => $val) {
                        $modList[$i]['checked'][$itemType] = 1;
            /* Comment out code from changesdue merge
            BEGIN MODIF
            $modList[$i]['links'][$itemType] = xarModURL('modules','admin','modifyorder',
                                    array('modulename' => $curhook,
                            'modulehookedname' =>  $modList[$i]['name'],
                            'itemtype' => $itemType));
              END MODIF
            */
                    }
                    break;
                }
            }
            $catmodlist[$modList[$i]['category']][] = $modList[$i];
        }
        $data['curhook'] = $curhook;
        $data['hookedmodules'] = $catmodlist;
        $data['authid'] = xarSecGenAuthKey('modules');

        foreach ($hooklist[$curhook] as $hook => $hookedmods) {
            $data['hooktypes'][] = $hook;
        }
    }
    $dropdownmods = array();
    foreach ($hooklist as $hookmodname => $hooks) {

        // Get module display name
        $regid = xarMod::getId($hookmodname);
        $modinfo = xarMod::getInfo($regid);
        $data['hookmodules'][] = array('modid' => $regid,
                                       'modname' => $hookmodname,
                                       'modtitle' => $modinfo['description'],
                                       'modstatus' => xarMod::isAvailable($modinfo['name']),
                                       'modlink' => xarModURL('modules','admin','hooks',
                                                              array('hook' => $hookmodname)));

        $dropdownmods[$hookmodname] = ucfirst($hookmodname);
    }
    $data['dropdownmods'] = $dropdownmods;
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('modules','admin','getmenulinks');
    //return the output
    return $data;
}

?>