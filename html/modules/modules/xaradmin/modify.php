<?php
/**
 * Modify module settings
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
 * Modify module settings
 *
 * This function queries the database for
 * the module's information and then queries
 * for any hooks that the module could use
 * and passes the data to the template.
 *
 * @author Xarigami Development Team
 * @param id registered module id
 * @param string return_url optional return URL after updating the hooks
 * @return array an array of variables to pass to the template
 */
function modules_admin_modify($args)
{
    extract($args);

    // xarVarFetch does validation if not explicitly set to be not required
    xarVarFetch('id','id',$id);
    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);

    $modInfo = xarMod::getInfo($id);
    if (!isset($modInfo)) return;
    $state = $modInfo['state'];
    //perhaps we want to disable hooks if the module is inactive not just available
    //if (!xarMod::isAvailable($modInfo['name'])) {
    if ($state != XARMOD_STATE_INACTIVE and $state != XARMOD_STATE_ACTIVE) {//ACTIVE or INACTIVE
         $msg = xarML('Cannot modify hooks, module #(1) is unavailable',$modInfo['name']);
        throw new ModuleNotFoundException($modInfo['name']);
    }

    $modName     = $modInfo['name'];
    $displayName = $modInfo['displayname'];

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"$modName::$id")) return;

    $data['savechangeslabel'] = xarML('Save Changes');

    // Get the list of all hook modules, and the current hooks enabled for this module
    $hooklist = xarMod::apiFunc('modules','admin','gethooklist',
                              array('modName' => $modName));

    // Get the list of all item types for this module (if any)
    $itemtypes = xarMod::apiFunc($modName,'user','getitemtypes',
                               // don't throw an exception if this function doesn't exist
                               array(), 0);
    if (isset($itemtypes)) {
        $data['itemtypes'] = $itemtypes;
    } else {
        $data['itemtypes'] = array();
    }

    // $data[hooklist] is the master array which holds all info
    // about the registered hooks.
    $data['hooklist'] = array();

    // Loop over available $key => $value pairs in hooklist
    // $modname is assigned key (name of module)
    // $hooks is assigned object:action:area
    // MrB: removed the details check, it's simpler to have the same datastructure
    // allways, and I think there's not much of a performance hit.
    // TODO: make the different hooks selectable per type of hook
    foreach ($hooklist as $hookmodname => $hooks) {
        $data['hooklist'][$hookmodname]['modname'] = $hookmodname;
        $data['hooklist'][$hookmodname]['checked'] = array();
        $data['hooklist'][$hookmodname]['hooks'] = array();
        // Fill in the details for the different hooks
        foreach ($hooks as $hook => $modules) {
            if (!empty($modules[$modName])) {
                foreach ($modules[$modName] as $itemType => $val) {
                    $data['hooklist'][$hookmodname]['checked'][$itemType] = 1;
                }
            }
            $data['hooklist'][$hookmodname]['hooks'][$hook] = 1;
        }
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('modules','admin','getmenulinks');

    $data['authid'] = xarSecGenAuthKey('modules');
    $data['id'] = $id;
    $data['displayname'] = $modInfo['displayname'];
    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    return $data;
}

?>