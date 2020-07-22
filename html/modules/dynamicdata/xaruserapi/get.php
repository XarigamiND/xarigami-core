<?php
/**
 * Dynamic Data Version Information
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Dynamic Data Version Information
 *
 * @author mikespub <mikespub@xaraya.com>
*/
function dynamicdata_userapi_get($args)
{
    return xarMod::apiFunc('dynamicdata','user','getfield',$args);
}
?>