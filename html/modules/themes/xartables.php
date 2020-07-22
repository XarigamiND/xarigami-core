<?php
/**
 * Themes administration and initialization
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @ xarigami Themes subsystem
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Themes administration
 * @return array The information with all tables held by the Themes module
 */

function themes_xartables()
{
    // Initialise table array
    $xartable = array();

    $sitePrefix   = xarDB::$prefix;

    // Set the table name
    $xartable['themes']                 = $sitePrefix . '_themes';
    $xartable['theme_vars']             = $sitePrefix . '_theme_vars';

    // Return the table information
    return $xartable;
}

?>