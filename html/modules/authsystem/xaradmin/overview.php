<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Overview displays standard Overview page
 * Used to call the template that provides display of the overview
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @return array xarTplModule with $data containing template data
 *          containing the menulinks for the overview item on the main manu
 * @since 29 Jan 2006
 */
function authsystem_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminAuthsystem',0)) return xarResponseForbidden();

    $data=array();
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('authsystem','admin','getmenulinks');  
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     * in this case main function
     */

    return xarTplModule('authsystem', 'admin', 'main', $data,'main');
}

?>