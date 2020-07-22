<?php
/**
 * View complete module information/details
 *
 * @package modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * View dynamic data property information/details
 * function passes the data to the template
 * opens in new window when browser is javascript enabled
 * @access public
 * @param none
 * @returns array
 */
function dynamicdata_admin_propinfo()
{

   //  if (!xarSecConfirmAuthKey('ViewDynamicData',1)) return xarResponseForbidden();

    $data = array();

    if (!xarVarFetch('propid', 'notempty', $id)) {return;}

    $props = xarMod::apiFunc('dynamicdata','user','getproptypes');
    //get info for the property
    if (isset($props[$id])) {
        $data['propinfo'] = $props[$id];
    } else {
        $data['propinfo'] = array(
                            'id'=>$id,
                            'name'=>'missing',
                            'label'=>'',
                            'format'=>'',
                            'filepath'=>'',
                            'validation'=>'',
                            'source'=>'',
                            'dependencies'=>'',
                            'requiresmodule'=>'dynamicdata',
                            'args'=>'',
                            'propertyClass'=>'',
                            'aliases'=>''
                            );
    }
    //jojo - TODO: not strictly correct as we need to get the module dir but atm dir == modname - watch it!
    $moddir = empty($data['propinfo']['requiresmodule']) ? 'base': trim($data['propinfo']['requiresmodule']);
    $data['moddir'] = $moddir;
    $locale = xarMLSGetCurrentLocale();
    $language = strtolower(xarMLSGetLanguageFromLocale($locale));
    $modules = sys::code().'modules/';
    $file1 = $moddir.'/xardocs/properties/'.$language.'_'.$data['propinfo']['name'].'.html'; //specific locale
    $file2 = $moddir.'/xardocs/properties/en-us_'.$data['propinfo']['name'].'.html'; //c language locale (en-us)
    $file3 = $moddir.'/xardocs/properties/'.$data['propinfo']['name'].'.html';//no locale
    $file4 = 'dynamicdata/xardocs/properties/'.$language.'_nodocs.html'; //locale no specific file
    $file5 = 'dynamicdata/xardocs/properties/en-us_nodocs.html'; //locale no specific file
    if (file_exists($modules.$file1)) {
        $data['infofile'] = $file1;
    } elseif (file_exists($modules.$file2)) {
        $data['infofile'] = $file2;
    } elseif (file_exists($modules.$file3)) {
         $data['infofile'] = $file3;
    } elseif (file_exists($modules.$file4)) {
        $data['infofile'] = $file4;
     } else {
        //bad luck we don't have anything but try language specific
         $data['infofile'] = $file5;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');

    // Redirect
    return $data;
}

?>