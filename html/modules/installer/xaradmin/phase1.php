<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Phase 1: Welcome (Set Language and Locale) Page
 *
 * @access private
 * @return data array of language values
 */
function installer_admin_phase1()
{
    //jojo - replace this check with a better one when we finish the new installer
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    // Get the installed locales
    $locales = xarMLSListSiteLocales();

    // Construct the array for the selectbox (iso3code, string in own locale)
    if(!empty($locales)) {
        $languages = array();
        foreach ($locales as $locale) {
            // Get the isocode and the description
            // Before we load the locale data, let's check if the locale is there

            $locale_data = xarMLSLoadLocaleData($locale);
            $languages[$locale] = $locale_data['/language/display'];
        }
    }

    $data['install_language'] = $install_language;
    $data['languages'] = $languages;
    $data['phase'] = 1;
    $data['phase_label'] = xarML('Step One');

    return $data;
}
?>