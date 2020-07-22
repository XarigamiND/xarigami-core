<?php
/**
 * List themes and current settings
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * List themes and current settings
 * @author Marty Vance
 * @param several params from the associated form in template
 */
function themes_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    // form parameters
    if (!xarVarFetch('hidecore',  'str:1:', $hidecore,  '0',                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle',  'str:1:', $selstyle,  'plain',              XARVAR_NOT_REQUIRED)) return;//deprecated
    if (!xarVarFetch('selfilter', 'str:1:', $selfilter, 'XARTHEME_STATE_ANY', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selclass',  'str:1:', $selclass,  'all',                XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('regen',     'str:1:', $regen,     false,                XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('useicons',  'checkbox', $useicons, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selpreview',  'checkbox', $selpreview, FALSE,             XARVAR_NOT_REQUIRED)) return;

    //make sure var is set for those that skip upgrade  (we can remove this later mainly for mtn users atm)
    $dummy = xarModGetVar('themes','selpreview');
    $dummy = xarModSetVar('themes','selpreview',isset($dummy)?$dummy:false);

    if (!xarModSetUserVar('themes', 'hidecore', $hidecore)) return;
    if (!xarModSetUserVar('themes', 'selstyle', $selstyle)) return; //deprecated
    if (!xarModSetUserVar('themes', 'selfilter', $selfilter)) return;
    if (!xarModSetUserVar('themes', 'selclass', $selclass)) return;
    if (!xarModSetUserVar('themes', 'useicons', $useicons)) return;
    if (!xarModSetUserVar('themes', 'selpreview', $selpreview)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('regen' => $regen = 1)));
}

?>