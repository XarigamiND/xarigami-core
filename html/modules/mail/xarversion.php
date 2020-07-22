<?php
/**
 * Initialization function
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 * @author John Cox 
 */

$modversion['name']           = 'mail';
$modversion['directory']      = 'mail';
$modversion['id']             = '771';
$modversion['displayname']    = 'Mail';
$modversion['version']        = '0.2.0';
$modversion['description']    = 'Mail handling utility module';
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = 'xardocs/help.txt';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = 'xardocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'Xarigami Team';
$modversion['contact']        = 'http://xarigami.com';
$modversion['admin']          = 1;
$modversion['user']           = 0;
$modversion['class']          = 'Core Complete';
$modversion['category']       = 'Global';

if (false) { //Load and translate once
    xarML('Mail');
    xarML('Mail handling utility module');
}
?>