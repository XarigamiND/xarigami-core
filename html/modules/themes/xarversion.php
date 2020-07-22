<?php
/**
 * Themes administration and initialization
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

$modversion['name']           = 'themes';
$modversion['directory']      = 'themes';
$modversion['id']             = '70';
$modversion['version']        = '1.9.3';
$modversion['displayname']    = 'Themes';
$modversion['description']    = 'Configure themes, change site appearance';
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = '';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = '';
$modversion['official']       = 1;
$modversion['author']         = 'Xarigami Team';
$modversion['contact']        = 'http://xarigami.com';
$modversion['admin']          = 1;
$modversion['user']           = 0;
$modversion['class']          = 'Core Admin';
$modversion['category']       = 'Appearance';

if (false) { //Load and translate once
    xarML('Themes');
    xarML('Configure themes, change site appearance');
}
?>