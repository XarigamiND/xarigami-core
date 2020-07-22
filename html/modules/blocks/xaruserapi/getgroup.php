<?php
/**
 * Retrieve a group raw data.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/*
 * Retrieve a group raw data.
 * @author Jim McDonald, Paul Rosania
 * @todo Is this function called anywhere?
*/

function blocks_userapi_getgroup($args)
{
    extract($args);

    if (!xarVarValidate('int:1', $gid, true)) {$gid = 0;}
    if (!xarVarValidate('str:1', $name, true)) {$name = '';}

    if (empty($gid) && empty($name)) {
        // No identifier provided.
        $msg = xarML('Invalid parameter: missing gid and name');
        throw new BadParameterException(null,$msg);
    }

    // The getall function does the main work.
    if (!empty($gid)) {
        $group = xarMod::apiFunc('blocks', 'user', 'getallgroups', array('gid' => $gid));
    } else {
        $group = xarMod::apiFunc('blocks', 'user', 'getallgroups', array('name' => $name));
    }

    // If exactly one row was found then return it.
    if (count($group) == 1) {
        return array_pop($group);
    } else {
        return;
    }
}

?>
