<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

function installer_versionlist($args=array())
{
    extract($args);
    $versionlist= array();
    //only list versions here that have actual equivalent upgrade version directories
    if (isset($distro) && $distro == 'xaraya')
    {
         $versionlist = array(
                            '1.1.1',
                            '1.1.2',
                            '1.1.3',
                           // '1.1.4',
                           // '1.1.5',
                           // '1.2.0',
                            '1.2.1',
                           // '1.2.2',
                           // '1.2.3'
                        );
    } else {
        $versionlist = array(
                            '1.1.4',
                            '1.1.6',
                            '1.1.7',
                            '1.1.8',
                            '1.2.0',
                            '1.3.0',
                            '1.3.1',
                            '1.3.2',
                            '1.3.3',
                            '1.3.4',
                            '1.3.5',
                            '1.4.0',
                            '1.4.1',
                            '1.4.2',
                            '1.4.3',
                            '1.5.0',
                            '1.5.1',
                            '1.5.2',
                            '1.5.3',
                            '1.5.4',
                            '1.5.5',
                        );
    }
    return $versionlist;
}
?>