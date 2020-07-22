<?php
/**
 * Review and configure Xarigami CSS
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Module admin function to review and configure Xarigami CSS
 * @return array
 */
function themes_admin_cssconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme',0)) return;
    // Generate security key
    $data['authid'] = xarSecGenAuthKey();

    return $data;
}

?>