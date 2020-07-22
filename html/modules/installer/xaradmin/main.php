<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * @access public
 * @return array an array of template values
 */
function installer_admin_main()
{
    $data['phase'] = 0;
    $data['phase_label'] = xarML('Welcome to Xarigami');
    return $data;
}
?>