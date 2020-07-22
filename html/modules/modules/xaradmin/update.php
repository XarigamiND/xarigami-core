<?php
/**
 * Update a module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update a module
 *
 * @author Xarigami Development Team
 * @param id the module's registered id
 * @param newdisplayname the new display name
 * @param newdescription the new description
 * @returns bool
 * @return true on success, error message on failure
 */
function modules_admin_update()
{
    // Get parameters
    xarVarFetch('id','id',$regId);
    xarVarFetch('newdisplayname','str::',$newDisplayName);

    if (!xarSecConfirmAuthKey()) return;

    // Pass to API
    $updated = xarMod::apiFunc('modules', 'admin', 'update',
                              array('regid' => $regId,
                                    'displayname' => $newDisplayName));

    if (!isset($updated)) return;

    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);
    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } else {
        xarResponseRedirect(xarModURL('modules', 'admin', 'list'));
    }

    return true;
}

?>