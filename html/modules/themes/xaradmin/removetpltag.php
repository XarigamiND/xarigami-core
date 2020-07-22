<?php
/**
 * Update/insert a template tag
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update/insert a template tag
 *
 * @author Marty Vance
 * @param tagname
 * @return bool true on success, error message on failure
 * @author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_admin_removetpltag()
{
    // Get parameters
    if (!xarVarFetch('tagname', 'str:1:', $tagname)) return;

    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0)) return xarResponseForbidden();

    if (!xarTplUnregisterTag($tagname)) {

        $msg = xarML('Could not unregister #(1)', $tagname);

        throw new BadParameterException(null,$msg);
    }

    xarResponseRedirect(xarModUrl('themes', 'admin', 'listtpltags'));

    return true;
}

?>