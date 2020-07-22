<?php
/**
 * Get all items
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Get all items
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_userapi_getall($args)
{
    return xarMod::apiFunc('dynamicdata','user','getitem',$args);
}

?>