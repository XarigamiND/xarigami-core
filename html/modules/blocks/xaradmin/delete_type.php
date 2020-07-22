<?php
/**
 * Block group management - delete a block type
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Delete a block type and all associated blocks of that type if they exist
 *
 * @param  blocktype  str    Name of blocktype (optional - must have either tid of blocktype name)
 * @param  tid        int    Block type id (optional - must have either tid of blocktype name)
 * @param  modulename str    Module name as block owner
 * @return data       array  mixed data for the template, true on successful deletion elsel false
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_admin_delete_type()
{
     // Get parameters
    if (!xarVarFetch('modulename', 'str:1:', $modulename, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('blocktype', 'str:1:', $blocktype, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('tid', 'str:1:', $blocktype, 0, XARVAR_NOT_REQUIRED)) {return;} //add tid for upstream compat
    if (!xarVarFetch('confirm', 'checkbox', $confirm, false, XARVAR_NOT_REQUIRED)) {return;}


    if (empty($tid) && (empty($modulename) || empty($blocktype))) {
        $msg = xarML('Missing #(1), or #(2) and #(3), for function #(4) in module #(5)', 'tid', 'modulename','blocktype', 'blocks_admin_delete_type','Blocks' );
        throw new BadParameterException(null,$msg);
    }

    // Get info on the current block type prior to sec check now in case we have $tid
    if (empty($modulename) || empty($blocktype)){
       $typeinfo = xarMod::apiFunc('blocks', 'user', 'getblocktype',array('tid' => $tid));
    } else {
       $typeinfo = xarMod::apiFunc('blocks', 'user', 'getblocktype',array('module' => $modulename,'type'=>$blocktype));
    }
   
    if (!is_array($typeinfo) || empty($typeinfo)) {
        $msg = xarML('Unkown Block Type for #(1)  and function #(2) ', 'Blocks', 'blocks_admin_delete_type');
         throw new BadParameterException(null,$msg);
    }
    $modulename = $typeinfo['module'];
    $blocktype = $typeinfo['type'];
    $tid = $typeinfo['tid'];

    // Security Check - deleting a block type deletes all blocks associated with a block type - suggest admin level required
    if(!xarSecurityCheck('AdminBlock', 1, 'Block',"{$modulename}:{blocktype}:All")) {return;}

    //Get some info on any instances of this block type
    //jojo - use the existing getall filtered on type (now added as an option)
    $blockinstances = xarMod::apiFunc('blocks','user','getall',array('tid' => $tid));
    if (is_array($blockinstances) && !empty($blockinstances)) {
        //process instances a little
        foreach ($blockinstances as $key=>$binstance) {
            $blockinstances[$key]['gcount'] = count($binstance['groups']);
            $blockinstances[$key]['gcountname'] = count($binstance['groups'])>1 ? xarML('groups') : xarML('group');
        }
    }

    $returnurl = xarModURL('blocks','admin','view_types');

    //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');

    // Check for confirmation
    if (FALSE == $confirm) {
        // No confirmation yet - get one
        if ($blocktype == NULL) {return;}
        $data = array('blocktype'       => $blocktype,
                      'modulename'      => $modulename,
                      'authid'          => xarSecGenAuthKey('blocks'),
                      'deletelabel'     => xarML('Delete'),
                      'returnurl'       => $returnurl,
                      'blockinstances'  => $blockinstances,
                      'menulinks'       => $menulinks
        );

        return $data;
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey('blocks')) {return;}

    // Pass to API
    xarMod::apiFunc('blocks', 'admin', 'delete_type', array('module' => $modulename,'type'=> $blocktype));

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_types'));

    return true;
}
?>