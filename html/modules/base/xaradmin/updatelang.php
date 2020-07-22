<?php
/**
 * Update site configuration
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Update Language and places
 *
 * @param string returnurl  optional
 * @return bool true on success of update
 * @todo decide whether a site admin can set allowed locales for users
 * @todo update auth system part when we figure out how to do it
 * @todo add decent validation
 */
function base_admin_updatelang()
{

    if (!xarSecConfirmAuthKey()) return;

    // Security Check
    if(!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();
    $invalid = array();
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str:1:100', $data['returnurl'], Null, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('defaultlocale','str:1:',$defaultLocale)) return;
    if (!xarVarFetch('active','isset',$active)) return;
    if (!xarVarFetch('mlsmode','str:1:',$MLSMode,'SINGLE',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('MLSEnabled','checkbox',$MLSEnabled,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('LADEnabled','checkbox',$LADEnabled,false,XARVAR_NOT_REQUIRED)) return;

    $localesList = array();
    foreach($active as $activelocale) $localesList[] = $activelocale;
    if (!in_array($defaultLocale,$localesList)) $localesList[] = $defaultLocale;
    sort($localesList);

    if ($MLSMode == 'UNBOXED') {
        if (xarMLSGetCharsetFromLocale($defaultLocale) != 'utf-8') {
         $msg = xarML('You should select utf-8 locale as default before selecting UNBOXED mode');
         xarTplSetMessage($msg,'error');
            //throw new ConfigurationException(null,'You should select utf-8 locale as default before selecting UNBOXED mode');
        }
    }

    $oldmls = xarConfigGetVar('Site.MLS.Enabled');
    //Turn off/on MLS system
    xarConfigSetVar('Site.MLS.Enabled', $MLSEnabled);
    $switchmls = ($oldmls != $MLSEnabled) && $MLSEnabled == TRUE ? xarML('On'):xarML('Off');
    if ($oldmls != $MLSEnabled) {
        $tmsg = xarML("MLS System has been turned #(1).",$switchmls);
        xarTplSetMessage($tmsg,'status');
    }
    // Turn off/on Locales Auto Detection
    xarMLSActivateAutoDectection($LADEnabled);

    // Locales
    xarConfigSetVar('Site.MLS.MLSMode', $MLSMode);
    xarConfigSetVar('Site.MLS.DefaultLocale', $defaultLocale);
    xarConfigSetVar('Site.MLS.AllowedLocales', $localesList);

  // Timezone, offset and DST
    if (!xarVarFetch('defaulttimezone','str:1:',$defaulttimezone,'Etc/UTC',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('systemtimezone','str:1:',$systemtimezone,'Etc/UTC',XARVAR_NOT_REQUIRED)) return;

    //system timezone
    $oldsystem =  xarSystemVars::get(sys::CONFIG, 'SystemTimeZone');
    $timezoneinfo = new DateTimezone($systemtimezone);
    xarCore_setSystemVar('SystemTimeZone', $systemtimezone);
    if ($oldsystem != $systemtimezone) {
        $stmsg = xarML("System timezone has been changed to #(1).",$systemtimezone);
        xarTplSetMessage($stmsg,'status');
    }

    //site timezone
    $timezoneinfo = new DateTimezone($defaulttimezone);

    if (!empty($timezoneinfo)) {
        $olddefault = xarConfigGetVar('Site.Core.TimeZone');
        $datetime = new DateTime();
        xarConfigSetVar('Site.Core.TimeZone', $defaulttimezone);
        $offset = $timezoneinfo->getOffset($datetime);
        xarConfigSetVar('Site.MLS.DefaultTimeOffset', $offset);
        if ($olddefault != $defaulttimezone) {
            $msg = xarML("The default time zone has been changed to #(1).",$defaulttimezone);
            xarTplSetMessage($msg,'status');
        }
    } else {
        // unknown/invalid timezone
        xarConfigSetVar('Site.Core.TimeZone', 'Etc/UTC');
        xarConfigSetVar('Site.MLS.DefaultTimeOffset', 0);
        $msg = xarML("There was a problem setting your chosen timezone. The default 'Etc/UTC' timezone has been set.");
        xarTplSetMessage($msg,'error');
    }

    // Call updateconfig hooks
    xarMod::callHooks('module','updateconfig','base', array('module' => 'base'));
    $msg = xarML("Language and place settings have been updated.");
    xarTplSetMessage($msg,'status');

    if (isset($returnurl)) {
        xarResponseRedirect($returnurl);
    } else {
        xarResponseRedirect(xarModURL('base', 'admin', 'languageandlocale'));
    }
    return true;
}
?>