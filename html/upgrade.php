<?php
/**
 * Xarigami Upgrade
 *
 * Please do not modify this file: editing this file in any way will prevent it from working.
 * If you are having issues, please drop into #xarigami room at irc://talk.xarigami.com
 *
 * @package upgrader
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Upgrade
 * @copyright (C) 2007-2012 2skies.com
 */

/**
 * 1. select language
 * ---set language
 * 2. Prepare for upgrade
 * ---check versions
 * 3. Upgrade
 * ---do database upgrades if necessary
 * 4. Checks and finalise upgrade
 * ---final db writes, run db health check if required
 * 5. finished!
*/

@date_default_timezone_set(date_default_timezone_get());

//minimal directory layout must be in the 'normal' config.system.php file

include_once (getcwd().DIRECTORY_SEPARATOR.'bootstrap.php');
if (!class_exists('sys')) die('could not load the bootstrap');
sys::init(sys::MODE_UPGRADE);

// Include the core
sys::import('xarigami.xarCache');
xarCache::init();
sys::import('xarigami.xarCore');
sys::import('xarigami.variables.system');
//jojo - why are we writing the timezone? can we get away without this?
//xarCore_setSystemVar('SystemTimeZone', date_default_timezone_get());
// Include some extra functions, as the installer is somewhat special
// for loading gui and api functions
sys::import('modules.installer.xarfunctions');
// Enable debugging always for the installer
xarCore::activateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);

// Basic systems always loaded
sys::import('xarigami.xarLog');
sys::import('xarigami.xarEvt');
sys::import('xarigami.xarException');
sys::import('xarigami.xarVar');
sys::import('xarigami.xarServer');
sys::import('xarigami.xarMLS');
sys::import('xarigami.xarTemplate');

// Besides what we explicitly load, we dont want to load
// anything extra for maximum control
$systemArgs = array();
$whatToLoad = XARCORE_SYSTEM_NONE;

// Start Logging Facilities as soon as possible
$systemArgs = array('loggerName' => xarSystemVars::get(null,'Log.LoggerName', true),
                    'loggerArgs' => xarSystemVars::get(null,'Log.LoggerArgs', true),
                    'level'      => xarSystemVars::get(null,'Log.LogLevel', true));
xarLog_init($systemArgs, $whatToLoad);

// Start Exception Handling System very early too
$systemArgs = array('enablePHPErrorHandler' => xarSystemVars::get(sys::CONFIG, 'Exception.EnablePHPErrorHandler'));
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
xarTpl_init($systemArgs, $whatToLoad);


// Get the install language everytime we request install.php
// We need the var to be able to initialize MLS, but we need MLS to get the var
// So we need something temporarily set, so we can continue
// We set a utf locale intially, otherwise the combo box wont be filled correctly
// for language names which include utf characters
$GLOBALS['xarMLS_mode'] = 'SINGLE';
xarVarFetch('upgrade_language','str::',$upgrade_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

// Construct an array of the available locale folders
$locale_dir = sys::varpath(). '/locales/';
$allowedLocales = array();
if (is_dir($locale_dir)) {
    if ($dh = opendir($locale_dir)) {
        while (($file = readdir($dh)) !== false) {
            // Exclude the current, previous and the Monotone folder
            // (just for us to be able to test, wont affect users who use a build)
            if ($file == '.' || $file == '..' || $file == '_MTN' || $file == 'SCCS' || filetype(realpath($locale_dir . $file)) == 'file' ) continue;
            if (filetype(realpath($locale_dir . $file)) == 'dir' &&
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
                    'defaultLocale'       => $upgrade_language,
                    'allowedLocales'      => $allowedLocales
                    );
xarMLS_init($systemArgs, $whatToLoad);

class xarUpgrader extends xarObject
{
    const XARUPGRADE_LANGUAGE           = 0; //language
    const XARUPGRADE_PHASE_WELCOME      = 1;
    const XARUPGRADE_PREPARE            = 2;
    const XARUPGRADE_DATABASE           = 3;
    const XARUPGRADE_MISCELLANEOUS      = 4;
    const XARUPGRADE_PHASE_COMPLETE     = 5;

    private static $upgradeinstance          = null;

    public static $errormessage       = '';

    protected function __construct()
    {
        // let the system know that we are in the process of installing
        xarCoreCache::setCached('Upgrade', 'upgrading',1);
        // Make sure we can render a page
        xarTplSetPageTitle(xarML('Xarigami Upgrade'));

        if(!xarTplSetThemeName('installtheme'))
            throw new Exception('You need the install theme if you want to upgrade Xarigami.');

        // if the debugger is active, start it
        if (xarCore::isDebuggerActive()) {
            ob_start();
        }

        // Set the default page title before calling the module function
        xarTplSetPageTitle(xarML("Upgrading Xarigami"));

        $output =  xarInstallFunc('upgrade');
        $this->renderPage($output);

    }

    private function renderPage($output)
    {
        if (xarCore::isDebuggerActive()) {
            if (ob_get_length() > 0) {
                $rawOutput = ob_get_contents();
                 $output = 'The following lines were printed in raw mode by module, however this
                                     should not happen. The module is probably directly calling functions
                                     like echo, print, or printf. Please modify the module to exclude direct output.
                                     The module is violating Xarigami architecture principles.<br /><br />'.
                                     $rawOutput.
                                     '<br /><br />This is the real module output:<br /><br />'.
                                      $output;
            }
            ob_end_clean();
        }

        $pageOutput = xarTpl_renderPage($output,NULL,'default');
        echo $pageOutput;
        return true;
    }

    public static function getUpgradeInstance()
    {
        if (null === self::$upgradeinstance) {
            self::$upgradeinstance = new self();
        }
        return self::$upgradeinstance;
    }

    public static function loadUpgradeFile($path)
    {
        $checkpath = sys::code() . 'modules/installer/' . $path;
        if (!file_exists($checkpath)) {
            self::$errormessage = xarML("The required file '#(1)' was not found.", $checkpath);
            return false;
        }
        $importpath = 'modules/installer/' . $path;
        $importpath = str_replace('/','.',$importpath);
        $importpath = substr($importpath,0,strlen($importpath)-4);
        sys::import($importpath);
        return true;
    }
}

//Call the upgrader
$upgrader = xarUpgrader::getUpgradeInstance();

?>
