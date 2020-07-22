<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Overview displays standard Overview page
 *
 * Used to call the template that provides display of the overview
 *
 * @returns array xarTplModule with $data containing template data
 * @return array containing the menulinks for the overview item on the main manu
 * @since 2 Nov 2005
 */
function roles_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('EditRole',0) && !xarSecurityCheck('ModerateGroupRoles',0)) return xarResponseForbidden();

    $data=array();
    
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview 
     */
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');   
    return xarTplModule('roles', 'admin', 'main', $data,'main');
}

?>