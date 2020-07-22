<?php
/**
 * Sample function returning an array of options
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * sample function returning an array of options for a Dropdown or Radio Buttons property
 *
 * @author mikespub <mikespub@xaraya.com>
 * @author the DynamicData module development team
 * @return array of values, or array of id => value combinations
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_utilapi_demolist($args)
{
    extract($args);
    // do something with arguments

    // fill in the array with the values to be shown
    $options = array(
                     '',                          // use an empty value as default
                     xarML('Employed Full Time'),
                     xarML('Employed Part Time'),
                     xarML('Self-Employed'),
                     xarML('Unemployed'),
                     xarML('Student'),
                     xarML('Retired'),
                     xarML('Not Applicable'),
                    );

    // or fill in the array with the id => value combinations
/*
    $options = array(
                     'unknown'    => '',                          // use an empty value as default
                     'emp_full'   => xarML('Employed Full Time'),
                     'emp_part'   => xarML('Employed Part Time'),
                     'self_emp'   => xarML('Self-Employed'),
                     'unempl'     => xarML('Unemployed'),
                     'student'    => xarML('Student'),
                     'retired'    => xarML('Retired'),
                     'not_applic' => xarML('Not Applicable'),
                    );
*/

    return $options;
}

?>