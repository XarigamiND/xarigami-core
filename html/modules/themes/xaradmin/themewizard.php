<?php
/**
 * Theme wizard
 *
 * @package modules
 * @subpackage Xarigami Themes module
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Display theme and skin wizard if there is one available wizard
 *
 * @param themeid $ theme id
 * @return array An array of variables to pass to the template
 */
function themes_admin_themewizard()
{

    if (!xarVarFetch('themeid',   'int:1:', $regid, NULL,   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('action',    'str', $action, '',   XARVAR_NOT_REQUIRED)) {return;}
        if (!isset($regid)) {
            //use current theme
            $themename = xarTplGetThemeName();
            $regid = xarThemeGetIDFromName($themename);
        }
        $themeinfo = xarThemeGetInfo($regid);
        $themename = $themeinfo['name'];
        if (!isset($themename)) return;

     // Security Check
    if (!xarSecurityCheck('EditTheme', 0, 'All', '$themename::$regId')) return xarResponseForbidden();

    if (isset($action) && ($action == 'update') && xarSecConfirmAuthKey()) {
        //get all the properties for this theme
        $themeprops = xarThemeGetConfig(array('themename'=>$themename));
        $args['id']     =  $themename; //name of property
        $newname   =$themename;
        $args['name'] = $newname;
        //ensure we get the value of our theme config property not individual properties
        //we get the value returned as one array then
        $args['type'] = 2001; //config type

        $myprop = xarMod::apiFunc('dynamicdata','user','getproperty',$args);
        //get the value
        $isvalid = TRUE;
        $msg = '';
        xarSessionDelVar('themevar.set');
        $isvalid = $myprop->checkInput($newname);
        $newvalue = $myprop->value;
        $oldvalue = array();

        foreach($themeprops as $var =>$info) {
            if (is_array($newvalue[$var])) {
                $newvalue[$var] = serialize($newvalue[$var]);
            }

            $info['themename'] = $themename;
            $info['varname'] = $info['name'];
            $info['value'] = $newvalue[$var];

            if ($isvalid === TRUE) {
                xarThemeSetConfig($info);
            }
        }
        if ($isvalid === TRUE) {
            $msg = xarML('Theme variable values have been successfully updated.');
            xarTplSetMessage($msg,'status',FALSE);
        } else {
            $invalidmsg = $myprop->invalid;
            $msg = xarML('There was a problem updating theme variable values. Please check listed errors:')."\n";
            foreach ($invalidmsg as $varname => $msginfo) {
                $msg .="<br />&#160;&#160;<strong>$varname</strong>  $msginfo \n";
            }
            xarTplSetMessage($msg,'error');
        }
    } else {
          $themevarmessage = '';
          $isvalid =  TRUE;
    }
    $themevarmessage = xarSessionGetVar('themevar.set');
    $data['authid'] = xarSecGenAuthKey();
    $data['themename'] = $themename;
    $data['regid'] = xarThemeGetIDFromName($themename);
    $data['isvalid'] = $isvalid;
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');

    return $data;
}

?>