<?php
/**
 * Export an object definition or an object item to XML
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
 * Export an object definition or an object item to XML
 *
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_utilapi_export($args)
{
    // restricted to DD Admins
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if (isset($args['objectref'])) {
        $myobject =& $args['objectref'];

    } else {
        extract($args);

        if (empty($objectid)) {
            $objectid = null;
        }
        if (empty($modid)) {
            $modid = xarMod::getId('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
        if (empty($itemid)) {
            $itemid = null;
        }

        $myobject = Dynamic_Object_Master::getObject(array('objectid' => $objectid,
                                             'moduleid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid'   => $itemid,
                                             'allprops' => true));
    }

    if (!isset($myobject) || empty($myobject->label)) {
        return;
    }

    // get the list of properties for a Dynamic Object
    $object_properties = Dynamic_Property_Master::getProperties(array('objectid' => 1));

    // get the list of properties for a Dynamic Property
    $property_properties = Dynamic_Property_Master::getProperties(array('objectid' => 2));

    $proptypes = xarMod::apiFunc('dynamicdata','user','getproptypes');

    $prefix = xarDB::$sysprefix;
    $prefix .= '_';

    $xml = '';

    $xml .= '<object name="'.$myobject->name.'">'."\n";
    foreach (array_keys($object_properties) as $name) {
        if ($name != 'name' && isset($myobject->$name)) {
            if (is_array($myobject->$name)) {
                $xml .= "  <$name>\n";
                foreach ($myobject->$name as $field => $value) {
                    $xml .= "    <$field>" . xarVarPrepForDisplay($value) . "</$field>\n";
                }
                $xml .= "  </$name>\n";
            } else {
                $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->$name) . "</$name>\n";
            }
        }
    }
    $xml .= "  <properties>\n";
    if (!empty($myobject->objectid)) {
        // get the property info directly from the database again to avoid default eval()
        $properties = Dynamic_Property_Master::getProperties(array('objectid' => $myobject->objectid));
    } else {
        $properties = array();
        foreach (array_keys($myobject->properties) as $name) {
            $properties[$name] = array();
            foreach (array_keys($property_properties) as $key) {
                if (isset($myobject->properties[$name]->$key)) {
                    $properties[$name][$key] = $myobject->properties[$name]->$key;
                }
            }
        }
    }
    foreach (array_keys($properties) as $name) {
        $xml .= '    <property name="'.$name.'">' . "\n";
        foreach (array_keys($property_properties) as $key) {
            if ($key != 'name' && isset($properties[$name][$key])) {
                if ($key == 'type') {
                    // replace numeric property type with text version
                    $xml .= "      <$key>".xarVarPrepForDisplay($proptypes[$properties[$name][$key]]['name'])."</$key>\n";
                } elseif ($key == 'source') {
                    // replace local table prefix with default xar_* one
                    $val = $properties[$name][$key];
                    $val = preg_replace("/^$prefix/",'xar_',$val);
                    $xml .= "      <$key>".xarVarPrepForDisplay($val)."</$key>\n";
                } else {
                    $xml .= "      <$key>".xarVarPrepForDisplay($properties[$name][$key])."</$key>\n";
                }
            }
        }
        $xml .= "    </property>\n";
    }
    $xml .= "  </properties>\n";
    $xml .= "</object>\n";

    return $xml;
}

?>