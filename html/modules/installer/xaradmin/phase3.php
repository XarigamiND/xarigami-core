<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Phase 3: Check system settings
 *
 * @access private
 * @param agree string
 * @return array data for the template display
 */
function installer_admin_phase3()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    if (!xarVarFetch('agree','regexp:(agree|disagree)',$agree)) return;

    $retry=1;

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponseRedirect('install.php?install_phase=2&install_language='.$install_language.'&retry=1');
    }

    //Defaults
    $systemConfigIsWritable   = false;
    $systemConfigDistFileIsReadable   = false;
    $cacheTemplatesIsWritable = false;
    $rssTemplatesIsWritable   = false;
    $metRequiredPHPVersion    = false;

    $systemVarDir             = sys::varpath();
    $cacheDir                 = $systemVarDir . XARCORE_CACHEDIR;
    $cacheTemplatesDir        = $systemVarDir . XARCORE_TPL_CACHEDIR;
    $rssTemplatesDir          = $systemVarDir . XARCORE_RSS_CACHEDIR;
   // $adodbTemplatesDir        = $systemVarDir . XARCORE_ADODB_CACHEDIR;
    $stylesTemplatesDir        = sys::web() . 'var' . XARCORE_STYLES_CACHEDIR; // Must use the var in the web root
    $systemConfigFile         = $systemVarDir . '/' . sys::CONFIG;
    $systemConfigDistFile      = $systemVarDir . '/' . sys::CONFIG . '.dist';
    $phpLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/php';
    $xmlLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/xml';
    $logFile               = $systemVarDir . '/logs/log.txt';

    $php5version = -1; //declare var indicating php5 or not, in this case -1 unknown
    if (function_exists('version_compare')) {
        if (version_compare(PHP_VERSION,'5.3.0','>=')) $metRequiredPHPVersion = true;
        if (version_compare(PHP_VERSION,'5.0.0','>=')) {
            $php5version = 1;
        } else {
            $php5version = 0; //we know the version and it is less than 5
        }
    }

    // If there is no system.config file, attempt to create it
    $varDirIsWriteable = FALSE;
    $systemConfigDistFileIsReadable = is_readable($systemConfigDistFile);
    $systemConfigIsWritable     = is_writable($systemConfigFile); // @todo: doesn't seem to work that well under Windows!
    $varDirIsWriteable = is_writeable($systemVarDir);

    if ($systemConfigDistFileIsReadable && !file_exists($systemConfigFile) && $varDirIsWriteable)
    {
            try {
                copy($systemConfigDistFile, $systemConfigFile);
            } catch (Exception $e) {
                //while the directory should be writeable in Windows we may get some issues so give the same message for now as not writeable
                die('<div style="font-family:  Verdana, Helevetica, sans-serif; font-size: large;margin: 2em;text-align: center; padding: 2em; background-color: #F0FFFF; border: solid 1px #C0FFFF; border-radius: 10px; box-shadow: rgba(0,0,0,0.4) 0px 10px 15px;">
                Your configuration file (config.system.php) appears to be missing.
                <br />We tried to create it for you but it seems that your xarigami <strong>var</strong> directory is not writable.
                <br/>Please ensure the directory is writeable  and <a href="install.php">try again</a>, or refer to the <a href="http://xarigami.org/resources/installing_xarigami">Xarigami installation instructions </a> for more information.</div>');
            }

        // We need to ensure we have at least the same settings that currently for the phase 4
        // (otherwise it breaks in some setups)
        // Bootstrap does a better job to determine the most suitable values
        // @todo prompt the user before creating the system.config.php and doing this.
        // if we are here, it means we could copy the file, so it is writeable.
        sys::import('xarigami.variables.system');
        xarSystemVars::set(NULL, 'webDir', sys::$pathWeb->forSys());
        xarSystemVars::set(NULL, 'codeDir', sys::$pathCode->forSys());
        xarSystemVars::set(NULL, 'libDir', xarPath::make(sys::$pathLib->append('..'))->forSys());
        xarSystemVars::set(NULL, 'siteDir', sys::$pathSites->forSys());
        $systemConfigIsWritable = true;
    } elseif  (!file_exists($systemConfigFile)) {
                die('<div style="font-family:  Verdana, Helevetica, sans-serif; font-size: large;margin: 2em;text-align: center; padding: 2em; background-color: #F0FFFF; border: solid 1px #C0FFFF; border-radius: 10px; box-shadow: rgba(0,0,0,0.4) 0px 10px 15px;">
                Your configuration file (config.system.php) appears to be missing.
                <br />We tried to create it for you but it seems that your xarigami <strong>var</strong> directory is not writable.
                <br/>Please ensure the directory is writeable  and <a href="install.php">try again</a>, or refer to the <a href="http://xarigami.org/resources/installing_xarigami">Xarigami installation instructions </a> for more information.</div>');
    }
    //now make sure we load debugger
    //xarCore::activateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);
    // back in install.php. What did make the install failed? Is it still happening?


    $cacheIsWritable            = check_dir($cacheDir);
    $logIsWritable              = is_writable($logFile);
    $cacheTemplatesIsWritable   = (check_dir($cacheTemplatesDir) || @mkdir($cacheTemplatesDir, 0700));
    $rssTemplatesIsWritable     = (check_dir($rssTemplatesDir) || @mkdir($rssTemplatesDir, 0700));
    //$adodbTemplatesIsWritable   = (check_dir($adodbTemplatesDir) || @mkdir($adodbTemplatesDir, 0700));
    $stylesTemplatesIsWritable   = (check_dir($stylesTemplatesDir) || @mkdir($stylesTemplatesDir, 0700));
    $phpLanguageFilesIsWritable = xarMLS__iswritable($phpLanguageDir);
    $xmlLanguageFilesIsWritable = xarMLS__iswritable($xmlLanguageDir);
    $maxexectime = trim(ini_get('max_execution_time'));
    $memLimit = trim(ini_get('memory_limit'));
    $memLimit = empty($memLimit) || $memLimit === FALSE ? xarML('Undetermined') : $memLimit;
    $memVal = mem_bytes($memLimit);

    //Comment out - add back later along with relevant template changes - consider when we require php 5.3
    //$memRealPathCache = trim(ini_get('realpath_cache_size'));
    //$memRealPathCache = empty($memRealPathCache) || $memRealPathCache === FALSE ? xarML('Undetermined') : $memRealPathCache;
    //$memRealPathCacheVal = mem_bytes($memRealPathCache);

    // Extension Check
    $data['xmlextension']             = extension_loaded('xml');
    $data['mysqlextension']           = extension_loaded('mysql');
    $data['pgsqlextension']           = extension_loaded('pgsql');
    $data['sqliteextension']          = extension_loaded('sqlite');
    $data['mysqliextension']          = extension_loaded('mysqli');
    $data['php5version'] = $php5version;

    // This is called xsl in PHP5.x Should check for that when php version is 5 or higher

    $data['xslextension']           = extension_loaded ('xsl');
    $data['ldapextension']          = extension_loaded ('ldap');
    $data['gdextension']            = extension_loaded ('gd');
    $data['mbstringextension']      = extension_loaded ('mbstring');
    $data['curlextension']          = extension_loaded ('curl');
    $data['xmlrpcextension']          = extension_loaded ('xmlrpc');

    $data['metRequiredPHPVersion']    = $metRequiredPHPVersion;
    $data['phpVersion']               = PHP_VERSION;
    $data['cacheDir']                 = $cacheDir;
    $data['cacheIsWritable']          = $cacheIsWritable;
    $data['logIsWritable']             = $logIsWritable ? 1:0;
    $data['logFile'] = $logFile ;
    $data['cacheTemplatesDir']        = $cacheTemplatesDir;
    $data['cacheTemplatesIsWritable'] = $cacheTemplatesIsWritable;
    $data['rssTemplatesDir']          = $rssTemplatesDir;
    $data['rssTemplatesIsWritable']   = $rssTemplatesIsWritable;
    //$data['adodbTemplatesDir']        = $adodbTemplatesDir;
    //$data['adodbTemplatesIsWritable'] = $adodbTemplatesIsWritable;
    $data['stylesTemplatesDir']     = $stylesTemplatesDir;
    $data['stylesTemplatesIsWritable'] = $stylesTemplatesIsWritable;
    $data['systemConfigFile']         = $systemConfigFile;
    $data['systemConfigIsWritable']   = $systemConfigIsWritable;
    $data['phpLanguageDir']             = $phpLanguageDir;
    $data['phpLanguageFilesIsWritable'] = $phpLanguageFilesIsWritable;
    $data['xmlLanguageDir']             = $xmlLanguageDir;
    $data['xmlLanguageFilesIsWritable'] = $xmlLanguageFilesIsWritable;
    $data['maxexectime']                = $maxexectime;
    $data['maxexectimepass']            = $maxexectime<=30;
    $data['memory_limit']               = $memLimit;
    $data['memory_warning']             = $memLimit == xarML('Undetermined');
    $data['metMinMemRequirement']       = $memVal >= 8 * 1024 * 1024 || $data['memory_warning'];
    //add back later along with relevant template changes - consider when we require php 5.3
    //$data['memRealpathCache'] = $memRealPathCacheVal;
    //$data['metRealpathCacheRequirement'] = $memRealPathCacheVal >= 800 * 1024;

    $data['language']    = $install_language;
    $data['phase']       = 3;
    $data['phase_label'] = xarML('Step Three');

    return $data;
}

/**
 * Check whether directory permissions allow to write and read files inside it
 *
 * @access private
 * @param string dirname directory name
 * @return bool true if directory is writable, readable and executable
 */
function check_dir($dirname)
{
    //don't use filenames preceded by . for windows servers
    if (@touch($dirname . '/check_dir')) {
        $fd = @fopen($dirname . '/check_dir', 'r');
        if ($fd) {
            fclose($fd);
            unlink($dirname . '/check_dir');
        } else {
            return false;
        }
    } else {
        return false;
    }
    return true;
}

function mem_bytes($mem)
{
    $memVal = substr($mem,0,strlen($mem)-1);
    $memVal = intval($mem, 10);
    $memBase = strtolower(substr($mem,-1,1));
    switch($memBase) {
        case 'g': $memVal *= 1024;
        case 'm': $memVal *= 1024;
        case 'k': $memVal *= 1024;
    }
    return $memVal;
}

?>
