<?php
/**
 * Block management - create a new block instance
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
 * create a new block instance
 */
function blocks_admin_create_instance()
{
    $invalid = array();
    // Get parameters
    if (!xarVarFetch('block_type', 'str:1:', $type)) {return;}
    if (!xarVarFetch('block_name', 'pre:lower:ftoken:passthru:str:1:', $name, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_state', 'int:0:2', $state)) {return;}
    if (!xarVarFetch('block_title', 'str:1:', $title, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_template', 'str:1:', $template, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_groups', 'array', $groups, array(), XARVAR_NOT_REQUIRED)) {return;}

    $newargs = array(
            'name'      => $name,
            'title'     => $title,
            'type'      => $type,
            'template'  => $template,
            'state'     => $state,
            'groups'    => $groups
        );
    //check required fields
    if (!isset($name) || empty($name)) {
         $invalid['block_name'] = xarML('You must enter a unique block name');
    }

    if (count($invalid) > 0) {
        xarResponseRedirect(xarModURL('blocks','admin', 'new_instance', array('invalid'=>$invalid)));
        return;
    }
    // Confirm Auth Key
    if (!xarSecConfirmAuthKey('blocks')) {return;}

    $blockinfo = xarMod::apiFunc('blocks','user','getblocktype',array('tid'=>$type));
    if (!is_array($blockinfo))
    {
        $msg = xarML('Block of that module and type cannot be found');
        throw new BadParameterException(null,$msg);
    }
    $blockmodule = $blockinfo['module'];
    $blocktype = $blockinfo['type'];
    // Security Check
    if(!xarSecurityCheck('AddBlock',0,'Block',"{$blockmodule}:{$blocktype}:{$name}")) return xarResponseForbidden();

    // Check if block name has already been used.
    $checkname = xarMod::apiFunc('blocks', 'user', 'get', array('name' => $name));

    if (!empty($checkname)) {
        $msg = xarML('The block name "#(1)" already exists, no duplicates are allowed.',$name);
        xarTplSetMessage($msg,'error');
        //jojo - TODO - return via xarTplModule with all info;
         xarResponseRedirect(xarModURL('blocks', 'admin', 'new_instance'));
        //throw new DuplicateException(array('block',$name));
    }

    // Pass to API
    $bid = xarMod::apiFunc(
        'blocks', 'admin', 'create_instance',
        array(
            'name'      => $name,
            'title'     => $title,
            'type'      => $type,
            'template'  => $template,
            'state'     => $state,
            'groups'    => $groups
        )
    );

    if (!$bid) {return;}

    // Go on and edit the new instance
    xarResponseRedirect(
        xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $bid))
    );

    return true;
}

?>