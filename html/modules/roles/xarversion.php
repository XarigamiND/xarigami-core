<?php
/**
 * Roles module initialization
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

$modversion['name']           = 'roles';
$modversion['directory']      = 'roles';
$modversion['id']             = '27';
$modversion['version']        = '1.2.0';
$modversion['displayname']    = 'Roles';
$modversion['description']    = 'User and Group management';
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = 'xardocs/help.txt';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = 'xardocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'Xaraya Core Development Team, Xarigami Team';
$modversion['contact']        = 'http://xarigami.com';
$modversion['admin']          = 1;
$modversion['user']           = 1;
$modversion['class']          = 'Core Complete';
$modversion['category']       = 'Users & Groups';

if (false) { //Load and translate once
    xarML('Roles');
    xarML('User and Group management');

}
?>
