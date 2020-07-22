<?php
/**
 * Overview displays standard Overview page
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
 * Overview displays standard Overview page
 *
 * Used to call the template that provides display of the overview
 *
 * @author jojodee
 * @return array xarTplModule with $data containing template data
 *         array containing the menulinks for the overview item on the main manu
 * @since 2 Nov 2005
 */
function themes_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    $data=array();
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');   
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */

    return xarTplModule('themes', 'admin', 'main', $data,'main');
}

?>