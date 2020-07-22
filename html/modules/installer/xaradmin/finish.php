<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

function installer_admin_finish()
{
    //jojo - replace this check with a better one once we finish new installer
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));
    $regenerated = xarMod::apiFunc('themes', 'admin', 'regenerate');
    if (!$regenerated) {
        xarTplSetMessage(xarML('There was an issue regenerating the themes list. 
        Please go to your Theme module administration and regenerate the themes listing.'),'warning');
    }
    xarTplSetThemeName('default');
    xarResponseRedirect('index.php');
}


?>