<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
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
 * @returns array xarTplModule with $data containing template data
 * @return array containing the menulinks for the overview item on the main manu
 * @since 2 Nov 2005
 */
function modules_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminModules',0)) return xarResponseForbidden();

    $data=array();
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('modules','admin','getmenulinks');       
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview 
     */

    return xarTplModule('modules', 'admin', 'main', $data,'main');
}

?>