<?php
/**
 * Initialization function
 *
 * @package core modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */


$modversion['name'] = 'privileges';
$modversion['directory'] = 'privileges';
$modversion['id'] = '1098';
$modversion['version'] = '1.0.6';
$modversion['displayname'] = 'Privileges';
$modversion['description'] = 'Manage privileges and site security';
$modversion['official'] = 1;
$modversion['author'] = 'Marc Lutolf, Xarigami Team ';
$modversion['contact'] = 'http://xarigami.com/';
$modversion['admin'] = 1;
$modversion['user'] = 0;
$modversion['securityschema'] = array('Privileges::' => 'name:pid');
$modversion['class'] = 'Core Complete';
$modversion['category'] = 'Users & Groups';

if (false) { //Load and translate once
    xarML('Privileges');
    xarML('Manage privileges and site security');
}
?>