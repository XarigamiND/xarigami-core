<?php
/**
 * Confirm logout from Admin panels system
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Confirm logout from administration system
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  array data for template
 * @throws  no exceptions
 * @todo    nothing
*/
function base_admin_confirmlogout()
{
    // Template does it all
    if (xarUserIsLoggedIn()) {
        return array();
    } else {
        xarResponseRedirect(xarServer::getVar('HTTP_REFERER'));
    }
}
?>