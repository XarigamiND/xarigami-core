<?php
/**
 * Show validation of some property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Show validation of some property
 * @return array
 */
function dynamicdata_admin_showpropval($args)
{
    extract($args);

    // get the property id
    if (!xarVarFetch('itemid',  'id',    $itemid)) {return;}
    if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirm', 'isset', $confirm, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('exit', 'isset', $exit, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('objectid', 'isset', $objectid, NULL, XARVAR_DONT_SET)) {return;}
    // check security
    $modid = xarMod::getId('dynamicdata');
    $itemtype = 1; // dynamic properties
    if (!xarSecurityCheck('EditDynamicDataItem',0,'Item',"$modid:$itemtype:$itemid")) return xarResponseForbidden();

    // get the object corresponding to this dynamic property
    $myobject = Dynamic_Object_Master::getObject(array('objectid' => 2,
                                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    //ensure we have a property - get the id of the property
    $newid = $myobject->getItem();
    if (empty($newid) || empty($myobject->properties['id']->value)) {
        $msg = xarML('Invalid item id');
         throw new EmptyParameterException(null,$msg);
    }
    // check if the module+itemtype this property belongs to is hooked to the uploads module
    $modid = $myobject->properties['moduleid']->value;
    $itemtype = $myobject->properties['itemtype']->value;
    $modinfo = xarMod::getInfo($modid);
    if (xarMod::isHooked('uploads', $modinfo['name'], $itemtype)) {
        xarCoreCache::setCached('Hooks.uploads','ishooked',1);
    }
    $data = array();
    // get a new property of the right type
    $data['type'] = $myobject->properties['type']->value;
    $id = $myobject->properties['validation']->id;
    $data['name']       = 'dd_'.$id;
    $name = $myobject->properties['validation']->name;
    // pass the actual id for the property here
    $data['id']         = $id;

    // pass the original invalid value here
    $data['invalid']    = !empty($invalid) ? $invalid :'';
    $property = Dynamic_Property_Master::getProperty($data);
    if (empty($property)) return;
    $data['propertytype'] = Dynamic_Property_Master::getProperty(array('type' => $data['type']));
   if (!empty($preview) || !empty($confirm) || !empty($exit)) {
        if (!xarVarFetch($data['name'],'isset',$validation,NULL,XARVAR_NOT_REQUIRED)) return;
        // pass the current value as validation rule
        $data['validation'] = isset($validation) ? $validation: '';
        $isvalid = $property->updateValidation($data);
        if ($isvalid) {
            if (!empty($confirm) || !empty($exit)) {
                  // store the updated validation rule back in the value
                $myobject->properties['validation']->value = $property->validation;
                if (!xarSecConfirmAuthKey()) return;

                $newid = $myobject->updateItem();
                if (empty($newid)) return;
                $msg = xarML('Property configuration for "#(1)" has been updated successfully.',$myobject->properties['name']->value);
                xarTplSetMessage($msg,'status');

            }
            if (!empty($exit)) {
                if (!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
                if (empty($return_url)) {
                    // return to modifyprop
                    $return_url = xarModURL('dynamicdata', 'admin', 'modifyprop',
                                            array('itemid' => $myobject->properties['objectid']->value));
                }
                if (xarRequestIsAJAX()) {
                    $confirm = array(
                        'short' => xarML('The update was successful.'),
                        'title' => xarML('Validation for "#(1)" Updated', $myobject->properties['name']->value)
                    );
                    return xarTplModule('base','message','confirm', $confirm);
                } else {
                    xarResponseRedirect($return_url);
                }
                return true;
            }

        } else {
            $myobject->properties['validation']->invalid = $property->invalid;
            $invalidarray=unserialize($property->validation);
            //add invalid messages to array
            foreach($invalidarray as $validopt=>$opt) {
                if (!isset($data['validation'][$validopt])) $data['validation'][$validopt] = $opt;
            }
        }

     } elseif (!empty($myobject->properties['validation'])) {
        $data['validation'] = @unserialize($myobject->properties['validation']->value);
    } else {
        $data['validation'] = null;
    }
    // pass the id for the input field here
    $data['id']         = 'dd_'.$id;
    $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
    $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
    $data['size']       = !empty($size) ? $size : 50;

    // call its showValidation() method and return
    $data['showval'] = $property->showValidation($data);
    $data['itemid'] = $itemid;
    $data['objectid'] = $objectid;
    $data['object'] = $myobject;
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');

      xarTplSetPageTitle(xarML('Validation for DataProperty #(1)', $itemid));
    // Return the template variables defined in this function

    return $data;
}

?>