<?php
/**
 * Update the module version in the database
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update the module version in the database
 *
 * @param 'regId' the id number of the module to update
 * @returns bool
 * @return true on success, false on failure
 *
 * @author Xarigami Development Team
 */
function themes_admin_updateversion()
{
    // Get parameters from input
    xarVarFetch('id', 'id', $regId);

    if (!isset($regId)) {
        $msg = xarML('Invalid theme id');
        throw new BadParameterException(null,$msg);
    }

    // Security Check
    if(!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    // Pass to API
    $updated = xarMod::apiFunc('themes','admin','updateversion',
                              array('regId' => $regId));

    if (!isset($updated)) return;

    // Redirect to module list
    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>