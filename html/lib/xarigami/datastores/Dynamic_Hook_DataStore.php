<?php
/**
 * Data Store is managed by a hook/utility module
 *
 * @package dynamicdata
 * @subpackage datastores
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 */


/**
 * Class to handle hook datastore
 *
 * @package dynamicdata
 *
 */
class Dynamic_Hook_DataStore extends Dynamic_DataStore
{
    /**
     * Get the field name used to identify this property (we use the hook name here)
     */
    function getFieldName($property)
    {
        // check if this is a known module, based on the name of the property type
        $proptypes = Dynamic_Property_Master::getPropertyTypes();
        $curtype = $property->type;
        if (!empty($proptypes[$curtype]['name'])) {
            return $proptypes[$curtype]['name'];
        }
    }

    function setPrimary($property)
    {
        // not applicable !?
    }

    function getItem($args)
    {
        $modid = $args['modid'];
        $itemtype = $args['itemtype'];
        $itemid = $args['itemid'];
        $modname = $args['modname'];

        foreach (array_keys($this->fields) as $hook) {
            if (xarMod::isAvailable($hook)) {
            // TODO: find some more consistent way to do this !
                $value = xarMod::apiFunc($hook,'user','get',
                                       array('modname' => $modname,
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$hook]->setValue($value);
                }
            }
        }
        return $itemid;
    }

}

?>
