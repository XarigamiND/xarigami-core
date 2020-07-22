<?php
/**
 * Mail Module table definition function
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Mail table definition functions
 * Return mail table names to xaraya
 *
 * This function is called internally by the core whenever the module is
 * loaded. It is loaded by xarMod::loadDbInfo().
 * @access private
 * @return array
 */
function mail_xartables()
{
    $xarTables = array();
    $prefix = xarDB::$prefix;

    // mail queue is our only table
    $xarTables['mail_queue'] = $prefix.'_mail_queue';

    return $xarTables;
}
?>