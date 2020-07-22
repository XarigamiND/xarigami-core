<?php
/**
 * Install and Upgarde Xarigami
 *
 * @package Installer
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * InstallXarigami
 *
 * @param none
 * @return bool
 * @throws DATABASE_ERROR
 */
function installer_init()
{
    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'base',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }

    // Initialisation successful
    return true;
}

/**
 * Upgrade Xarigami
 *
 * @param string oldVersion
 * @return bool
 */
function installer_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '1.0':
        // compatability upgrade, nothing to be done
        break;
    }
    return true;
}

/**
 * Delete Installer module
 *
 * @return bool false. This module cannot be removed
 */
function installer_delete()
{
    // this module cannot be removed
    return false;
}

?>