<?php
/**
 * Modify a template tag
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Modify a template tag
 * @param none
 */
function themes_admin_modifytpltag()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0)) return xarResponseForbidden();

    $aData = array();

    // form parameters
    if (!xarVarFetch('tagname', 'str::', $tagname, '')) return;

    // get the tags as an array
    $aTplTag = xarMod::apiFunc('themes','admin','gettpltag',
                             array('tagname'=>$tagname));

    $aData = $aTplTag;
    $aData['authid'] = xarSecGenAuthKey();
    $aData['updateurl'] = xarModUrl('themes','admin','updatetpltag');
    //common admin menu
    $aData['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');   
    return $aData;
}

?>