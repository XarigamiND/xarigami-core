<?php
/**
 * Update attributes of a block instance
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * update attributes of a block instance
 *
 * @param $args['bid'] the ID of the block to update
 * @param $args['title'] the new title of the block
 * @param $args['group_id'] the new position of the block (deprecated)
 * @param $args['groups'] optional array of group memberships
 * @param $args['template'] the template of the block instance
 * @param $args['content'] the new content of the block
 * @param $args['refresh'] the new refresh rate of the block
 * @return bool true on success, false on failure
 */
function blocks_adminapi_update_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional arguments
    if (!isset($content)) {
        $content = '';
    }

    // The content no longer needs to be serialized before it gets here.
    // Lets keep the serialization close to where it is stored (since
    // storage is the only reason we do it).
    if (!is_string($content)) {
        $content = serialize($content);
    }

    if (!isset($template)) {
        $template = '';
    }

    // Argument check
    if (!xarVarValidate('pre:lower:ftoken:passthru:str:1', $name) ||
        (!isset($bid) || !is_numeric($bid)) ||
        (!isset($title)) ||
        (!isset($refresh) || !is_numeric($refresh)) ||
        (!isset($state)  || !is_numeric($state))) {
        $msg = xarML('Invalid parameter');
        throw new BadParameterException(null,$msg);
    }

    // Legacy support of group_id
    if (!isset($groups) && isset($group_id)) {
        $groups = array(array('gid' => $group_id, 'template' => ''));
    }

    //check for unique name before updating the database (errors raised
    // by unique keys are not user-friendly).
    $name = strtolower($name);
    $blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));
    if ($name != $blockinfo['name']) {
        $checkname = xarMod::apiFunc('blocks', 'user', 'get', array('name' => $name ));
        if (!empty($checkname)) {
            $msg = xarML('Block name "#(1)" already exists', $name);
            throw new BadParameterException(null,$msg);
        }
    }
    // Security
    if(!xarSecurityCheck('EditBlock', 1, 'Block', "{$blockinfo['module']}:{$blockinfo['type']}:{$blockinfo['name']}")) {return;}

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = 'UPDATE ' . $block_instances_table . '
              SET xar_content = ?,
                  xar_template = ?,
                  xar_name = ?,
                  xar_title = ?,
                  xar_refresh = ?,
                  xar_state = ?
              WHERE xar_id = ?';

    $bind = array(
        $content, $template, $name, $title,
        $refresh, $state, $bid
    );

    $result = $dbconn->Execute($query, $bind);
    if (!$result) {return;}

    // Update the group instances.
    if (isset($groups) && is_array($groups)) {
        // Pass the group updated to the API if required.
        // TODO: error handling.
        $result = xarMod::apiFunc(
            'blocks', 'admin', 'update_instance_groups',
            array('bid' => $bid, 'groups' => $groups)
        );
    }

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarMod::callHooks('item', 'update', $bid, $args);
  xarLogMessage('BLOCKS: Block id '.$bid.' was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    if (isset($directreturn) && ($directreturn == 1)) { //flag to notify that this was a special update and return direct to the modify func
        xarResponseRedirect(xarModURL('blocks','admin','modify_instance',array('bid'=>$bid,'auth'=> xarSecGenAuthKey())));
    }

    return true;
}

?>
