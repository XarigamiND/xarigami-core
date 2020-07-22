<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Overview displays standard Overview page
 *
 * Used to call the template that provides display of the overview
 *
 * @return array xarTplModule with $data containing template data
 * @since 2 Nov 2005
 */
function dynamicdata_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('ModerateDynamicData',0)) return xarResponseForbidden();

    $data=array();
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */

    return xarTplModule('dynamicdata', 'admin', 'main', $data,'main');
}

?>