<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Bootstrap Xarigami
 *
 * @access private
 */
function installer_admin_bootstrap()
{
    //jojo - replace this check with a better one once we finish new installer
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarCoreCache::setCached('installer','installing', true);
    // create the default roles and privileges setup
    sys::import('modules.privileges.xarsetup');
    initializeSetup();


    //jojo Issue xgami-000147
    // prevent regeneration during install running upgrade twice
    // All modules are calling upgrade from the init function for consistency
    // and have been installed, but the version number may not be up to date
    // Now make sure they have the up to date version number
    //load moduleapi
    xarLogMessage("starting version upgrade");
    $updatelist = array('base','blocks','privileges','roles','themes','modules','dynamicdata','mail');

    foreach ($updatelist as $mod)
    {
        $regid=xarMod::getId($mod);
        if (isset($regid)) {
            $versionupdated = xarMod::apiFunc('modules','admin','updateversion',array('regId'=>$regid));
            if (!$versionupdated) {
               //we fall over? fix this later
           }
        } else {
        //hmmm
        }
    }

    //jojo - this will rerun upgrade, and reload again any sql statements
    if (!xarMod::apiFunc('modules', 'admin', 'regenerate')) return;

    //check authsystem
    $regid=xarMod::getId('authsystem');
    if (empty($regid)) {
        die(xarML('I cannot load the Authsystem module. Please make it available and reinstall'));
    }


    // Set the state and activate the following modules
    // jojodee - Modules, base, installer, blocks and themes are already activated in base init
    // We run them through roles and privileges and authsystem as special cases that need an 'activate' phase. Others don't??
   /* with Cumulus version, we don't seem to need this. The modules are activated from the initial install - as long as load order is maintained.
       Be careful with what is put in the upgrades as the load order must be considered, else it needs to go in Upgrade.php
   $modlist=array('roles','privileges','authsystem');
    foreach ($modlist as $mod) {
        // Set state to inactive first
        $regid=xarMod::getId($mod);
        if (isset($regid)) {
            if (!xarMod::apiFunc('modules','admin','setstate',
                                array('regid'=> $regid, 'state'=> XARMOD_STATE_INACTIVE))) return;

            // Then run activate function
            if (!xarMod::apiFunc('modules','admin','activate', array('regid'=> $regid))) return;
        }
    }
   */

    // Initialise and activate mail, dynamic data
    $modlist = array('mail', 'dynamicdata');
    foreach ($modlist as $mod) {
        // Initialise the module
        $regid = xarMod::getId($mod);
        if (isset($regid)) {
            if (!xarMod::apiFunc('modules', 'admin', 'initialise', array('regid' => $regid))) return;
            // Activate the module
            if (!xarMod::apiFunc('modules', 'admin', 'activate', array('regid' => $regid))) return;
        }
        xarLogMessage('INSTALLER: finished installing '.$mod);
    }
    
    
    try {
        xarMod::apiFunc('themes', 'admin', 'regenerate');
    } catch (Exception $e) {
        throw new Exception('theme regenerate');
    }

    // Set the state and activate the following themes
    //Default and installer should already be installed
    $themelist = array('installtheme','print','rss');
    
    foreach ($themelist as $theme) {
        // Set state to inactive
        $regid = xarThemeGetIDFromName($theme);
        if (isset($regid)) {
            if (!xarMod::apiFunc('themes','admin','setstate', array('regid'=> $regid,'state'=> XARTHEME_STATE_INACTIVE))){
                return;
            }
            // Activate the theme
            if (!xarMod::apiFunc('themes','admin','activate', array('regid'=> $regid)))
            {
                return;
            }
        }
    }

/* --------------------------------------------------------
 * Create wrapper DD objects for the native itemtypes of the privileges module
 */

    if (!xarMod::apiFunc('privileges','admin','createobjects')) return;

    xarResponseRedirect(xarModURL('installer', 'admin', 'create_administrator',array('install_language' => $install_language)));
}

?>