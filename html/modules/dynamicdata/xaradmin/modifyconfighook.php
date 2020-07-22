<?php
/**
 * Modify configuration for a module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * modify configuration for a module - hook for ('module','modifyconfig','GUI')
 *
 * @param int $args['objectid'] ID of the object
 * @param array $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_modifyconfighook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyconfighook', 'dynamicdata');
         throw new BadParameterException(null,$msg);
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarMod::getName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarMod::getId($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyconfighook', 'dynamicdata');
         throw new EmptyParameterException(null,$msg);
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!xarMod::apiLoad('dynamicdata', 'user')) return;

    $fields = xarMod::apiFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        $fields = array();
    }

    $labels = array(
                    'id' => xarML('ID'),
                    'name' => xarML('Name'),
                    'label' => xarML('Label'),
                    'type' => xarML('Field Format'),
                    'default' => xarML('Default'),
                    'source' => xarML('Data Source'),
                    'validation' => xarML('Configuration'),
                   );

    foreach($fields as $field=>$ftype) {
        if (isset($ftype['validation']) && !empty($ftype['validation']) && substr($ftype['validation'],0,2) == 'a:') {
            $configset = unserialize($ftype['validation']);
            foreach($configset as $s=>$value) {
                $fields[$field]['configset'][$s] = $value;
            }
        } else {
            $fields[$field]['configset'] = array();
        }

    }
    $labels['dynamicdata'] = xarML('Dynamic Data Fields');
    $labels['config'] = xarML('modify');

    $data = array();
    $data['labels'] = $labels;
    $data['link'] = xarModURL('dynamicdata','admin','modifyprop',
                              array('modid' => $modid,
                                    'itemtype' => $itemtype));
    $data['fields'] = $fields;
    $data['fieldtypeprop'] = Dynamic_Property_Master::getProperty(array('type' => 'fieldtype'));

    $object = Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                                       'itemtype' => $itemtype));
    if (!isset($object)) return;

    if (!empty($object->template)) {
        $template = $object->template;
    } else {
        $template = $object->name;
    }
    return xarTplModule('dynamicdata','admin','modifyconfighook',$data,$template);
}

?>