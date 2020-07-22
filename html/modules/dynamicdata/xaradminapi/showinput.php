<?php
/**
 * Show predefined form input field
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * show some predefined form input field in a template
 *
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showinput($args)
{
    $property = Dynamic_Property_Master::getProperty($args);
    $args['property'] = $property;
    if (!empty($args['preset']) && empty($args['value'])) {
        return $property->_showPreset($args);

    } elseif (!empty($args['hidden'])) {
    //allow override in itemplate
            if ($args['hidden'] == 'active') {
                $property->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE);
            } elseif ($args['hidden'] == 'display') {
                $property->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_DISPLAYONLY);
            } elseif ($args['hidden'] == 'hidden') {
                $property->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN);
                return $property->showHidden($args);
            }
    }
    //we may have overridden and set status in GUI as well
    if (($property->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN) ||
        ($property->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDENDISPLAY) ||
        ($property->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)) {
            return $property->showHidden($args);
    }

    return $property->showInput($args);

    // TODO: input for some common hook/utility modules
}
?>