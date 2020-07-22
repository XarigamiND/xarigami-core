<?php
/**
 * Take action on theme config
 *
 * @package modules
 * @subpackage Xarigami Themes module
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Configure theme
 *
 * @param id $ theme id
 * @return array An array of variables to pass to the template
 */
function themes_admin_configaction()
{
    if (!xarVarFetch('themeid', 'int',       $regid)) return;
    if (!xarVarFetch('varid',   'isset',     $varid,    null,   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('varname', 'str',       $varname , '',   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('action',  'str',       $action)) return;


    $themeinfo  = xarThemeGetInfo($regid);
    $themename  = $themeinfo['name'];
    $propertytypes = xarMod::apiFunc('dynamicdata','user','getproptypes');
    $varinfo    = xarThemeGetConfig(array('themename'=>$themename,'varname'=>$varname));
    $varinfo    = isset($varinfo[$varname])? $varinfo[$varname]:$varinfo;

    $data['action']     = $action;
    $data['authid']     = xarSecGenAuthKey();
    $data['themeid']    = $regid;
    $data['themename']  = $themename;
    $data['varid']      = $varid;
    $data['varname']    = $varname;
    $data['menulinks']  = xarMod::apiFunc('themes','admin','getmenulinks');

    if (!xarSecurityCheck('AdminTheme', 0, 'All', '$themename::$regid')) return xarResponseForbidden();

    if ($action == 'del' || $action == 'confirmdel') {
        $data['action']     = 'del';
           //common admin menu
        if ($action == 'confirmdel') {
        if (!xarSecConfirmAuthKey()) return;
            $test = xarThemeDelVar($themename,$varname);
             $msg =xarML('Theme variable - #(1) - was removed from your database.',$varname);
                xarTplSetMessage($msg,'status');
        } else {
            return $data;
        }
    }
    if ($action == 'restore' && $varinfo['prime'] ==1 ) {
        //delete the var and regenerate
        $test = xarThemeDelVar($themename,$varname);

    }
    if ($action == 'restoreall') {
        //delete the var and regenerate
        $args = array('themename'=>$themename,'prime'=>1);
        $test = xarThemeDelConfig($args);
        if ($test ===TRUE) {
            $msg = xarML('System theme variables for -#(1)- have been reset.',$themename);
            xarTplSetMessage($msg,'status');
        }

    }
    if ($action=='config') { //this will submit to  update function

        //get the property config info
        $data               = $varinfo;
        $args['modid']      = 70;//themes module
        $args['itemtype']   = $regid;
        $args['varname']    = $varname;
        $args['varid']      = $varinfo['id'];
        $args['themename']  = $themename;
        $args['themeid']    = $regid;
        $args['type']       = 2002; //themevar
        $args['id']         = $varname.'_'.$args['varid']; //unique name used by the whole configuration
        $args['name']       = $varname.'_'.$args['varid'];
        $args['xv_vartype'] = $data['config']['type'] ;
               //here we get the theme var property type that will be used to show the configuration screen
        $myvarprop     = xarMod::apiFunc('dynamicdata','user','getproperty',$args);
        //set up specific info for this properties config
        //now send the information to be parsed
        //this will populate the property with the specific information for this property type
        $myvarprop->parseValidation($data['config']);
        //values for the template;

        $data['varid']      =  $varinfo['id'];
        $data['showval']    =  $myvarprop->showValidation($args);
        $data['themeid']    = $regid;
        $data['themename']  = $themename;
        $data['authid']     = xarSecGenAuthKey();
        $data['varname']    = $varname;
        //use the property id of the actual type which is proptype
        $propid     = isset($myvarprop->xv_proptype) && !empty($myvarprop->xv_proptype)?$myvarprop->xv_proptype: 2;//

        $data['propinfourl'] = xarModURL('dynamicdata','admin','propinfo',array('propid'=>$propid));

        $data['action'] = 'config';

        return $data;

    }
    $returnurl = xarModURL('themes','admin','config',array('themeid'=>$regid));

    xarResponseRedirect($returnurl);
    return;
}

?>