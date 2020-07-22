<?php
/**
 * Changes the navigation language
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @

/**
 * Changes the navigation language
 * This is the external entry point to tell MLS use another language
 */
function roles_user_changelanguage()
{
    if (!xarVarFetch('locale',     'str:1:', $locale,     NULL, XARVAR_GET_OR_POST, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('return_url', 'str:1:', $return_url, NULL, XARVAR_GET_OR_POST, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('override',   'bool',  $override, false, XARVAR_GET_OR_POST)) return;

    $locales = xarMLSListSiteLocales();
    if (!isset($locales)) return; // throw back
    // Check if requested locale is supported
    if (!in_array($locale, $locales)) {
        throw new LocaleNotFoundException($locale);
    }
    if (xarUserSetNavigationLocale($locale) == false) {
        // Wrong MLS mode
        // FIXME: <marco> Show a custom error here or just throw an exception?
        // <paul> throw an exception. trap it later if we want it to look nice,
        // that's the whole point of exceptions.
    }
    xarMLSEnforceLocale($locale, $return_url, $override);
    xarResponseRedirect($return_url);
}

?>