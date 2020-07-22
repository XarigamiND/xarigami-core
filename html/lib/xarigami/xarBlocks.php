<?php
/**
 * Display Blocks
 *
 * xarBlockType functions are now in xarLegacy,
 * they can be called through blocks module api
 *
 * @package Xarigami core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami blocks
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

//wrappers for cumulus functions
function xarBlock_renderGroup($groupname, $template = NULL) { return xarBlock::renderGroup($groupname, $template); }
function xarBlock_renderBlock($blockinfo)                   { return xarBlock::renderBlock($blockinfo); }
function xarBlock_render($blockinfo)                        { return xarBlock::render($blockinfo); }

interface IxarBlock
{
    public static function render(Array $blockinfo=array());
    public static function renderBlock(Array $blockinfo=array());
    public static function renderGroup($groupname, $template=null);
}

class xarBlock extends xarBase implements IxarBlock
{
    const BLOCK_STATE_INACTIVE  = 0;  // Inactive blocks don't execute and don't rende
    const BLOCK_STATE_HIDDEN    = 1;   // Hidden blocks still execute but do not render
    const BLOCK_STATE_VISIBLE   = 2;  // Visible and active

    public static function init(array $args = NULL)
    {
        // Blocks Support Tables
        $systemPrefix = xarDB::$sysprefix;

        $tables = array(
            'block_instances'       => $systemPrefix . '_block_instances',
            'block_groups'          => $systemPrefix . '_block_groups',
            'block_group_instances' => $systemPrefix . '_block_group_instances',
            'block_types'           => $systemPrefix . '_block_types'
        );

        xarDB::importTables($tables);

        return TRUE;
    }

    /**
     * Renders a block
     *
     * @author Paul Rosania, Marco Canini <marco@xaraya.com>
     * @access protected
     * @param  array blockinfo block information parameters
     * @return string output the block to show
     * @throws  BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
     * @todo   this function calls a module function, keep an eye on it
     */
    public static function render(Array $blockinfo=array())
    {

        // Skip executing inactive blocks
        if (isset($blockinfo['state']) && $blockinfo['state'] == self::BLOCK_STATE_INACTIVE) {
            // @TODO: global flag to raise exceptions
            // if ((bool)xarModVars::get('blocks', 'noexceptions')) return '';
            return '';
        }

        // Get a cache key for this block if it's suitable for block caching
        //Output caching for blocks
        $cacheKey = xarCache::getBlockKey($blockinfo);
         // Check if the block is cached
        if (!empty($cacheKey) && xarBlockCache::isCached($cacheKey)) {
            // Return the cached block output
            return xarBlockCache::getCached($cacheKey);
        }

        $modName = $blockinfo['module'];
        $blockType = $blockinfo['type'];
        $blockName = $blockinfo['name'];
        if (!xarModIsAvailable($modName)) return FALSE; //do not allow blocks to render if the module is not active

        xarLogMessage('xarBlock_render: begin '.$modName.':'.$blockType.':'.$blockName);

        //now do sec check
        if(!xarSecurityCheck('ViewBlock',0,'Block',"{$blockinfo['module']}:{$blockinfo['type']}:{$blockinfo['name']}")) return;

        if (isset($blockinfo['group_name']) && !empty($blockinfo['group_name'])) {
            if (!xarSecurityCheck('ReadBlockGroup', 0, 'Blockgroup', "{$blockinfo['group_name']}")) return;
        }
        // This lets the security system know what module we're in
        // no need to update / select in database for each block here
        // xarModSetVar('blocks','currentmodule',$modName);
        xarCoreCache::setCached('Security.Variables', 'currentmodule', $modName);

        // Load the block
        try {
            xarMod::apiFunc('blocks', 'admin', 'load', array('modName' => $modName, 'blockType' => $blockType, 'blockFunc' => 'display'));
        } catch (Exception $e) {
           // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
        }

        // Get the block display function name.
        $displayFuncName = "{$modName}_{$blockType}block_display";

        // check if block has expired
        $now = time();
        if (isset($block->expire) && $now > $block->expire && $block->expire != 0) {
           // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return '';
        }

        // Fetch complete blockinfo array.
        if (function_exists($displayFuncName)) {
            // Allow the block to modify the content before rendering.
            // In fact, the block can access and alter any aspect of the block info.
            try {
                $blockinfo = $displayFuncName($blockinfo);

            } catch (Exception $e) {
                // Set the output of the block in cache
                if (!empty($cacheKey)) {
                    xarBlockCache::setCached($cacheKey, '');
                }
                throw ($e);
            }

            //the block has nothing to display
            if (!isset($blockinfo) || !is_array($blockinfo)) {
                // Set the output of the block in cache
                if (!empty($cacheKey)) {
                    xarBlockCache::setCached($cacheKey, '');
                }
                return '';
            }

            // Render the block if it has content and isn't hidden
            if (is_array($blockinfo['content']) && $blockinfo['state'] != self::BLOCK_STATE_HIDDEN) {
                // Here $blockinfo['content'] is template data.
                //Set some additional details that the could be useful in the block.
                $blockinfo['content']['blockid'] = $blockinfo['bid'];
                $blockinfo['content']['blockname'] = $blockinfo['name'];
                $blockinfo['content']['blocktypename'] = $blockinfo['type'];
                if (isset($blockinfo['bgid'])) {
                    // The block may not be rendered as part of a group.
                    $blockinfo['content']['blockgid'] = $blockinfo['bgid'];
                    $blockinfo['content']['blockgroupname'] = $blockinfo['group_name'];
                }

                // Render this block template data.
                try {
                    $blockinfo['content'] = xarTpl::block(
                        $modName, $blockType, $blockinfo['content'],
                        $blockinfo['_bl_block_template'],
                        !empty($blockinfo['_bl_template_base']) ? $blockinfo['_bl_template_base'] : NULL
                    );
                } catch (Exception $e) {
                        // Set the output of the block in cache
                    if (!empty($cacheKey)) {
                        xarBlockCache::setCached($cacheKey, '');
                    }
                    throw ($e);
                }
            } else {
                // hidden block, or no content to display
                if (!empty($cacheKey)) {
                    xarBlockCache::setCached($cacheKey, '');
                }
                return "";
            }
        }

        //$blockinfo itself is passed to the outer template
        // Attempt to render this block template data.
        try {
            $boxOutput = xarTpl::renderBlockBox($blockinfo, $blockinfo['_bl_box_template']);
        } catch (Exception $e) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
             throw ($e);

        }
        xarLogMessage('xarTpl::renderBlockBox: end '.$modName.':'.$blockType.':'.$blockName);
        // Set the output of the block in cache
        if (!empty($cacheKey)) {
            xarBlockCache::setCached($cacheKey, $boxOutput);
        }
        return $boxOutput;
    }

    /**
     * Renders a block group
     *
     * @access protected
     * @param string groupname the name of the block group
     * @param string template optional template to apply to all blocks in the group
     * @return string
     * @throws EmptyParameterException
     */
    public static function renderGroup($groupname, $template = NULL)
    {
        if (empty($groupname)) {
            throw new EmptyParameterException('groupname');
        }
        $blockCaching = xarCoreCache::getCached('xarcache', 'blockCaching');
        
        $tables = &xarDB::$tables;
        $blockGroupInstancesTable = $tables['block_group_instances'];
        $blockInstancesTable      = $tables['block_instances'];
        $blockGroupsTable         = $tables['block_groups'];
        $blockTypesTable          = $tables['block_types'];

        // Fetch details of all blocks in the group.
        $query = "SELECT    inst.xar_id as bid,
                            btypes.xar_type as type,
                            btypes.xar_module as module,
                            inst.xar_name as name,
                            inst.xar_title as title,
                            inst.xar_content as content,
                            inst.xar_last_update as last_update,
                            inst.xar_state as state,
                            group_inst.xar_position as position,
                            bgroups.xar_id              AS bgid,
                            bgroups.xar_name            AS group_name,
                            bgroups.xar_template        AS group_bl_template,
                            inst.xar_template           AS inst_bl_template,
                            group_inst.xar_template     AS group_inst_bl_template
                  FROM      $blockGroupInstancesTable group_inst
                  LEFT JOIN $blockGroupsTable bgroups
                  ON        group_inst.xar_group_id = bgroups.xar_id
                  LEFT JOIN $blockInstancesTable inst
                  ON        inst.xar_id = group_inst.xar_instance_id
                  LEFT JOIN $blockTypesTable btypes
                  ON        btypes.xar_id = inst.xar_type_id
                  WHERE     bgroups.xar_name = ?
                  AND       inst.xar_state > 0
                  ORDER BY  group_inst.xar_position ASC";

        $result = xarDB::$dbconn->Execute($query, array($groupname));
        if (!$result) {return;}

        $output = '';
        while(!$result->EOF) {
            $blockinfo = $result->GetRowAssoc(false);

            $blockinfo['last_update'] = $result->UnixTimeStamp($blockinfo['last_update']);

            // Get the overriding template name.
            // Levels, in order (most significant first): group instance, instance, group
            $group_inst_bl_template = preg_split('/;/', $blockinfo['group_inst_bl_template'], 3);
            $inst_bl_template = preg_split('/;/', $blockinfo['inst_bl_template'], 3);
            $group_bl_template = preg_split('/;/', $blockinfo['group_bl_template'], 3);

            if (empty($group_bl_template[0])) {
                // Default the box template to the group name.
                $group_bl_template[0] = $blockinfo['group_name'];
            }

            if (empty($group_bl_template[1])) {
                // Default the block template to the instance name.
                // TODO
                $group_bl_template[1] = $blockinfo['name'];
            }

            // Cascade level over-rides for the box template.
            $blockinfo['_bl_box_template'] = !empty($group_inst_bl_template[0]) ? $group_inst_bl_template[0]
                : (!empty($inst_bl_template[0]) ? $inst_bl_template[0] : $group_bl_template[0]);

            // Global override of box template - usually comes from the 'template'
            // attribute of the xar:blockgroup tag.
            if (!empty($template)) {
                $blockinfo['_bl_box_template'] = $template;
            }

            // Cascade level over-rides for the block template.
            $blockinfo['_bl_block_template'] = !empty($group_inst_bl_template[1]) ? $group_inst_bl_template[1]
                : (!empty($inst_bl_template[1]) ? $inst_bl_template[1] : $group_bl_template[1]);

            $blockinfo['_bl_template_base'] = $blockinfo['type'];

            // Unset a few elements that clutter up the block details.
            // They are for internal use and we don't want them used within blocks.
            unset($blockinfo['group_inst_bl_template']);
            unset($blockinfo['inst_bl_template']);
            unset($blockinfo['group_bl_template']);

            $blockoutput = self::render($blockinfo);

            $output .= $blockoutput;

            // Next block in the group.
            $result->MoveNext();
        }

        $result->Close();

        return $output;
    }

    /**
     * Renders a single block
     *
     * @author John Cox
     * @access protected
     * @param  string args[instance] id or name of block instance to render
     * @param  string args[module] module that owns the block
     * @param  string args[type] module that owns the block
     * @return string
     * @todo   this function calls a module function, keep an eye on it.
     */
    public static function renderBlock(Array $args=array())
    {
        // All the hard work is done in this function.
        // It keeps the core code lighter when standalone blocks are not used.
        $blockinfo = xarMod::apiFunc('blocks', 'user', 'getinfo', $args);

        if (!empty($blockinfo)) {
            $blockoutput = self::render($blockinfo);
            $output = $blockoutput;
        } else {
            // TODO: return NULL to indicate no block found?
            $output = '';
        }

        return $output;
    }
}
?>
