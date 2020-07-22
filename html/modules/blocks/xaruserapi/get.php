<?php
/**
 * Retrieve a block instance raw data.
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */

function blocks_userapi_get($args)
{
    extract($args);
    if (!xarVarValidate('int:1', $bid, true)) {$bid = 0;}
    if (!xarVarValidate('str:1', $name, true)) {$name = '';}

    if (empty($bid) && empty($name)) {
        // No identifier provided.
        $msg = xarML('Invalid parameter: missing bid or name');
         throw new BadParameterException(null,$msg);
    }

    // The getall function does the main work.
    if (!empty($bid)) {
        $instances = xarMod::apiFunc('blocks', 'user', 'getall', array('bid' => $bid));
    } elseif (isset($module)) {
        //Let us get name and module to ensure uniqueness
        $instances = xarMod::apiFunc('blocks', 'user', 'getall', array('name' => $name, 'module'=>$module));
    } else {
        //remain compatible with prior name only call
        $instances = xarMod::apiFunc('blocks', 'user', 'getall', array('name' => $name));
    }

    // If exactly one row was found then return it.
    if (count($instances) == 1) {
        $instance = array_pop($instances);
        return $instance;
    } else {
        return;
    }
}

?>
