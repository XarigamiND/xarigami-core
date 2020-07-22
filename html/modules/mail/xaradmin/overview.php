<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 * @author John Cox
 */
/**
 * Overview displays standard Overview page
 *
 * Used to call the template that provides display of the overview
 *
 * @return array xarTplModule with $data containing template data
 * @since 2 Nov 2005
 */
function mail_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();

    $data=array();

    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('mail','admin','getmenulinks');   
    return xarTplModule('mail', 'admin', 'main', $data,'main');
}

?>