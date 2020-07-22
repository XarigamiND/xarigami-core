<?php
/**
 * View complete module information/details
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * View complete module information/details
 * function passes the data to the template
 * opens in new window when browser is javascript enabled
 * @author Xarigami Development Team
 * @access public
 * @param none
 * @returns array
 * @todo some facelift
 */
function modules_admin_modinfo()
{

    // Security check - not needed here, imo
    // we just show some info here, not changing anything
/*     if (!xarSecConfirmAuthKey()) return; */

    $data = array();

    if (!xarVarFetch('id', 'notempty', $id)) {return;}

    // obtain maximum information about module
    $modinfo = xarMod::getInfo($id);

    // data vars for template
    $data['modid']              = xarVarPrepForDisplay($id);
    $data['modname']            = xarVarPrepForDisplay($modinfo['name']);
    $data['moddescr']           = xarVarPrepForDisplay($modinfo['description']);
    $data['moddispname']        = xarVarPrepForDisplay($modinfo['displayname']);
    $data['moddispdesc']        = xarVarPrepForDisplay($modinfo['description']);
    $data['modlisturl']         = xarModURL('modules', 'admin', 'list');
    $data['moddir']             = $modinfo['directory'];
    $data['modclass']           = xarVarPrepForDisplay($modinfo['class']);
    $data['modcat']             = xarVarPrepForDisplay($modinfo['category']);
    $data['modver']             = xarVarPrepForDisplay($modinfo['version']);
    $data['modauthor']          = xarVarPrepForDisplay($modinfo['author']);
    $data['modcontact']         = xarVarPrepForDisplay($modinfo['contact']);
    $data['homepage']           = !empty($modinfo['homepage'])? $modinfo['homepage']:'http://xarigami.com';
    // check for proper icon, if not found display default
    if (isset($modinfo['icon'])) {
        $modicon = xarVarPrepForDisplay($modinfo['icon']);
    } else {
        $modicon = xarTplGetImage('admin.png',$data['modname']);
        // Use supplied gif if it is there as the custom image was always called admin.gif (deprecated)
        if(!file_exists($modicon)) {
          $modicon = 'modules/' . $data['moddir'] . '/xarimages/admin.gif';
        }
    }
    $modicongeneric = xarTplGetImage('admin-generic.png','modules');
    if (substr($data['modclass'], 0, 4) == 'Core'){ //assume we have the icons for this ....
        $data['modiconurl']     = xarVarPrepForDisplay($modicon);
        $data['modiconmsg']     = xarVarPrepForDisplay(xarML('Xarigami Core Module'));
    }elseif (file_exists($modicon)){
        $data['modiconurl']     = xarVarPrepForDisplay($modicon);
        $data['modiconmsg']     = xarML('As provided by the author');
    }else{
        $data['modiconurl']     = $modicongeneric;
        $data['modiconmsg']     = xarML('Only generic icon has been provided');
    }
    if(isset($modinfo['dependency']) && is_array($modinfo['dependency']) && count($modinfo['dependency']) > 0){
        $dependency             = xarML('Module IDs:').' '.implode(',',$modinfo['dependency']);
    } else {
        $dependency             = xarML('None');
    }
    $data['moddependency']      = xarVarPrepForDisplay($dependency);
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('modules','admin','getmenulinks');

    // Redirect
    return $data;
}

?>