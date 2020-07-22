<?php
/**
 * Module initialization
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */
$modversion['name']               = 'modules';
$modversion['directory']          = 'modules';
$modversion['id']                 = '1';
$modversion['version']            = '2.3.4';
$modversion['displayname']        = 'Modules';
$modversion['description']        = 'Install and configure modules and hooks';
$modversion['credits']            = 'xardocs/credits.txt';
$modversion['help']               = '';
$modversion['changelog']          = 'xardocs/changelog.txt';
$modversion['license']            = '';
$modversion['official']           = 1;
$modversion['author']             = 'Xarigami Core Development Team';
$modversion['contact']            = 'http://xaraya.com';
$modversion['admin']              = 1;
$modversion['user']               = 0;
$modversion['class']              = 'Core Admin';
$modversion['category']           = 'Global';

if (false) { //Load and translate once
    xarML('Modules');
    xarML('Install and configure modules and hooks');
}
?>