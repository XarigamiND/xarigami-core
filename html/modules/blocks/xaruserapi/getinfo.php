<?php
/**
 * Get details suitable for *rendering* a block instance.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2008-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/*
 * Get details suitable for *rendering* a block instance.
 * This will return the details for a block.
 *
 * TODO: big changes planned for in here.
 * - block can be an instance or a 'one-off' standalone block
 * - standalone blocks will be seeded with the block init details (if available)
 * - arbitrary parameters can be passed in to override the block content array elements
 * - some sort of validation check could be made available for the overridable params?
 * - system-level flag to switch between reporting attribute/args errors or just ignoring
 *
 * Note: this function is used solely by the BL renderer, and is subject
 * to change without notice.
 */

function blocks_userapi_getinfo($args)
{
    extract($args);
    // Exit now for templates that have not been recompiled - at least one of these elements
    // will be missing.
    if (!array_key_exists('instance', $args) || !array_key_exists('module', $args) || !array_key_exists('type', $args)) {
        return;
    }

    // security check  - do further down after we have the info
    // needed when blocks are directly accessed

    // We will be selecting either a block instance or a stand-alone block.
    if (!empty($instance)) {
        // Block instance - fetch it from the database.
        if (is_numeric($instance)) {
            if (xarCoreCache::isCached('Block.Infos2', $instance)) {
                $blockinfo = xarCoreCache::getCached('Block.Infos2', $instance);
            } else {
                $blockinfo = xarModAPIfunc('blocks', 'user', 'get', array('bid' => $instance));
            }

            if (empty($blockinfo)) {
                // No matching block was found.
                return;
            }

            if (!xarCoreCache::isCached('Block.Infos2', $blockinfo['bid'])) {
                // Cache the block details if available
                xarCoreCache::setCached('Block.Infos2', $blockinfo['bid'], $blockinfo);
            }

            // TODO: return here if the block name fails a security check.
        } else {
            $blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('name' => $instance));
        }

    } else {
        // Standalone block - load it from file and seed with default details.
        $blockinfo = array(
            'module' => $module,
            'type' => $type,
            'title' => '',
            'template' => '',
            'bid' => 0,
            'state' => 3,
            'name' => (!is_null($name) ? $name : '')
        );

        // Get block default content.
        $default_content = xarMod::apiFunc(
            'blocks', 'user', 'read_type_init',
            array('module' => $module, 'type' => $type)
        );

        if (!empty($default_content)) {
            // Default details for the block are available - use them.
            // Note that this will be an array, not serialized. Blocks
            // that provide initialization data must also be able to
            // accept a non-serialized content array.
            $blockinfo['content'] = $default_content;
        } else {
            $blockinfo['content'] = '';
        }
    }

    if(!xarSecurityCheck('ReadBlock',0,'Block',"{$blockinfo['module']}:{$blockinfo['type']}:{$blockinfo['name']}")) return;
    // No block details.
    // Perhaps the instance or module/type is invalid.
    if (empty($blockinfo)) {return;}

    // Do standard overrides.
    if (!is_null($title)) {$blockinfo['title'] = $title;}
    if (!is_null($state)) {$blockinfo['state'] = $state;}

    // Now do the custom overrides.
    // We have a hack here to unserialize the content string, update the
    // fields then reserialize (if it started serialized). The problem is,
    // until ALL blocks can accept arrays as content, then passing arrays
    // from here will cause blocks to fail. This hack will work until all
    // blocks are converted.

    // Not sure how this will work - there is no reliable way of recognising
    // a string of serialised data. We will make a guess that is starts
    // 'a:[number]:{' and ends '}'.
    $serialize_flag = FALSE;
    if (is_string($blockinfo['content']) && preg_match('/^[at]:[0-9]+:\{.*\}$/', $blockinfo['content'])) {
        // We think this is serialized data - try expanding it.
        $content2 = @unserialize($blockinfo['content']);
        if (!is_null($content2)) {
            // Looks like it was successful.
            $blockinfo['content'] =& $content2;
            $serialize_flag = TRUE;
        }
    }

    // The content at this point will be, completely empty, a string or
    // an array - only try and set array elements for an array.
    if (is_array($blockinfo['content'])) {
        foreach($content as $pname => $pvalue) {
            // If the array element exists, then override it.
            // There is no validation here (yet) - so arrays can
            // override strings and strings can override arrays.
            // TODO: allow a block to provide validation rules to
            // pass $pvalue through for each $pname.
            // Such validation would also be able to convert numbers
            // into booleans, string lists into arrays etc.
            // Only override non-array and unset elements for now.

            if (!isset($blockinfo['content'][$pname])) {
                $blockinfo['content'][$pname] = $pvalue;
            } elseif (!is_array($blockinfo['content'][$pname])) {
                // Now let's be clever. Validate the override so it is the same
                // data type as the element it is over-riding. We still can't do
                // range-checking, or more complicated transformationsm, but we
                // get better validation and type casting.

                switch (gettype($blockinfo['content'][$pname])) {
                    case 'integer' : $valid = xarVarValidate('int', $pvalue, TRUE); break;
                    case 'string' : $valid = xarVarValidate('str', $pvalue, TRUE); break;
                    // Note: bool type validates 'TRUE'/'FALSE', or set/non-set
                    // but NOT non-zero and zero.
                    case 'boolean' : $valid = xarVarValidate('bool', $pvalue, TRUE); break;
                    case 'float' : $valid = xarVarValidate('float', $pvalue, TRUE); break;
                    default : $valid = FALSE;
                }

                // If the override validated, then set the parameter.
                if ($valid) {$blockinfo['content'][$pname] = $pvalue;}
            }
        }

        if ($serialize_flag) {
            // We need to serialize the content again.
            // TODO: when all blocks support serialized content data,
            // remove this re-serialization. It is just for legacy support.
            $blockinfo['content'] = @serialize($blockinfo['content']);
        }
    }

    if (is_null($template)) {$template = $blockinfo['template'];}
    // Split template name into outer and inner using the ';' separator.
    if (strpos($template, ';') === FALSE) {
        $blockinfo['_bl_box_template'] = $template;
        $blockinfo['_bl_block_template'] = '';
    } else {
        $template = preg_split('/;/', $template, 3);
        $blockinfo['_bl_box_template'] = $template[0];
        $blockinfo['_bl_block_template'] = $template[1];
    }

    // Allow a global override to the box template for the xar:blockgroup tag.
    if (!empty($box_template) && empty($blockinfo['_bl_box_template'])) {$blockinfo['_bl_box_template'] = $box_template;}

    // Legacy support.
    if (!isset($instance ) ||is_array($instance)) $instance['id'] = $blockinfo['bid'];
    $blockinfo['bkey'] = $blockinfo['bid'];
    $blockinfo['mid'] = $blockinfo['module'];

    return $blockinfo;
}

?>