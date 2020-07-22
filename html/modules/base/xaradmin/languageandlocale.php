<?php
/**
 * Modify site language and locale infor
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Modify site configuration
 * @return array of template values
 */
function base_admin_languageandlocale()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();

    if (!xarVarFetch('returnurl', 'str:1:100', $data['returnurl'], Null, XARVAR_NOT_REQUIRED)) return;
    xarVarFetch('invalid', 'array',     $invalid, array(), XARVAR_NOT_REQUIRED);


    $hostdatetime = new DateTime();
    $zone = xarSystemVars::get(sys::CONFIG, 'SystemTimeZone',true);
    $timezone = new DateTimeZone($zone);
    $hosttime = $hostdatetime->setTimezone($timezone);
    $data['hosttime'] = $zone;
    $sitedatetime = new DateTime();
    $sitetimezone = xarConfigGetVar('Site.Core.TimeZone');
    if (!isset($sitetimezone) || substr($sitetimezone,0,2) == 'US') {
        xarConfigSetVar('Site.Core.TimeZone', '');
    }
    $timezone = new DateTimeZone($sitetimezone);
    $sitedatetime->setTimezone($timezone);
    $data['sitetime'] = $sitetimezone;

     // Locale Options
    $data['MLSEnabled'] = xarConfigGetVar('Site.MLS.Enabled');
    $data['LADEnabled'] = xarConfigGetVar('Site.MLS.AutoDetection.Enabled');
    $localehome = sys::varpath() . "/locales";
    if (!file_exists($localehome)) {
       throw new DirectoryNotFoundException($localehome);
    }
    $BrowserDetection = new xarMLSBrowserLocales();
    $data['BrowserDetection'] = $BrowserDetection->DisplayBrowserInfo();

    $dd = opendir($localehome);
    $locales = array();
    while ($filename = readdir($dd)) {
            if (is_dir($localehome . "/" . $filename) && file_exists($localehome . "/" . $filename . "/locale.xml")) {
                $locales[] = $filename;
            }
    }
    closedir($dd);

   $allowedlocales = xarConfigGetVar('Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $allowedlocales)) $active = true;
        else $active = false;
        $data['locales'][] = array('name' => $locale, 'active' => $active);

        $data['localeitems'][$locale] = $locale;
    }

    $data['defaultlocale'] = xarMLSGetSiteLocale();
    $data['LADEnabled'] = xarConfigGetVar('Site.MLS.AutoDetection.Enabled');

    // Localization Virtual Paths
    $data['VPEnabled'] = xarMLSVirtualPathsIsEnabled();
    $data['VPEnforced'] =  xarMLSVirtualPathsIsEnforced();

    $data['virtualpaths'] = xarMLSGetVirtualPathMappingArray();

    // Translation Backend
    $data['TranslationsBackend']  = xarConfigGetVar('Site.MLS.TranslationsBackend');
    $data['backendoptions'] = array('xml'=>xarML('XML (good for interoperability)'),
                                    'php'=>xarML('PHP (good for programmed access)'),
                                    'xml2php'=>xarML('XML with caching in PHP (new experimental)'));


    //common menu
    $data['menulinks'] = xarMod::apiFunc('base','admin','getmenulinks');
    $data['invalid'] = $invalid;
      $data['authid'] = xarSecGenAuthKey();
    return $data;
}

?>
