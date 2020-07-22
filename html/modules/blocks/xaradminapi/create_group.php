<?php
/**
 * create a new group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * create a new group
 * @param $args['name'] the group name
 * @param $args['template'] the default block template
 * @return int group id on success, false on failure
 */
function blocks_adminapi_create_group($args)
{
    // Get arguments from argument array
    extract($args);

    if (!isset($template)) {
        $template = '';
    }

    // Argument check
    if ((!isset($name))) {
         throw new EmptyParameterException(array('name','adminapi','create_group','blocks'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    // Security
   if(!xarSecurityCheck('AddBlockGroup', 0, 'Blockgroup','All')) {return xarResponseForbidden();}

    // Load up database
    $dbconn =  xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $block_groups_table = $xartable['block_groups'];

    // Insert group into table
    $nextId = $dbconn->GenId($block_groups_table);
    $query = 'INSERT INTO ' . $block_groups_table
        . ' (xar_id, xar_name, xar_template) VALUES (?, ?, ?)';

    $result = $dbconn->Execute($query , array($nextId, $name, $template));
    if (!$result) {return;}

    // Get group ID as index of groups table
    $group_id = $dbconn->PO_Insert_ID($block_groups_table, 'xar_id');
    xarLogMessage('BLOCKS: A new block group with id '.$group_id.' was created by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return $group_id;
}

?>