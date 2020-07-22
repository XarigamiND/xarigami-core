<?php
/**
 * Data Store is the theme variables
 *
 * @package dynamicdata
 * @subpackage datastores
 * @copyright (C) 2008-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 */

/**
 * Class to handle theme variables datastores
 *
 * @package dynamicdata
 */
class Dynamic_ThemeVariables_DataStore extends Dynamic_DataStore
{
    public $themename;

    function __construct($name)
    {
        // invoke the default constructor from our parent class
        Dynamic_DataStore::__construct($name);

        // keep track of the concerned theme for theme settings
    // TODO: the concerned theme is currently hiding in the third part of the data store name :)
        list($fixed1,$fixed2,$themeid) = explode('_',$name);
        if (empty($themeid)) {
            $themeid = xarThemeGetIDFromName(xarTplGetThemeName());
        }
        $themeinfo = xarThemeGetInfo($themeid);
        if (!empty($themeinfo['name'])) {
            $this->themename = $themeinfo['name'];
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
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
               if (count($fieldlist) < 1) {
            return;
        }

        // let's cheat a little bit here, and preload everything :-)
        xarTheme_getVarsByTheme($this->themename);

        foreach ($fieldlist as $field) {
            // get the value from the module variables
        // TODO: use $field.$itemid for modules with several itemtypes ? [like articles :)]
            $value = xarThemeGetVar($this->themename,$field);
            // set the value for this property
            $this->fields[$field]->setValue($value);
        }
        return $itemid;
    }

    function createItem($args)
    {
        // There's no difference with updateItem() here, because xarModSetVar() handles that
        return $this->updateItem($args);
    }

    function updateItem($args)
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
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
            $description = isset($description)?$description:'';
             $prime = isset($prime)?$prime:0;
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            xarThemeSetVar($this->themename,$field,$value,$prime,$description);
        }
        return $itemid;
    }

    function deleteItem($args)
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            xarThemeDelVar($this->themename,$field);
        }

        return $itemid;
    }

    function getItems(Array $args = array())
    {
        // TODO: not supported by xarMod*Var
    }

    function countItems(Array $args = array())
    {
        // TODO: not supported by xarMod*Var
        return 0;
    }

}

?>
