<?php
/**
 * Overview displays standard Overview page
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Overview displays standard Overview page
 *
 * Used to call the template that provides display of the overview
 *
 * @return array xarTplModule with $data containing template data
 * @author jojodee
 * @since 2 Nov 2005
 */
function blocks_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('EditBlock',0)) return xarResponseForbidden();

    $data=array();

    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('blocks','admin','getmenulinks');
    
    return xarTplModule('blocks', 'admin', 'main', $data,'main');
}

?>
