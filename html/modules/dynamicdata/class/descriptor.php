<?php
/**
 * @package modules
 * @copyright (C) 2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('xarigami.structures.descriptor');
/*
 * generate the variables necessary to instantiate a DataObject or DataProperty class
*/
class Dynamic_Data_ObjectDescriptor extends xarObjectDescriptor
{
    function __construct(Array $args=array())
    {
        $args = self::getObjectID($args);
        parent::__construct($args);
    }

    static function getModID(Array $args=array())
    {
        foreach ($args as $key => &$value) {
            if (in_array($key, array('module','modid','module','moduleid'))) {
                if (empty($value)) $value = xarMod::getRegID(xarMod::getName());
                if (is_numeric($value) || is_integer($value)) {
                    $args['moduleid'] = $value;
                } else {
                    //$info = xarMod::getInfo(xarMod::getRegID($value));
                    $args['moduleid'] = xarMod::getRegID($value); //$info['systemid']; FIXME
                }
                break;
            }
        }
        // Still not found?
        if (!isset($args['moduleid'])) {
            if (isset($args['fallbackmodule']) && ($args['fallbackmodule'] == 'current')) {
                $args['fallbackmodule'] = xarMod::getName();
            } else {
                $args['fallbackmodule'] = 'dynamicdata';
            }
            $args['moduleid'] = xarMod::getRegID($args['fallbackmodule']);
        }
        if (!isset($args['itemtype'])) $args['itemtype'] = 0;
        return $args;
    }

    /**
     * Get Object ID
     *
     * @return array all parts necessary to describe a DataObject
     */
    static function getObjectID(Array $args=array())
    {
        xarMod::loadDbInfo('dynamicdata','dynamicdata');
        $xartable = &xarDB::$tables;
        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT xar_object_id,
                         xar_object_name,
                         xar_object_moduleid,
                         xar_object_itemtype
                  FROM $dynamicobjects ";

        $bindvars = array();
        if (isset($args['name'])) {
            $query .= " WHERE name = ? ";
            $bindvars[] = $args['name'];
        } elseif (!empty($args['objectid'])) {
            $query .= " WHERE id = ? ";
            $bindvars[] = (int) $args['objectid'];
        } else {
            $args = self::getModID($args);
            $query .= " WHERE moduleid = ?
                          AND itemtype = ? ";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }

        $dbconn = xarDB::$dbconn;
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result->first()) {
            $row = array();
        } else {
            $row = $result->getRow();
        }
        $result->close();

        if (empty($row) || count($row) < 1) {
            $args['moduleid'] = isset($args['moduleid']) ? (int)$args['moduleid'] : null;
            $args['itemtype'] = isset($args['itemtype']) ? (int)$args['itemtype'] : null;
            $args['objectid'] = isset($args['objectid']) ? (int)$args['objectid'] : null;
            $args['name'] = isset($args['name']) ? $args['name'] : null;
        } else {
            $args['moduleid'] = (int)$row['moduleid'];
            $args['itemtype'] = (int)$row['itemtype'];
            $args['objectid'] = (int)$row['id'];
            $args['name'] = $row['name'];
        }
        if (empty($args['tplmodule'])) $args['tplmodule'] = xarMod::getName($args['moduleid']);
        if (empty($args['template'])) $args['template'] = $args['name'];
        return $args;
    }
}

?>