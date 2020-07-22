<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
function installer_admin_upgrade()
{
    if(!xarVarFetch('phase','int', $data['phase'], 0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('action','str', $action, '', XARVAR_DONT_SET)) {return;}
    xarVarFetch('install_language','str::',$upgrade_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    if(!xarVarFetch('checkfix','str', $checkfix, '', XARVAR_DONT_SET)) {return;}

    //reset the phase back to the check phase if we're fixing a not run db update
   //this way we make sure we run the checks again
    if (!empty($checkfix)) $data['phase'] = 4;

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
    $data['upgrade'] = array();
    $data['upgrade_language'] = $upgrade_language;
    $data['languages'] = $languages;
    $data['active_step'] = 0;
    $data['iserror'] = 0;//initialize

    $fileversion = XARCORE_VERSION_NUM;
    $data['fileversion'] = $fileversion;
    $fileversionid = XARCORE_VERSION_ID;
    $data['fileversionid'] = $fileversionid;

    $data['fileversionsub'] = XARCORE_VERSION_SUB;
    $data['fileversionrev'] = XARCORE_VERSION_REV;

    $data['dbversion'] =  xarUpgradeGetVar('System.Core.VersionNum');
    $data['dbversionid'] = xarUpgradeGetVar('System.Core.VersionId');
    $data['dbversionsub'] = xarUpgradeGetVar('System.Core.VersionSub');
    $data['dbversionrev'] = xarUpgradeGetVar('System.Core.VersionRev');
    $data['dbversionrev'] = isset($data['dbversionrev'])?  $data['dbversionrev']:'unknown';

    if (!isset($data['dboldversion'])) $data['dboldversion'] =  $data['dbversion'];

    $data['versioncompare']  = xarInstallAPIFunc('compare',
                            array('modName'=>'base',
                                 'modType'=>'versions',
                                 'ver1'=>$data['dbversion'] ,
                                 'ver2'=>$data['fileversion'],
                                 'levels' => 3)
                                  );
    //some versions of xarigami prior to db signatre
     if (($data['dbversionid'] == 'Xarigami') ||
         ($data['dbversionsub'] == 'cumulus') ||
         ($data['dbversionsub'] == 'Cumulus') ||
         ( $data['dbversionid'] == 'Xaraya' &&  $data['dbversion'] == '1.1.6') ) { //xaraya didn't have a version 1.1.6

        $data['upgradable']     = xarInstallAPIFunc('compare',
                            array('modName'=>'base',
                                 'modType'=>'versions',
                                 'ver1'=>'1.1.6' ,
                                 'ver2'=>$data['dbversion'],
                                 'levels' => 3))>=0;


    } elseif ( $data['dbversionid'] == 'Xaraya') {
        $data['upgradable'] =   xarInstallAPIFunc('compare',
                            array('modName'=>'base',
                                 'modType'=>'versions',
                                 'ver1'=>'1.1.1' ,
                                 'ver2'=>$data['dbversion'],
                                 'levels' => 3)) >= 0;
    }

    //determine notefile
    $locale = xarMLSGetCurrentLocale();
    $language = strtolower(xarMLSGetLanguageFromLocale($locale));
    //try locale
    $docinfo = strtolower($fileversionid).'-'.str_replace('.','',$fileversion);
    $moddir = sys::code().'modules/';
    $file1 = 'installer/xardocs/'.$language.'_'.$docinfo.'.html'; //specific locale
    $file2 = 'installer/xardocs/en-us_'.$docinfo.'.html'; //c language locale (en-us)
    $file3 = 'installer/xardocs/'.$docinfo.'.html';//no locale
    $file4 = 'installer/xardocs/en-us_xarigami-nonotes.html'; //c language locale (en-us)
    if (file_exists($moddir.$file1)) {
        $notefile= $file1;
    } elseif (file_exists($moddir.$file2)) {
       $notefile= $file2;
    } elseif (file_exists($moddir.$file3)) {
         $notefile= $file3;
     }else {
        //we use the default for no docs
         $notefile = $file4;
    }

    sys::import('modules.installer.upgrades.versionlist');
    $versionlist = installer_versionlist(array('distro'=>'xarigami'));
    $latestversion = end($versionlist);

    $compactlatest =str_replace('.','',$latestversion);
    $data['notefile'] = $notefile;
    if ($data['phase'] == 1) {
         $data['active_step'] = 1;
    } elseif ($data['phase'] == 2) {
        if (!empty($action) && $action=='updaterev') {
            xarUpgradeSetVar('System.Core.VersionRev', $data['fileversionrev']);
            $data['dbversionrev'] = xarUpgradeGetVar('System.Core.VersionRev');
            $data['active_step'] = 5;
        } else {
            $data['active_step'] = 2;
        }
    } elseif ($data['phase'] == 3) {

        $data['active_step'] = 3;
        $data['uplist'] = array();
        $data['upgrade']['errormessage'] = '';
        $data['upgrade']['message'] = '';

        if (($data['fileversionid'] == 'Xarigami' && $data['dbversionid'] == 'Xarigami')
          //  || (XARCORE_VERSION_SUB == 'cumulus') || (XARCORE_VERSION_SUB == 'Cumulus')
                //no db signature for xarigami below 1.1.6 and maybe some 1.1.7 peeps got caught out if using mtn
             ||($data['fileversionid'] == 'Xaraya' && $data['dbversion'] == '1.1.6')
             ||($data['fileversionid'] == 'Xaraya' && $data['dbversion'] == '1.1.7')
                ) {

            $testlist = $versionlist;
            foreach ($versionlist as $key=>$version) {
                //we only want to run upgrades from the specific db version to the current file version
                //use our version array copy - testlist - as the list we want to use
                //remove the version if it doesn't apply
                if ($data['dbversion'] != $version) unset($testlist[$key]);
                if ($data['dbversion'] == $version) {
                    //we can start our upgrades
                    foreach ($testlist as $version) {
                    $compactversion = str_replace('.','',$version);
                        try {
                            xarUpgrader::loadUpgradeFile('upgrades/xarigami_'.$compactversion.'/main.php');
                        } catch (Exception $e) {
                            continue;
                        }
                        $mainfunc = 'main_'.$compactversion;
                        $data['uplist'][$compactversion] = $mainfunc();
                        $data = array_merge($data,$data['uplist']);
                        if ($data['fileversion'] == $version) {
                            $data['upgrade']['message'] = $data['uplist'][$compactversion]['upgrade']['message'];
                            break;
                        }
                    }
                }
            }

        } elseif ($data['fileversionid'] == 'Xarigami' && $data['dbversionid'] == 'Xaraya' ) {

            $xarayaversionlist = installer_versionlist(array('distro'=>'xaraya'));

            foreach ($xarayaversionlist as $version) {
               $compactversion = str_replace('.','',$version);
                try {
                    xarUpgrader::loadUpgradeFile('upgrades/xaraya_'.$compactversion.'/main.php');
                } catch (Exception $e) {
                    continue;
                }
                $mainfunc = 'main_'.$compactversion;

                if (function_exists($mainfunc)) {
                    $data['uplist'][$compactversion] = $mainfunc();
                    $data = array_merge($data,$data['uplist']);
                }
            }
            $versionlist = installer_versionlist(array('distro'=>'xarigami'));
            //now xarigami updates

            foreach ($versionlist as $version) {
                $compactversion = str_replace('.','',$version);
                try {
                    xarUpgrader::loadUpgradeFile('upgrades/xarigami_'.$compactversion.'/main.php');

                } catch (Exception $e) {
                    continue;
                }
                $mainfunc = 'main_'.$compactversion;
                $data['uplist'][$compactversion] = $mainfunc();
                $data = array_merge($data,$data['uplist']);
                if ($version == $data['fileversion']) {
                    $data['upgrade']['message'] = $data['uplist'][$compactversion]['upgrade']['message'];
                    break;
                }
            }
        }

        //Now work out if we had an error message. Any one error means the upgrade wasn't successful
        //If no error messages, then we really only want to know the final version message
        foreach ($data['uplist'] as $upnumber) {
            foreach($upnumber as $upgrade) {
               if (!empty($upgrade['errormessage']))  {
                    $data['upgrade']['message'] = $upgrade['errormessage'];
                    $data['iserror'] = 1;
                    break (2);
               } else {
                    if ($upnumber == (str_replace('.','',$data['fileversion']))) {
                        $data['upgrade']['message'] = $upgrade['message'];
                         $data['iserror'] = 0;
                        break (2);
                    }
               }
            }
        }

    } elseif ($data['phase'] == 4) {
      //do some checks
       $data['checklist'] = array();
       $data['active_step'] = 4;
       $data['upgrade']['errormessage'] = '';
       $data['upgrade']['message'] = '';
       if (!empty($checkfix)) {
            //we want to actually do a fix
            //some versions of xarigami prior to db signature
            $vercheck = preg_match('/^(check_)([0-9]+)(_.*)$/',$checkfix,$matches);
            $checkfix = str_replace('check','fix',$checkfix);
            if (isset($matches[2]) && !empty($matches[2])) {
                $upgradepath = 'upgrades/xarigami_'.$matches[2].'/updates/' . $checkfix . '.php';
                if (!xarUpgrader::loadUpgradeFile($upgradepath)) {
                    $data['upgrade']['errormessage'] = xarUpgrader::$errormessage;
                }
                $result = $checkfix();
            } else {
                //jojo - handle this better
                 $data['upgrade']['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
            }
        }
       //run the checks again
       if ($data['dbversionid'] == 'Xarigami') {
            $versionlist = array_reverse($versionlist);

            foreach ($versionlist as $version) {
                $compactversion = str_replace('.','',$version);
                $checkfile = 'upgrades/xarigami_'.$compactversion.'/checks/main.php';
                try {
                    xarUpgrader::loadUpgradeFile($checkfile);
                } catch (Exception $e) {
                    continue;
                }
                $mainfunc = 'check_main_'.$compactversion;
                $data['checklist'][$version] = $mainfunc();
                $data = array_merge($data, $data['checklist']);

            }

         //Now work out if we had an error message. Any one error means the upgrade wasn't successful
            //If no error messages, then we really only want to know the final version message
            foreach ($data['checklist'] as $upnumber) {
                foreach($upnumber as $check) {
                   if (!empty($upnumber['upgrade']['errormessage']))  {
                        $data['upgrade']['message'] = $upnumber['upgrade']['errormessage'];
                        $data['iserror'] = 1;
                        break (2);
                   } else {
                        if ($upnumber == (str_replace('.','',$data['fileversion']))) {
                            $data['upgrade']['message'] = $check['message'];
                            $data['iserror'] = 0;
                            break (2);
                        }
                   }
                }
            }
        } else {
            $data['check']['message'] = xarML('Checks can only be run on Xarigami files and databases');
        }

    } elseif ($data['phase'] == 5) {
        //Miscellaneous upgrades that always require running
        //Set config vars that possibly change each upgrade
        sys::import('xarigami.xarVar');
        sys::import('xarigami.variables.config');
        $args = array();
        xarVars::init($args,array());
        sys::import('xarigami.caching.core');


        // Align the db and filesystem version info
        xarConfigSetVar('System.Core.VersionId', XARCORE_VERSION_ID);
        xarConfigSetVar('System.Core.VersionNum', XARCORE_VERSION_NUM);
        xarConfigSetVar('System.Core.VersionRev', XARCORE_VERSION_REV);
        xarConfigSetVar('System.Core.VersionSub', XARCORE_VERSION_SUB);

        sys::import('xarigami.xarMod');
        $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true);
        xarMod::init($systemArgs,array());

        //set some vars we need refreshed and ready for final display page
        $data['modulelisturl'] = xarModURL('modules','admin','list');
        //repeat before final display for update
        $data['fileversion'] = XARCORE_VERSION_NUM;
        $data['fileversionid'] = XARCORE_VERSION_ID;
        $data['fileversionsub'] = XARCORE_VERSION_SUB;
        $data['fileversionrev'] = XARCORE_VERSION_REV;

        $data['dbversion'] =  xarConfigGetVar('System.Core.VersionNum');
        $data['dbversionid'] = xarConfigGetVar('System.Core.VersionId');
        $data['dbversionsub'] = xarConfigGetVar('System.Core.VersionSub');
        $data['dbversionrev'] = xarConfigGetVar('System.Core.VersionRev');
        $data['dbversionrev'] = isset($data['dbversionrev'])?  $data['dbversionrev']:'unknown';

        $anonuid = xarConfigGetVar('Site.User.AnonymousUID');
        $anonuid = !empty($anonuid) ? $anonuid : 2;
        if (!defined('_XAR_ID_UNREGISTERED'))  define('_XAR_ID_UNREGISTERED', $anonuid);
        if ($action == 'finished') {
         xarMod::loadDbInfo('privileges', 'privileges');
        sys::import('xarigami.xarSession');
        sys::import('xarigami.xarUser');
        sys::import('xarigami.xarSecurity');

        //flush the database
        xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));
        //regenerate themes
         sys::import('xarigami.xarTheme');
        /* jojo - not here

         $regenerated = xarMod::apiFunc('themes', 'admin', 'regenerate');
        if (!$regenerated) {
            xarTplSetMessage(xarML('There was an issue regenerating the themes list.
            Please go to your Theme module administration and regenerate the themes listing.'),'warning');
        }
        */
    }
        $data['active_step'] = 5;

    }

    return $data;
}
?>
