<?php
/**
 * View complete theme information/details
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * View complete theme information/details
 * function passes the data to the template
 *
 * @author Marty Vance
 * @author Jo Dalle Nogare
 * @access public
 * @param none
 * @return array
 * @todo some facelift
 */
function themes_admin_themesinfo()
{

    // Security check - not needed here, imo
    // we just show some info here, not changing anything
    /* if (!xarSecConfirmAuthKey()) return; */

    $data = array();

    if (!xarVarFetch('id', 'int:1:', $id)) return;
   
    // obtain maximum information about module
    $info = xarThemeGetInfo($id);
    // data vars for template
    $data['themeid']              = xarVarPrepForDisplay($id);
    $data['themename']            = xarVarPrepForDisplay($info['name']);
    $data['themedescr']           = xarVarPrepForDisplay($info['description']);
    $data['themedispname']        = xarVarPrepForDisplay($info['displayname']);
    $data['themelisturl']         = xarModURL('themes', 'admin', 'list');

    $data['themedir']             = xarVarPrepForDisplay($info['directory']);
    $data['themeclass']           = xarVarPrepForDisplay($info['class']);
    $data['themever']             = xarVarPrepForDisplay($info['version']);
    $data['themestate']           = $info['state'];
    $data['themeauthor']          = preg_replace('/,/', '<br />', xarVarPrepForDisplay($info['author']));
    if(!empty($info['dependency'])){
        $dependency             = xarML('Working on it...');
    } else {
        $dependency             = xarML('None');
    }
    $data['themedependency']      = xarVarPrepForDisplay($dependency);
    $themedir = xarConfigGetVar('Site.BL.ThemesDirectory');
    $previewpath = "$themedir/$info[directory]/images/preview.jpg";
    $data['themepreview'] = file_exists($previewpath) ? $previewpath : '';

    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');   
    // Redirect
    return $data;
}
?>