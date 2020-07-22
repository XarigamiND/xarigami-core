<?php
/**
 * Draw state icon
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * @author Jim McDonald, Paul Rosania
*/
function blocks_userapi_drawStateIcon($args)
{
    if (xarUserIsLoggedIn() && !empty($args['bid'])) {
        if(xarMod::apiFunc('blocks', 'user', 'getState', $args) == true) {
            $output = '<a href="'.xarModURL('blocks', 'user', 'changestatus', array('bid' => $args['bid'])).'"><img src="modules/blocks/xarimages/'.xarModGetVar('blocks', 'blocksuparrow').'" border="0" alt="" /></a>';
        } else {
            $output = '<a href="'.xarModURL('blocks', 'user', 'changestatus', array('bid' => $args['bid'])).'"><img src="modules/blocks/xarimages/'.xarModGetVar('blocks', 'blocksdownarrow').'" border="0" alt="" /></a>';
        }
        return $output;
    }
}

?>