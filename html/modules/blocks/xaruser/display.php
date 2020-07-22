<?php
/**
 * Display a single block in the module space
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
*/
/*
 * @subpackage blocks
 * @return array 
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @param  string $name name of the block to render
 */
function blocks_user_display($args)
{
    extract($args);
    // Get all the available blocks
    $benum = 'enum';  $data = array();
    $blocks =xarMod::apiFunc('blocks', 'user', 'getall');
    
    foreach($blocks as $bid => $binfo)
    {
        //jojodee - sec check
        if(xarSecurityCheck('ReadBlock',0,'Block',"{$binfo['module']}:{$binfo['type']}:{$binfo['name']}")) {
            $benum .= ':'.$binfo['name'];
        }
    }
    if(!xarVarFetch('name',$benum,$name)) return;
    // Template issues a wrapped xar:block tag.
    $data['name'] = $name;
    return $data;
}
?>
