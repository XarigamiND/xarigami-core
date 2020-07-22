<?php
/**
 * Get possible data sources
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get possible data sources (// TODO: for a module ?)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or (// TODO: for a module ?)
 * @param $args['modid'] module id of the item field to get (// TODO: for a module ?)
 * @param $args['itemtype'] item type of the item field to get (// TODO: for a module ?)
 * @return mixed list of possible data sources, or false on failure
 * @throws BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getsources($args)
{
    return Dynamic_DataStore_Master::getDataSources();
}

?>