<?php
/**
 * Get a dynamic property
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
 * get a dynamic property
 *
 * @author the DynamicData module development team
 * @param $args['type'] type of property (required)
 * @param $args['name'] name for the property (optional)
 * @param $args['label'] label for the property (optional)
 * @param $args['default'] default for the property (optional)
 * @param $args['source'] data source for the property (optional)
 * @param $args['validation'] validation for the property (optional)
 * @return object a particular Dynamic Property
 */
function dynamicdata_userapi_getproperty($args)
{
    if (empty($args['type'])) {
        $result = null;
        return $result;
    }
    $result =  Dynamic_Property_Master::getProperty($args);
    return $result;
}

?>