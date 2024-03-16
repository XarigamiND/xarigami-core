<?php
/**
 * Data Store is a dummy (for in-memory data storage, perhaps)
 *
 * @package dynamicdata
 * @subpackage datastores
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 */
/**
 * Dummy data store class
 *
 * @package dynamicdata
 */
if (!class_exists('Dynamic_DataStore_Master') || !class_exists('Dynamic_DataStore')) sys::import('modules.dynamicdata.class.datastores');
class Dynamic_Dummy_DataStore extends Dynamic_DataStore
{

    function getItem($args)
    {

       if (empty($args['itemid']))
            throw new BadParameterException(xarML('Cannot get itemid of 0'));
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            $this->fields[$field]->setValue($itemid);
        }
    }

    function getItems(Array $args = array())
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
        }
        foreach ($itemids as $itemid) {
            foreach (array_keys($this->fields) as $field) {
                $this->fields[$field]->setItemValue($itemid, $itemid);
            }
        }
    }

    function createItem($args)
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'createvalue')) {
                $this->fields[$field]->createValue($itemid);
            }
        }
    }

    function updateItem($args)
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'updatevalue')) {
                $this->fields[$field]->updateValue($itemid);
            }
        }
    }

    function deleteItem($args)
    {
        $itemid = $args['itemid'];
        foreach (array_keys($this->fields) as $field) {
            if (method_exists($this->fields[$field],'deletevalue')) {
                $this->fields[$field]->deleteValue($itemid);
            }
        }
    }


}

?>
