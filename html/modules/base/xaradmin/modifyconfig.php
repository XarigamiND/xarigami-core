<?php
/**
 * Modify site configuration
 *
 * @package modules
 * @copyright (C) 2002-2007The Digital Development Foundation
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
function base_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'display', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str:1:100', $data['returnurl'], Null, XARVAR_NOT_REQUIRED)) return;
    xarVarFetch('invalid', 'array',     $invalid, array(), XARVAR_NOT_REQUIRED);

    //Site Display variables
    if (xarConfigGetVar('Site.Core.DefaultModuleType') == 'admin'){
    // Get list of user capable mods
        $data['mods'] = xarMod::apiFunc('modules','admin','getlist',
                          array('filter'     => array('AdminCapable' => 1)));
    } else {
        $data['mods'] = xarMod::apiFunc('modules','admin','getlist',
                          array('filter'     => array('UserCapable' => 1)));
    }
    $defaultModuleName = xarConfigGetVar('Site.Core.DefaultModuleName');
    $data['defaultModuleName'] = $defaultModuleName;
    $data['defaultModuleMissing']  = true;
    $data['modlist'] = array();
    foreach ($data['mods'] as $module) {
        if ($module['name'] == $defaultModuleName) {
            $data['defaultModuleMissing'] = false;
        }
        $data['modlist'][$module['name']] =  $module['displayname'];
    }

    if ($data['defaultModuleMissing']) {
         $data['defaultModuleName'] = '';
         $data['modlist'][''] = xarML("MISSING: #(1)",$defaultModuleName);
    }

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
    $data['showcontrolpanel'] = xarModGetVar('base', 'showdashboard')?xarModGetVar('base', 'showdashboard'):false;
    $data['UseAlternatePageTemplate'] = xarModGetVar('base', 'UseAlternatePageTemplate');
    $data['AlternatePageTemplateName'] = xarModGetVar('base', 'AlternatePageTemplateName');
    $data['EnableShortURLsSupport'] = xarConfigGetVar('Site.Core.EnableShortURLsSupport');
    $data['SupportShortURLs'] = xarModGetVar('base', 'SupportShortURLs');
    $data['FixHTMLEntities'] = xarConfigGetVar('Site.Core.FixHTMLEntities');
    $data['DefaultModuleFunction'] = xarConfigGetVar('Site.Core.DefaultModuleFunction');
    $data['DefaultModuleType'] = xarConfigGetVar('Site.Core.DefaultModuleType');
    $data['defaulttype'] = array('admin'=>'Admin','user'=>'User');
    $data['ThemesDirectory'] = xarConfigGetVar('Site.BL.ThemesDirectory');
    $data['EnableSecureServer'] = xarConfigGetVar('Site.Core.EnableSecureServer');
    $port = xarConfigGetVar('Site.Core.SecureServerPort');
    $data['SecureServerPort'] = empty($port) ? 443 : $port;

    //Security and sessions
    $data['authmodules'] = xarConfigGetVar('Site.User.AuthenticationModules');

    $data['authlist'] = array();

    foreach ($data['authmodules'] as $authmod) {
        $data['authlist'][$authmod] = $authmod;
    }
    $data['size'] = count($data['authmodules']);
    $data['SecurityLevel'] = xarConfigGetVar('Site.Session.SecurityLevel');
    $data['securityvalues'] = array('High'=>'High','Medium'=>'Medium','Low'=>'Low');
    $data['SessionDuration'] =  xarConfigGetVar('Site.Session.Duration');
    $data['InactivityTimeout'] =  xarConfigGetVar('Site.Session.InactivityTimeout');
    $data['CookieName'] =  xarConfigGetVar('Site.Session.CookieName');
    $data['CookiePath'] = xarConfigGetVar('Site.Session.CookiePath');
    $data['CookieDomain'] = xarConfigGetVar('Site.Session.CookieDomain');
    $data['RefererCheck'] = xarConfigGetVar('Site.Session.RefererCheck');


    // Localization Virtual Paths
    $data['VPEnabled'] = xarMLSVirtualPathsIsEnabled();
    $data['VPEnforced'] =  xarMLSVirtualPathsIsEnforced();

    $data['virtualpaths'] = xarMLSGetVirtualPathMappingArray();


    //Other Options
    $data['LoadLegacy'] = xarConfigGetVar('Site.Core.LoadLegacy');
    $data['proxyhost'] = xarModGetVar('base','proxyhost');
    $data['proxyport'] = xarModGetVar('base','proxyport');
    $releasenumber=xarModGetVar('base','releasenumber');
    $data['releasenumber']=isset($releasenumber) ? $releasenumber:10;
    $data['showresources']=xarModGetVar('base','showresources');
    $data['showdevnotes']=xarModGetVar('base','showdevnotes');
    $data['showdevnews']=xarModGetVar('base','showdevnews');
    $data['urlspaces']=xarModGetVar('base','urlspaces')?xarModGetVar('base','urlspaces'): 0;
    $data['urlspaceoptions'] = array('_'=> xarML('_ (underscore)'),
                             '-'=> xarML('- (dash)')
                            );
    // TODO: delete after new backend testing
    // $data['translationsBackend'] = xarConfigGetVar('Site.MLS.TranslationsBackend');
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');
    $data['XARCORE_VERSION_NUM'] = XARCORE_VERSION_NUM;
    $data['XARCORE_VERSION_ID'] =  XARCORE_VERSION_ID;
    $data['XARCORE_VERSION_SUB'] = XARCORE_VERSION_SUB;
    $data['XARCORE_VERSION_REV'] = XARCORE_VERSION_REV;
    //common menu
    $data['menulinks'] = xarMod::apiFunc('base','admin','getmenulinks');
    $data['dummyimage'] = xarTplGetImage('blank.gif','base');
    $data['invalid'] = $invalid;
    return $data;
}

?>
