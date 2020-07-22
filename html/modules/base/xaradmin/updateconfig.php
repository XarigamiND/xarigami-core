<?php
/**
 * Update site configuration
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Update site configuration
 *
 * @param string tab Part of the config to update
 * @param string returnurl  optional
 * @return bool true on success of update
 * @todo move in timezone var when we support them
 * @todo decide whether a site admin can set allowed locales for users
 * @todo update auth system part when we figure out how to do it
 * @todo add decent validation
 */
function base_admin_updateconfig()
{

    if (!xarSecConfirmAuthKey()) return;

    // Security Check
    if(!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();
    $invalid = array();
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str:1:100', $data['returnurl'], Null, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('direction','str:1:',$direction,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('moveinst','str:1',$moveinst,'',XARVAR_NOT_REQUIRED)) return;

    if (!empty($direction) && !empty($moveinst) && $data['tab'] == 'security') {
        $authorder= xarConfigGetVar('Site.User.AuthenticationModules');
        $totalauth = count($authorder);
        $found = array_search($moveinst,$authorder);
        if ($direction == 'up' && $found>0) {
            $newpos = $found-1;
            unset($authorder[$found]);
            array_splice($authorder,$newpos,0,$moveinst);
        } elseif ($found < $totalauth) {
            $newpos = $found+1;
            unset($authorder[$found]);
            array_splice($authorder,$newpos,0,$moveinst);
        }
        xarConfigSetVar('Site.User.AuthenticationModules',$authorder);
        xarResponseRedirect(xarModURL('base','admin','modifyconfig',array('tab'=>'security')));
    }


    switch ($data['tab']) {
        case 'display':
            if (!xarVarFetch('defaultmodule','str:1:',$defaultModuleName)) return;
            if (!xarVarFetch('alternatepagetemplate','checkbox',$alternatePageTemplate,false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('alternatepagetemplatename','pre:trim:passthru:str',$alternatePageTemplateName,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('defaulttype','str:1:',$defaultModuleType)) return;
            if (!xarVarFetch('defaultfunction','pre:trim:passthru:str:1:',$defaultModuleFunction,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('shorturl','checkbox',$enableShortURLs,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('baseshorturl','checkbox',$enableBaseShortURLs,false,XARVAR_NOT_REQUIRED)) return;
             if (!xarVarFetch('showcontrolpanel','checkbox',$showcontrolpanel,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('themedir','pre:trim:passthru:str:1:',$defaultThemeDir,'themes',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('urlspaces','str:1',$urlspaces,'_',XARVAR_NOT_REQUIRED)) return;
            //check required fields
            if (!isset($defaultThemeDir) || empty($defaultThemeDir)) {
                $invalid['themedir'] = xarML('A default theme directory must be provided. Ensure the directory contains your current active theme.');
            }
            if (!isset($defaultModuleFunction) || empty($defaultModuleFunction)) {
                $invalid['defaultfunction'] = xarML('The Default Module Function cannot be empty. Your changes have not been saved.');
            }
            if ((!isset($alternatePageTemplateName) || empty($alternatePageTemplateName)) && ($alternatePageTemplate != FALSE)) {
                $invalid['alternatepagetemplatename'] = xarML('Alternate page template name must not be empty when an alternative page template is selected.');
            }
            if (count($invalid) > 0) {
                xarResponseRedirect(xarModURL('base','admin', 'modifyconfig', array('invalid'=>$invalid,'tab'=>'display')));
                die('what');
            }

            xarConfigSetVar('Site.BL.ThemesDirectory', $defaultThemeDir);

            xarConfigSetVar('Site.Core.DefaultModuleName', $defaultModuleName);
            xarModSetVar('base','UseAlternatePageTemplate', ($alternatePageTemplate ? 1 : 0));
            xarModSetVar('base','AlternatePageTemplateName', $alternatePageTemplateName);
            xarModSetVar('base','showdashboard', $showcontrolpanel);
            xarConfigSetVar('Site.Core.DefaultModuleType', $defaultModuleType);
            xarConfigSetVar('Site.Core.DefaultModuleFunction', $defaultModuleFunction);
            xarConfigSetVar('Site.Core.EnableShortURLsSupport', $enableShortURLs);
            // enable short urls for the base module itself too
            xarModSetVar('base','SupportShortURLs', ($enableBaseShortURLs ? 1 : 0));
            xarModSetvar('base','urlspaces',$urlspaces);

            xarLogMessage('BASE: Configuration settings in Display were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
              $msg = xarML("Site default page and display settings were successfully updated.");
             xarTplSetMessage($msg,'status');
            break;
        case 'security':
            if (!xarVarFetch('secureserver','checkbox',$secureServer,FALSE,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('secureserverport','int:1:',$secureServerPort,0,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('securitylevel','str:1:',$securityLevel)) return;
            if (!xarVarFetch('sessionduration','int:1:',$sessionDuration,30,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('sessiontimeout','int:1:',$sessionTimeout,10,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('authmodule_order','str:1:',$authmodule_order,'',XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('cookiename','str:1:',$cookieName,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('cookiepath','str:1:',$cookiePath,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('cookiedomain','str:1:',$cookieDomain,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('referercheck','str:1:',$refererCheck,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('proxyhost','str:1:',$proxyhost,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('proxyport','int:1:',$proxyport,0,XARVAR_NOT_REQUIRED)) return;

            xarConfigSetVar('Site.Core.EnableSecureServer', $secureServer);
            xarConfigSetVar('Site.Core.SecureServerPort', $secureServerPort);
            //Filtering Options
            // Security Levels
            xarConfigSetVar('Site.Session.SecurityLevel', $securityLevel);
            xarConfigSetVar('Site.Session.Duration', $sessionDuration);
            xarConfigSetVar('Site.Session.InactivityTimeout', $sessionTimeout);
            xarConfigSetVar('Site.Session.CookieName', $cookieName);
            xarConfigSetVar('Site.Session.CookiePath', $cookiePath);
            xarConfigSetVar('Site.Session.CookieDomain', $cookieDomain);
            xarConfigSetVar('Site.Session.RefererCheck', $refererCheck);
            xarModSetVar('base','proxyhost',$proxyhost);
            xarModSetVar('base','proxyport',$proxyport);
            // Authentication modules

            if (!empty($authmodule_order)) {
                $authmodules = explode(';', $authmodule_order);
                xarConfigSetVar('Site.User.AuthenticationModules', $authmodules);
            }
             xarLogMessage('BASE: Configuration settings in Security were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
               $msg = xarML("Server and Session related settings were successfully updated.");
             xarTplSetMessage($msg,'status');
            break;
         case 'urls':
            if (!xarVarFetch('VPEnabled','checkbox',$VPEnabled,FALSE,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('VPEnforced','checkbox',$VPEnforced,FALSE,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('paths','isset',$VPPaths)) return;
            if (!xarVarFetch('locales','isset',$VPLocales)) return;
            if(count($VPPaths) != count($VPLocales)) return;

            // Virtual paths for locales
            xarMLSActivateVirtualPaths($VPEnabled);
            // Enforce URL using navigation locale
            xarMLSSetEnforcedVirtualPaths($VPEnforced);
            $VPOldMapping = xarMLSGetVirtualPathMappingArrayFromLocale();
            // Check out that active locales have not been changed
            if (count($VPOldMapping) != count($VPLocales)) return;
            $VPNewMapping = array_combine($VPLocales, $VPPaths);
            // Compare previous and new mapping
            $VPDiff = array_diff_assoc($VPOldMapping, $VPNewMapping);
            // Then update
            foreach($VPDiff as $locale => $path) {
                xarMLSSetVirtualPath($locale, strtolower(trim($VPNewMapping[$locale])));
            }

            xarLogMessage('BASE: Configuration settings in URLS updated by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
              $msg = xarML("URL rewrite related settings were successfully updated.");
             xarTplSetMessage($msg,'status');
            break;
        case 'other':
            if (!xarVarFetch('loadlegacy','checkbox',$loadLegacy,FALSE,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('releasenumber','int:1:',$releasenumber,10,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showdevnotes','checkbox',$showdevnotes,FALSE,XARVAR_NOT_REQUIRED)) return;
             if (!xarVarFetch('showresources','checkbox',$showresources,FALSE,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showdevnews','checkbox',$showdevnews,FALSE,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('htmlenitites','checkbox',$FixHTMLEntities,FALSE,XARVAR_NOT_REQUIRED)) return;

            // Save these in normal module variables for now

            xarModSetVar('base','releasenumber', $releasenumber);
            xarConfigSetVar('Site.Core.LoadLegacy', $loadLegacy);
            xarModSetVar('base','showdevnotes',$showdevnotes);
            xarModSetVar('base','showresources',$showresources);
            xarModSetVar('base','showdevnews',$showdevnews);
            xarConfigSetVar('Site.Core.FixHTMLEntities', $FixHTMLEntities);
             xarLogMessage('BASE: Configuration settings in Other were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
            $msg = xarML("General option and Resource Feed settings were successfully updated.");
             xarTplSetMessage($msg,'status');
            break;
    }

    // Call updateconfig hooks
    xarMod::callHooks('module','updateconfig','base', array('module' => 'base'));

    if (isset($data['returnurl'])) {
        xarResponseRedirect($data['returnurl']);
    } else {
        xarResponseRedirect(xarModURL('base', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
    }
    return true;
}

?>