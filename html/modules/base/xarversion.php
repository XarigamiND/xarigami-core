<?php
/**
 * Base Module Initialisation
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
$modversion['name']         = 'base';
$modversion['directory']    = 'base';
$modversion['id']           = '68';
$modversion['displayname']  = 'Site Base';
$modversion['version']      = '0.1.0';
$modversion['description']  = 'Site wide settings';
$modversion['credits']      = 'xardocs/credits.txt';
$modversion['help']         = 'xardocs/help.txt';
$modversion['changelog']    = 'xardocs/changelog.txt';
$modversion['license']      = 'xardocs/license.txt';
$modversion['official']     = 1;
$modversion['author']       = 'Xarigami Team ';
$modversion['contact']      = 'http://xarigami.com';
$modversion['admin']        = 1;
$modversion['user']         = 1;
$modversion['class']        = 'Core Admin';
$modversion['category']     = 'Global';

if (false) { //Load and translate once
    xarML('Site Base');
    xarML('Site wide settings');
}
?>
