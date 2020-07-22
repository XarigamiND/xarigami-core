<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * This is a standard function to modify the configuration parameters of the
 * module
 * @return array
 */
function dynamicdata_admin_manageproplist()
{

    // the blocklayout template, and get the common menu configuration - it
    // helps if all of the module pages have a standard menu at the top to
    // support easy navigation
    //$data = xarMod::apiFunc('dynamicdata','admin','menu'); //deprecated
    if (!xarVarFetch('invalid',  'array', $invalid,'', XARVAR_DONT_SET)) {return;}

    // Security check
    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();
    // Initialise the $data variable that will hold the data to be used in
    $data = array();
    $data['invalid'] = $invalid;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    if (!xarMod::apiLoad('dynamicdata', 'user')) return;

    // Get the defined property types from somewhere...
    $data['fields'] = xarMod::apiFunc('dynamicdata','user','getproptypes');
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    sys::import('modules.dynamicdata.xarproperties.Dynamic_FieldType_Property');

    $data['fieldtypeprop'] = new Dynamic_FieldType_Property(array('type' => 'fieldtype'));

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'name' => xarML('Name'),
                            'label' => xarML('Description'),
                            'informat' => xarML('Input Format'),
                            'outformat' => xarML('Display Format'),
                            'validation' => xarML('Example config'),
                        // etc.
                            'new' => xarML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = xarVarPrepForDisplay(xarML('Update Property Types'));
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    // Return the template variables defined in this function
    return $data;
}

?>