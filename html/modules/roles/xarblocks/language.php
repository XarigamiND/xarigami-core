<?php
/**
 * Language Selection via block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/*
 * Language Selection via block
 * @author Marco Canini
 * initialise block
 */
function roles_languageblock_init()
{
    return array(
        'nocache' => 1, // don't cache by default
        'pageshared' => 1, // share across pages
        'usershared' => 0, // don't share across users
        'cacheexpire' => null);
}

/**
 * get information on block
 */
function roles_languageblock_info()
{
    return array(
        'text_type' => 'Language',
        'module' => 'roles',
        'text_type_long' => 'Language selection'
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function roles_languageblock_display($blockinfo)
{

    // if (xarMLSGetMode() != XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE) {
        return;
    }

    $current_locale = xarMLSGetCurrentLocale();

    $site_locales = xarMLSListSiteLocales();

    asort($site_locales);

    if (count($site_locales) <= 1) {
        return;
    }

    foreach ($site_locales as $locale) {
        $locale_data = xarMLSLoadLocaleData($locale);

        $selected = ($current_locale == $locale);

        $locales[] = array(
            'locale'   => $locale,
            'country'  => $locale_data['/country/display'],
            'name'     => $locale_data['/language/display'],
            'selected' => $selected
        );
    }


    $tplData['form_action'] = xarModURL('roles', 'user', 'changelanguage');
    $tplData['form_picker_name'] = 'locale';
    $tplData['locales'] = $locales;
    $tplData['blockid'] = $blockinfo['bid'];

    if (xarServer::getVar('REQUEST_METHOD') == 'GET') {
        // URL of this page
        $tplData['return_url'] = xarServer::getCurrentURL();
    } else {
        // Base URL of the site
        $tplData['return_url'] = xarServer::getBaseURL();
    }

    $blockinfo['content'] = $tplData;

    return $blockinfo;
}

?>
