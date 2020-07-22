<?php
/**
 * Blocks initialization
 *
 * @package Xarigami modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
$modversion['name']                 = 'blocks';
$modversion['directory']            = 'blocks';
$modversion['id']                   = '13';
$modversion['displayname']          = 'Blocks';
$modversion['version']              = '1.1.0';
$modversion['description']          = 'Administration of block content and block groups';
$modversion['credits']              = '';
$modversion['help']                 = 'xardocs/changelog.txt';
$modversion['changelog']            = 'xardocs/changelog.txt';
$modversion['license']              = '';
$modversion['homepage']              = 'http://xarigami.com/project/xarigami_core';
$modversion['official']             = 1;
$modversion['author']               = 'Xarigami Team ';
$modversion['contact']              = 'http:/xarigami.com';
$modversion['admin']                = 1;
$modversion['user']                 = 0;
$modversion['class']                = 'Core Admin';
$modversion['category']             = 'Appearance';

if (false) { //Load and translate once
    xarML('Blocks');
    xarML('Administration of block content and block groups');
}
?>