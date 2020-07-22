<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Phase 4: Database Settings Page
 *
 * @access private
 * @return array of default values for the database creation
 */
function installer_admin_phase4()
{
    //jojo - replace this check with a better one once we finish new installer
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarFetch('invalid','str::',$invalid, '', XARVAR_NOT_REQUIRED);

    if (!empty($invalid)) $invalid = unserialize($invalid);
    sys::import('xarigami.variables.system');
    sys::import('xarigami.variables.config');
    // now we can be sure we have the files and config vars and config is writeable
    xarCore_setSystemVar('SystemTimeZone', date_default_timezone_get());

    $data['invalid']['prefix'] = isset($invalid['prefix']) ?$invalid['prefix'] : array();
    $data['invalid']['username'] = isset($invalid['username']) ?$invalid['username'] : array();
    $data['invalid']['database'] = isset($invalid['database']) ?$invalid['database'] : array();
    // Reuse the config file settings if we have a DB.UserName in there.
    // Otherwise check for SQLite driver and use appropriate defaults from code

    if (xarSystemVars::get(sys::CONFIG, 'DB.UserName') != '') {
        $data['database_host']       = xarSystemVars::get(sys::CONFIG, 'DB.Host');
        $data['database_username']   = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
        $data['database_name']       = xarCore_getSystemvar('DB.Name');
        $data['database_prefix']     = xarCore_getSystemvar('DB.TablePrefix');
        $data['database_type']       = xarCore_getSystemvar('DB.Type');
        $data['database_charset']    = xarCore_getSystemvar('DB.Charset');
    } else {
       if (extension_loaded('mysql')) {
            if (version_compare(phpversion(),'5.3.0') < 0) {
                $hostvalue = 'localhost';
            } else {
                $hostvalue = '127.0.0.1';
            }
            $data['database_host']       = "$hostvalue";
            $data['database_username']   = '';
            $data['database_name']       = 'xarigami';
            $data['database_type']       = 'mysql';
        } elseif (extension_loaded('sqlite')) {
            $data['database_host']       = './var';
            $data['database_username']   = '';
            $data['database_name']       = 'xarigami.sqlite';
            $data['database_type']       = 'sqlite';
        }

        $data['database_charset']    = 'utf8';
        $data['database_prefix']     = 'xar';
    }
    $data['database_password']   = '';//xarCore_getSystemvar('DB.Password');

    // Supported  Databases:
    $data['database_types']      = array('mysql'    => array('name' => 'MySQL (mysql)'   , 'available' => extension_loaded('mysql')),
                                         'mysqli' => array('name' => 'Improved MySQL (mysqli)', 'available' => extension_loaded('mysqli')),
                                         'postgres' => array('name' => 'PostgreSQL (postgres)', 'available' => extension_loaded('pgsql')),
                                         'sqlite'   => array('name' => 'SQLite (sqlite)'  , 'available' => extension_loaded('sqlite')),
                                         // use portable version of OCI8 driver to support ? bind variables
                                         'oci8po'   => array('name' => 'Oracle 8+ (oci8) [not supported]'  , 'available' => extension_loaded('oci8')),
                                         'mssql'    => array('name' => 'MSSQL Server (mssql) [not supported]' , 'available' => extension_loaded('mssql')),
                                        );

    $data['database_charsets'] = array(
                             'utf8'    => 'utf8 (Default)',
                             'big5'    => 'Big5 Traditional Chinese',
                             'koi8r'   => 'KOI8-R Relcom Russian',
                             'ujis'    => 'EUC-JP Japanese',
                             'sjis'    => 'Shift-JIS Japanese',
                             'latin1'  => 'cp1252 West European',
                             'latin2'  => 'ISO 8859-2 Central European',
                             'ascii'   => 'US ASCII',
                             'gb2312'  => 'GB2312 Simplified Chinese',
                             'cp1250'  => 'Windows Central European'
                                );
    $data['language'] = $install_language;
    $data['phase'] = 4;
    $data['phase_label'] = xarML('Step Four');

    return $data;
}

?>