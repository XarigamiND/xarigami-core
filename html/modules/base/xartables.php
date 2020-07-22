<?php
/**
 * Base Table Definitions
 *
 * @package modules
 * @copyright (C) 2005-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Passes table definitons back to Xarigami core
 * @author Paul Rosania
 * @return string
 */
function base_xartables()
{
    // Initialise table array
    $tables = array();

    $systemPrefix = xarDB::$sysprefix;

    // Get the name for the template Tags table table
    $templateTagsTable = $systemPrefix . '_template_tags';

    // Q: does this need to be here?
    $tables['template_tags']= $templateTagsTable;
    // Return the table information
    return $tables;
}

?>