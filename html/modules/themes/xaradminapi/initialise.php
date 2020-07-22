<?php
/**
 * Initialise a theme
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage XarigamiThemes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Initialise a theme
 *
 * @param regid registered theme id
 * @returns bool
 * @return
 * @throws BAD_PARAM, THEME_NOT_EXIST
 */
function themes_adminapi_initialise($args)
{

    extract($args);

    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    if (!isset($themeInfo)) {
        throw new ThemeNotFoundException($regid,'Theme (regid: #(1) does not exist.');
    }

    // Get theme database info
    xarThemeDBInfoLoad($themeInfo['name'], $themeInfo['directory']);

    $xarinitfilename = xarConfigGetVar('Site.BL.ThemesDirectory').'/'. $themeInfo['directory'] .'/xartheme.php';
    if (!file_exists($xarinitfilename)) {
       throw new FileNotFounException($xarinitfilename);
    }

     // Update state of theme - initialise first
    $set = xarMod::apiFunc('themes','admin', 'setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_INACTIVE));

    if (!isset($set)) {
        xarSession::setVar('errormsg', xarML('Theme state change failed'));
        return false;
    }

    //do theme vars - after the theme is initialised
    try {
        include $xarinitfilename;
    } catch (Exception $e) {
        $themevars = '';
    }

    if (isset($themevars) && !empty($themevars)) {
        $temp = array();
        foreach($themevars as $var => $value){
            if(!isset($value['name']) || empty($value['name']) || !isset($value['value'])){
                $msg = xarML('Malformed Theme Variable (#(1)) in your xartheme.php file for theme #(2).', $var, $themeInfo['name']);
               // throw new Exception($msg);
               //don't break install over a theme variable here
               //xarSessionSetVar('themes_statusmsg',$msg);
               xarTplSetMessage($msg,'alert');
               xarLogMessage('THEME INSTALL: error installing theme var for theme' .$themeInfo['name']);
               continue;
            }
           $value['prime'] = 1; //always set theme vars to prime
           $value['themename'] = $themeInfo['name'];
           $value['varname'] = $value['name'];
           $set = xarThemeSetConfig($value);
           if(!$set) return;
        }
    }
    // Success
    return true;
}
?>