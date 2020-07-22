<?php
/**
 * Xarigami Installer
 *
 * Please do not modify this file: editing this file in any way will prevent it from working.
 * If you are having issues, please drop into #xarigami room at irc://talk.xarigami.com
 *
 * @package installer
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Xarigami Core Development Team
 *
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2012 2skies.com
 */

/**
 * 1. select language
 * ---set language
 * 2. read license agreement
 * ---check agreement state
 * 3. set config.php permissions
 * ---check permissions
 * 4. input database information
 * ---verify, write config.php, install basic dataset (inc. default admin), bootstrap
 * 5. create administrator
 * ---modify administrator information in xar_users
 * 6. pick optional components
 * ---call optional components' init funcs, disable non-reusable areas of install module
 * 7. finished!
*/
@date_default_timezone_set(date_default_timezone_get());


/**
 * Defines for the phases
 *
 */
define ('XARINSTALL_PHASE_WELCOME',             '1');
define ('XARINSTALL_PHASE_LANGUAGE_SELECT',     '2');
define ('XARINSTALL_PHASE_LICENSE_AGREEMENT',   '3');
define ('XARINSTALL_PHASE_SYSTEM_CHECK',        '4');
define ('XARINSTALL_PHASE_SETTINGS_COLLECTION', '5');
define ('XARINSTALL_PHASE_BOOTSTRAP',           '6');

//minimal directory layout must be in the 'normal' config.system.php file

include_once (getcwd().DIRECTORY_SEPARATOR.'bootstrap.php');
if (!class_exists('sys')) die('could not load the bootstrap');
sys::init(sys::MODE_INSTALL);

// Include the core
sys::import('xarigami.xarCore');
// Include some extra functions, as the installer is somewhat special
// for loading gui and api functions
sys::import('modules.installer.xarfunctions');
// Enable debugging always for the installer

xarCore::activateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);

// Basic systems always loaded
sys::import('xarigami.xarLog');
sys::import('xarigami.xarEvt');
sys::import('xarigami.xarException');
// We need xarCoreCache it for xarVar
sys::import('xarigami.caching.core');
sys::import('xarigami.xarVar');
xarCoreCache::init();

sys::import('xarigami.xarServer');

sys::import('xarigami.xarMLS');
sys::import('xarigami.xarTemplate');

// Besides what we explicitly load, we dont want to load
// anything extra for maximum control
$systemArgs = array();
$whatToLoad = XARCORE_SYSTEM_NONE;

xarLog_init($systemArgs);

// Start Exception Handling System very early too
set_exception_handler(array('ExceptionHandlers','bone'));

// Start Event Messaging System - required because xarServer calls its functions
$systemArgs = array('loadLevel' => $whatToLoad);
xarEvt_init($systemArgs, $whatToLoad);

// Start HTTP Protocol Server/Request/Response utilities
$systemArgs = array('enableShortURLsSupport' =>false,
                    'defaultModuleName'      => 'installer',
                    'defaultModuleType'      => 'admin',
                    'defaultModuleFunction'  => 'main',
                    'generateXMLURLs'        => false,
                    'Site.MLS.Enabled'       => true);
xarSerReqRes_init($systemArgs, $whatToLoad);

// Start BlockLayout Template Engine
// This is probably the trickiest part, but we want the installer
// templateable too obviously
$systemArgs = array('enableTemplatesCaching' => false,
                    'themesBaseDirectory'    => 'themes',
                    'defaultThemeDir'        => 'installtheme',
                    'generateXMLURLs'        => false
                    );
xarTpl::init($systemArgs, $whatToLoad);

// Get the install language everytime we request install.php
// We need the var to be able to initialize MLS, but we need MLS to get the var
// So we need something temporarily set, so we can continue
// We set a utf locale intially, otherwise the combo box wont be filled correctly
// for language names which include utf characters
$GLOBALS['xarMLS_mode'] = 'SINGLE';
xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

// Construct an array of the available locale folders
$locale_dir = sys::varpath(). '/locales/';
$allowedLocales = array();
if (is_dir($locale_dir)) {
    if ($dh = opendir($locale_dir)) {
        while (($file = readdir($dh)) !== false) {
            // Exclude the current, previous and the Monotone folder
            // (just for us to be able to test, wont affect users who use a build)
            if($file == '.' || $file == '..' || $file == '_MTN' || $file == 'SCCS' || filetype(realpath($locale_dir . $file)) == 'file' ) continue;
            if(filetype(realpath($locale_dir . $file)) == 'dir' &&
               file_exists(realpath($locale_dir . $file . '/locale.xml'))) {
                $allowedLocales[] = $file;
            }
        }
        closedir($dh);
    }
}

if (empty($allowedLocales)) {
    throw new Exception("The var directory is corrupted: no locale was found!");
}
// A sorted combobox is better
sort($allowedLocales);

// Start Multi Language System
$systemArgs = array('translationsBackend' => 'xml2php',
                    'MLSMode'             => 'BOXED',
                    'defaultLocale'       => $install_language,
                    'allowedLocales'      => $allowedLocales
                    );
xarMLS_init($systemArgs, $whatToLoad);

/**
 * Entry function for the installer
 *
 * @access private
 * @param phase integer the install phase to load
 * @return bool true on success, false on failure
 */
function xarInstallMain()
{
    // let the system know that we are in the process of installing
    xarCoreCache::setCached('installer','installing',1);
    //test

    // Make sure we can render a page
    xarTpl::setPageTitle(xarML('Xarigami installer'));
   if (!xarTpl::setThemeName('installtheme'))
        throw new Exception('You need the installer theme if you want to install Xarigami.');
    // Handle installation phase designation
    xarVarFetch('install_phase','int:1:6',$phase,1,XARVAR_NOT_REQUIRED);

    // Build function name from phase
    $funcName = 'phase' . $phase;

    // if the debugger is active, start it
    if (xarCore::isDebuggerActive()) {
       ob_start();
    }

    // Set the default page title before calling the module function
    xarTpl::setPageTitle(xarML("Installing Xarigami"));

    // Run installer function
    $mainModuleOutput = xarInstallFunc($funcName);

    if (xarCore::isDebuggerActive()) {
        if (ob_get_length() > 0) {
            $rawOutput = ob_get_contents();
            $mainModuleOutput = 'The following lines were printed in raw mode by module, however this
                                 should not happen. The module is probably directly calling functions
                                 like echo, print, or printf. Please modify the module to exclude direct output.
                                 The module is violating Xarigami architecture principles.<br /><br />'.
                                 $rawOutput.
                                 '<br /><br />This is the real module output:<br /><br />'.
                                 $mainModuleOutput;
        }
        ob_end_clean();
    }

    // Render page using the installer.xt page template
    $pageOutput = xarTpl::renderPage($mainModuleOutput,NULL,'default');

    echo $pageOutput;
    return true;
}
xarInstallMain();

?>