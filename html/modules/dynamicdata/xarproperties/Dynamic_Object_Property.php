<?php
/**
 * Dynamic Object Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Include the base class
 *
 */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * handle the object property
 *
 * @package dynamicdata
 */
/*
* Options available to user selection
* ===================================
* Options take the form:
*   option-type:option-value;
* option-types:
*   static:true - add modules to the list
*/

class Dynamic_Object_Property extends Dynamic_Select_Property
{
    public $id         = 24;
    public $name       = 'object';
    public $desc       = 'Object reference';
    public $reqmodules  = 'dynamicdata';
    public $xv_store_name   = FALSE;   //Store name instead of id

    /* jojo : we could extend object ref property , but let's keep object prop independent as it is a special case,
     *        and provide simple option to store name instead of id, eg name or itemid.
     *        This keeps it simple in this special case and prevents issues such as recursion that we might run into otherwise.
     */
    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath     = 'modules/dynamicdata/xarproperties';
        if (isset($args['store_name'])) $this->xv_store_name = $args['store_name']; //passed in

        if (!empty($this->validation)) {
            foreach(preg_split('/(?<!\\\);/', $this->validation) as $option) {
                // Semi-colons can be escaped with a '\' prefix.
                $option = str_replace('\;', ';', $option);
                // An option comes in two parts: option-type:option-value
                if (strchr($option, ':')) {
                    list($option_type, $option_value) = explode(':', $option, 2);
                    if ($option_type == 'static' && $option_value == 1) {
                        $includestatics = true;
                        $modlist = xarMod::apiFunc('modules',
                                         'admin',
                                         'GetList');
                        foreach ($modlist as $modinfo) {
                            $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
                        }
                    }
                }
            }
        }

    }
  // Return a list of array(id => value) for the possible options
    function getOptions()
    {
            sys::import('modules.dynamicdata.class.objects');
            $objInfo  = Dynamic_Object_Master::getObjectInfo(array('name' => 'objects'));
            $objects=  xarMod::apiFunc('dynamicdata', 'user', 'getitems', array (
                                    'objectid'    => $objInfo['objectid']));

            if (!isset($objects)) {
                $objects = array();
            }

            foreach ($objects as $objectid => $object) {
                if (!empty($includestatics)) {
                    $ancestors = xarMod::apiFunc('dynamicdata','user','getancestors',array('objectid' => $objectid, 'top' => false));
                    $name ="";
                    foreach ($ancestors as $parent) $name .= $parent['name'] . ".";
                    $options[] = array('id' => '182.' . ($this->xv_store_name == TRUE ?$object['name'] :$objectid), 'name' => $name . $object['name']);
                } else {
                    $options[] = array('id' => $this->xv_store_name == TRUE ?$object['name'] :$objectid,
                                              'name' => $object['name']);
                }
            }
        $this->options = $options;
        return $options;
    }

    function getBaseValidationInfo()
    {
        static $validationarray = array();
       if (empty($validationarray)) {
            $parentvalidations = parent::getBaseValidationInfo();


            $validations = array('xv_store_name' =>  array(  'label'=>xarML('Use name for id?'),
                                                                'description'=>xarML('Store the object name instead of the id?'),
                                                                'propertyname'=>'checkbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          )
                                    );
            $validationarray = array_merge($parentvalidations,$validations);
       }

        return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
            $args = array();
         $validation = $this->getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 24,
                            'name'       => 'object',
                            'label'      => 'Object dropdown',
                            'format'     => '24',
                            'validation' => serialize($validation),
                            'source'     => '',
                            'dependancies' => '',
                            'filepath'    => 'modules/dynamicdata/xarproperties',
                            'requiresmodule' => 'dynamicdata',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>