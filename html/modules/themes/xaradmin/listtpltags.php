<?php
/**
 * List template tags
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
 * List template tags
 * @param none
 */
function themes_admin_listtpltags()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0)) return xarResponseForbidden();

    $aData = array();

    // form parameters
    if (!xarVarFetch('modname', 'str:1:', $sSelectedModule, '', XARVAR_NOT_REQUIRED)) return;

    // get the tags as an array
    $aTplTags = xarMod::apiFunc('themes', 'admin','gettpltaglist',
                              array('module'=>$sSelectedModule));


    // add delete / edit urls to the array
    for($i=0; $i<sizeOf($aTplTags); $i++) {
        $aTplTags[$i]['editurl']   = xarModUrl('themes', 'admin', 'modifytpltag', array('tagname'=>$aTplTags[$i]['name']));
        $aTplTags[$i]['deleteurl'] = xarModUrl('themes', 'admin', 'removetpltag', array('tagname'=>$aTplTags[$i]['name']));
    }

    $aData['tags'] = $aTplTags;
    $aData['addurl'] = xarModUrl('themes', 'admin', 'modifytpltag', array('tagname'=>''));
    //common admin menu
    $aData['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');   
    return $aData;

}

?>