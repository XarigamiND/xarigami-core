<?php
/**
 * Modify module properties
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C)2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Modify module properties from information in the xarversion file
 *
 * This function queries the database for
 * the module's information
 * and passes the data to the template.
 *
 * @author Xarigami Development Team
 * @param id registered module id
 * @param return_url optional return URL after setting the hooks
 * @return array an array of variables to pass to the template
 */
function modules_admin_modifyproperties($args)
{
    extract($args);

    // xarVarFetch does validation if not explicitly set to be not required
    xarVarFetch('id','id',$id);
    xarVarFetch('details','str:0:1',$details,0,XARVAR_NOT_REQUIRED);
    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);

    $modInfo = xarMod::getInfo($id);
    if (!isset($modInfo)) return;

    $modName     = $modInfo['name'];

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"$modName::$id")) return xarResponseForbidden();

    $displayName = $modInfo['displayname'];
    $data['admincapable'] = $modInfo['admincapable'];
    $data['usercapable'] = $modInfo['usercapable'];
    $filesettings = xarMod::getFileInfo($modName);
    $data['adminallowed'] = $filesettings['admin'];
    $data['userallowed'] = $filesettings['user'];


    $data['savechangeslabel'] = xarML('Save Changes');
    if ($details) {
        $data['DetailsLabel'] = xarML('Hide Details');
        $data['DetailsURL'] = xarModURL('modules','admin','modify',
                                        array('id' => $id));
    } else {
        $data['DetailsLabel'] = xarML('Show Details');
        $data['DetailsURL'] = xarModURL('modules','admin','modify',
                                        array('id' => $id, 'details' => true));
    }

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

    // End form
    $data['details'] = $details;
    $data['authid'] = xarSecGenAuthKey('modules');
    $data['id'] = $id;
    $data['displayname'] = $modInfo['displayname'];
    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    return $data;
}

?>