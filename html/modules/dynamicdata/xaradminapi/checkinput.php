<?php
/**
 * Dynamic data browse function
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
 * check input from dynamic data (needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input)
 *
 * @param array  &$fields fields array (pass by reference here !)
 * @param string $dd_function optional name of the calling function
 * @param array  $extrainfo optional extra information (from hooks)
 * @return array of invalid fields
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_checkinput($args)
{
// don't use extract here - we want to pass the updated fields back
//    extract($args);

    // replaced by validation in Dynamic_Property

// TODO: test this replacement :)
    $invalid = array();
    foreach ($args['fields'] as $field) {
        $property = Dynamic_Property_Master::getProperty($field);
        if (!$property->checkInput()) {
            $invalid[$property->name] = $property->invalid;
        }
    }
    return $invalid;
}
?>