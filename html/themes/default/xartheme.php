<?php
/**
 * Skyline Theme (default theme)
 *
 * @package Xarigami Core
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage skyline system default theme
 * @copyright (C) 2011-2012 2skies.com
 * @link http://xarigami.com
 * @author Xarigami Team
 */

$themeinfo['name'] = 'default';
$themeinfo['displayname'] = 'System Theme';
$themeinfo['id'] = '1105';
$themeinfo['directory'] = 'default';
$themeinfo['author'] = 'Xarigami Team';
$themeinfo['homepage'] = 'http://xarigami.com';
$themeinfo['email'] = 'talktous@xarigami.com';
$themeinfo['description'] = 'Default theme';
$themeinfo['contact'] = 'http://xarigami.com';
$themeinfo['publish_date'] = '08/03/2012';
$themeinfo['license'] = 'GPL';
$themeinfo['version'] = '3.1.0';
$themeinfo['xar_version'] = '1.4';
$themeinfo['bl_version'] = '1.0';
$themeinfo['class'] = '0';

$themevars['pagetimer'] = array (
  'name' => 'pagetimer',
  'value' => '1',
  'prime' => '1',
  'description' => 'Display a page timer in the page',
  'config' => 
  array (
    'propertyname' => 'checkbox',
    'label' => 'Display a page timer in the page',
    'propargs' => 
    array (
      'xv_displaycolumns' => '3',
      'xv_displaydelimiter' => ',',
      'xv_display_layout' => 'default',
      'xv_size' => '1',
      'xv_cansearch' => '1',
      'xv_allowempty' => '1',
      'xv_hasvalue' => '<!--xarPageTimer--> ',
    ),
    'varcat' => 'miscellaneous',
    'status' => '1',
    'type' => '14',
    'default' => '1',
  ),
);
$themevars['shownavbar'] = array (
  'name' => 'shownavbar',
  'value' => '1',
  'prime' => '1',
  'description' => 'Display the top admin menu nav bar (always on with Admin Dashboard).',
  'config' =>  array (
            'propargs' =>  array (
                'xv_hasvalue' => 'Nav bar on',
                'xv_hasnovalue' => 'Nav bar off',
                'xv_display_layout' => 'default',
                'xv_cansearch' => '1',
                'xv_allowempty' => '1',
                ),
                'label' => 'Display admin Nav Bar',
                'default' => '1',
                'status' => '1',
                'varcat' => 'layout_and_position',
                'propertyname' => 'checkbox',
                'type' => '14',
              )

);

if (false) { //Load and translate once
    xarML('Default theme');
    xarML('Display a page timer in the page.');
}

?>