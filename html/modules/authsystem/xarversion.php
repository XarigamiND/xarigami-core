<?php
/**
 * Initialise the Authsystem module
 *
 * @package Xaraya modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
$modversion['name']                 = 'authsystem';
$modversion['directory']            = 'authsystem';
$modversion['id']                   = '42';
$modversion['version']              = '1.0.3';
$modversion['displayname']          = 'Authsystem';
$modversion['description']          = 'Default authentication module';
$modversion['credits']              = 'xardocs/credits.txt';
$modversion['help']                 = 'xardocs/help.txt';
$modversion['changelog']            = 'xardocs/changelog.txt';
$modversion['license']              = 'docs/license.txt';
$modversion['official']             = 1;
$modversion['author']               = 'Xarigami Core Development Team';
$modversion['contact']              = 'info@xarigami.com';
$modversion['homepage']             = 'http://xarigami.com/projects/xarigami_core';
$modversion['admin']                = 1;
$modversion['user']                 = 0;
$modversion['class']                = 'Core Authentication';
$modversion['category']             = 'Security';
$modversion['dependencyinfo']   = array(
                                    0 => array(
                                            'name' => 'core',
                                            'version_ge' => '1.2.0'
                                         )
                                        );
if (false) { //Load and translate once
    xarML('Authsystem');
    xarML('Default authentication module');
}
?>