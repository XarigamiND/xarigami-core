<?php
/**
 * View block types
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * view block types
 */
function blocks_admin_view_types()
{
    // Security Check
    if(!xarSecurityCheck('EditBlock',0)) return xarResponseForbidden();

    // Parameter to indicate a block type for which to get further details.
    if (!xarVarFetch('tid', 'id', $tid, 0, XARVAR_NOT_REQUIRED)) {return;}

    $params = array();
    $info = array();
    $detail = array();
    if (!empty($tid)) {
        // Get details for a specific block type.
        $detail = xarMod::apiFunc(
            'blocks', 'user', 'getblocktype', array('tid' => $tid)
        );
        if (!empty($detail)) {
            // The block type exists.

            // Get info data.
            $info = xarMod::apiFunc(
                'blocks', 'user', 'read_type_info',
                array(
                    'module' => $detail['module'],
                    'type' => $detail['type']
                )
            );

            // Get initialisation data.
            $init = xarMod::apiFunc(
                'blocks', 'user', 'read_type_init',
                array(
                    'module' => $detail['module'],
                    'type' => $detail['type']
                )
            );

            if (is_array($init)) {
                // Parse the initialisation data to extract further details.
                foreach($init as $key => $value) {
                    $valuetype = gettype($value);
                    $params[$key]['name'] = $key;

                    if ($valuetype == 'string') {
                        $value = "'" . $value . "'";
                    }

                    if ($valuetype == 'boolean') {
                        if ($value) {
                            $params[$key]['value'] = 'true';
                        } else {
                            $params[$key]['value'] = 'false';
                        }
                    } else {
                        $params[$key]['value'] = $value;
                    }

                    $params[$key]['type'] = $valuetype;
                    if ($valuetype == 'boolean' || $valuetype == 'integer' || $valuetype == 'float' || $valuetype == 'string' || $valuetype == 'NULL') {
                        $params[$key]['overrideable'] = true;
                    } else {
                        $params[$key]['overrideable'] = false;
                    }
                }
            }
        }
    }

    $block_types = xarMod::apiFunc(
        'blocks', 'user', 'getallblocktypes', array('order' => 'module,type')
    );

    // Add in some extra details.
    foreach($block_types as $index => $block_type) {
        $block_types[$index]['modurl'] = xarModURL($block_type['module'], 'admin');
        if(xarSecurityCheck('EditBlock',0,'Block',"{$block_type['module']}:{$block_type['type']}:All")) 
        {        
            $block_types[$index]['refreshurl'] = xarModURL('blocks', 'admin', 'update_type_info',
                array('modulename'=>$block_type['module'], 'blocktype'=>$block_type['type']));
        } else {
            $block_types[$index]['refreshurl'] = '';
        }
        $block_types[$index]['detailurl'] = xarModURL(
            'blocks', 'admin', 'view_types',
            array('tid'=>$block_type['tid'])
        );
        $block_types[$index]['info'] = $block_type['info'];
        
        //This is a serious business - could delete all associated blocks as well ...
        if(xarSecurityCheck('AdminBlock',0,'Block',"{$block_type['module']}:{$block_type['type']}:All")) 
        {
            $block_types[$index]['deleteurl'] = xarModURL('blocks', 'admin', 'delete_type',
            array('modulename'=>$block_type['module'], 'blocktype'=>$block_type['type']));
        } else {
            $block_types[$index]['deleteurl'] = '';
        }
        
    }
    //common admin menu
    $menulinks = xarMod::apiFunc('blocks','admin','getmenulinks');  
 
    return array(
        'block_types' => $block_types,
        'tid' => $tid,
        'params' => $params,
        'info' => $info,
        'detail' => $detail,
        'menulinks' => $menulinks
    );
}

?>