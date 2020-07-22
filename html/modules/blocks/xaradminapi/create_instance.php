<?php
/**
 * create a new block instance
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * create a new block instance
 * @param $args['name'] unique name for the block
 * @param $args['title'] the title of the block
 * @param $args['type'] the block's type
 * @param $args['template'] the block's template
 * @return int block instance id on success, false on failure
 */
function blocks_adminapi_create_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((!isset($name) || !xarVarValidate('pre:lower:ftoken:passthru:str:1', $name)) ||
        (!isset($type) || !is_numeric($type)) ||
        (!isset($state) || !is_numeric($state))) {
        // TODO: this type of error to be handled automatically
        // (i.e. no need to pass the position through the error message, as the
        // error handler should already know).
        $msg = xarML('Invalid Parameter Count', 'admin', 'create', 'Blocks');
         throw new BadParameterException(null, $msg);

        return;
    }
    // Make sure type exists.
    $blocktype = xarMod::apiFunc('blocks', 'user', 'getblocktype', array('tid' => $type));

    // Security
    if (!xarSecurityCheck('AddBlock', 0, 'Block', "{$blocktype['module']}:{$blocktype['type']}:{$name}")) {return xarResponseForbidden();}

     $initresult = xarMod::apiFunc('blocks', 'user', 'read_type_init', $blocktype);

    // If the content is not set, attempt to get initial content from
    // the block initialization function.
    if (!isset($content)) {
        $content = '';

        if (!empty($initresult)) {
            $content = $initresult;
        }
    }

    if (!empty($content) && !is_string($content)) {
        // Serialize the content, so arrays of initial content
        // can be passed directly into this API.
        $content = serialize($content);
    }

    if (!isset($template)) $template = '';
    if (!isset($title)) $title = '';

    // Load up database details.
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $block_instances_table = $xartable['block_instances'];

    // Insert instance details.
    $nextId = $dbconn->GenId($block_instances_table);
    $query = 'INSERT INTO ' . $block_instances_table . ' (
              xar_id,
              xar_type_id,
              xar_name,
              xar_title,
              xar_content,
              xar_template,
              xar_state,
              xar_refresh,
              xar_last_update
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $bindvars = array($nextId, $type, $name, $title, $content, $template, $state, 0, 0);
    $result = $dbconn->Execute($query, $bindvars);
    if (!$result) {return;}

    // Get ID of row inserted.
    $bid = $dbconn->PO_Insert_ID($block_instances_table, 'xar_id');

    // Update the group instances.
    if (isset($groups) && is_array($groups)) {
        // Pass the group updated to the API if required.
        // TODO: error handling.
        $result = xarMod::apiFunc(
            'blocks', 'admin', 'update_instance_groups',
            array('bid' => $bid, 'groups' => $groups)
        );
    }

    // Insert defaults for block caching (based on block init array)
    if (!empty($initresult) && is_array($initresult) && !empty($xartable['cache_blocks'])) {
        if (!empty($initresult['nocache'])) {
            $nocache = 1;
        } else {
            $nocache = 0;
        }
        if (!empty($initresult['pageshared']) && is_numeric($initresult['pageshared'])) {
            $pageshared = (int) $initresult['pageshared'];
        } else {
            $pageshared = 0;
        }
        if (!empty($initresult['usershared']) && is_numeric($initresult['usershared'])) {
            $usershared = (int) $initresult['usershared'];
        } else {
            $usershared = 0;
        }
        // don't use empty because this could be 0 here
        if (isset($initresult['cacheexpire']) && is_numeric($initresult['cacheexpire'])) {
            $cacheexpire = (int) $initresult['cacheexpire'];
        } else {
            $cacheexpire = NULL;
        }
        //check and see if there is an entry already before trying to add one - bug # 5815
        $checkbid = xarMod::apiFunc('blocks','user','getcacheblock',array('bid'=>$bid));
        //we assume for now that it's left here due to bug # 5815 so delete it
        if (is_array($checkbid)) {
           $deletecacheblock = xarMod::apiFunc('blocks','admin','delete_cacheinstance', array('bid' => $bid));
        }
        //now create the new block
        $cacheblocks = $xartable['cache_blocks'];
        $query = "INSERT INTO $cacheblocks (xar_bid,
                                            xar_nocache,
                                            xar_page,
                                            xar_user,
                                            xar_expire)
                  VALUES (?,?,?,?,?)";
        $bindvars = array($bid, $nocache, $pageshared, $usershared, $cacheexpire);
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) {return;}
    }

    // Resequence the blocks.
    xarMod::apiFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarMod::callHooks('item', 'create', $bid, $args);
    xarLogMessage('BLOCKS: A new block with id '.$bid.' was created by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return $bid;
}
?>