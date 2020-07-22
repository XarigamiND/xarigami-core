<?php
/**
 * Overview displays standard Overview page
 *
 * @package Xaraya modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xartinymce
 * @author Xarigami Team 
 */

/**
 * Overview displays standard Overview page
 *
 * Only used if you actually supply an overview link in your adminapi menulink function
 * and used to call the template that provides display of the overview
 *
 * @return array xarTplModule with $data containing template data
 * @since 2 Oct 2005
 */
function base_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();
    if (!xarVarFetch('template','str:',$template,'',XARVAR_NOT_REQUIRED)) return;
    $data=array();
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */
    //common admin menu
   $data['menulinks'] = xarMod::apiFunc('base','admin','getmenulinks');
   if ($template !='') {
      return xarTplModule('base', 'admin', 'main', $data,$template);
   } else {
       return xarTplModule('base', 'admin', 'main', $data,'main');
   }
}

?>