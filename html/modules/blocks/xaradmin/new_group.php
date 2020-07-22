<?php
/**
 * Display form for new block group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * display form for a new block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_new_group()
{
    // Security Check
    if(!xarSecurityCheck('AddBlockGroup', 0, 'Blockgroup','All')) {return xarResponseForbidden();}
     //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');     
    
    return array(
        'createlabel' => xarML('Create Group'),
        'cancellabel' => xarML('Cancel'),
        'authid'      => xarSecGenAuthKey('blocks'),
        'menulinks'   => $menulinks
    );
}

?>