<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Phase 2: Accept License Page
 *
 * @access private
 * @return array
 */
function installer_admin_phase2()
{
    //jojo - replace this check with a better one once we finish new installer
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarFetch('retry','int:1',$data['retry'],NULL, XARVAR_NOT_REQUIRED);

    $data['language'] = $install_language;
    $data['phase'] = 2;
    $data['phase_label'] = xarML('Step Two');

    return $data;
}

?>