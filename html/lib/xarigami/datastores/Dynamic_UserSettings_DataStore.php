<?php
/**
 * Data Store is the user settings (user variables per module) // TODO: integrate user variable handling with DD
 *
 * @package dynamicdata
 * @subpackage datastores
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 */

/**
 * User settings datastore
 *
 * @package dynamicdata
 */
class Dynamic_UserSettings_DataStore extends Dynamic_DataStore
{
    public $modname;

    function __construct($name)
    {
        // invoke the default constructor from our parent class
        Dynamic_DataStore::__construct($name);

        // keep track of the concerned module for user settings
    // TODO: the concerned module is currently hiding in the third part of the name :)
        list($fixed1,$fixed2,$modid) = explode('_',$name);
        if (empty($modid)) {
            $modid = xarMod::getId(xarMod::getName());
        }
        $modinfo = xarMod::getInfo($modid);
        if (!empty($modinfo['name'])) {
            $this->modname = $modinfo['name'];
        }
    }

    /**
     * Get the field name used to identify this property (we use the name of the property here)
     */
    function getFieldName($property)
    {
        return $property->name;
    }

    function getItem($args)
    {
        if (empty($args['itemid'])) {
            // default is the current user (if any)
            $itemid = xarUserGetVar('uid');
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

    // TODO: introduce xarModGetUserVars ?

        foreach ($fieldlist as $field) {
            // get the value from the user variables
            $value = xarModUserVars::get($this->modname,$field,$itemid);

            // set the value for this property
            if (isset($value)) {
                $this->fields[$field]->setValue($value);
            //} else {
                // use the equivalent module variable as default
            //    $this->fields[$field]->setValue(xarModGetVar($this->modname,$field));
            }
        }
        return $itemid;
    }

    function createItem($args)
    {
        // There's no difference with updateItem() here, because xarModSetUserVar() handles that
        return $this->updateItem($args);
    }

    function updateItem($args)
    {
        if (empty($args['itemid'])) {
            // default is the current user (if any)
            $itemid = xarUserGetVar('uid');
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            xarModSetUserVar($this->modname,$field,$value,$itemid);
        }
        return $itemid;
    }

    function deleteItem($args)
    {
        if (empty($args['itemid'])) {
            // default is the current user (if any)
            $itemid = xarUserGetVar('uid');
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            xarModDelUserVar($this->modname,$field,$itemid);
        }

        return $itemid;
    }

    function getItems(Array $args = array())
    {
        // TODO: not supported by xarMod*UserVar
    }

    function countItems(Array $args = array())
    {
        // TODO: not supported by xarMod*UserVar
        return 0;
    }

}

?>
