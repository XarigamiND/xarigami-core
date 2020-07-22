<?php
/**
 * Count number of items held by this module
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
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @param array $args the usual suspects :)
 * @return integer number of items held by this module
 */
function dynamicdata_userapi_countitems($args)
{
    $mylist = Dynamic_Object_Master::getObjectList($args);
    if (!isset($mylist)) return;

    return $mylist->countItems();
}

?>