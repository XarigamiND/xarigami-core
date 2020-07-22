<?php
/**
 * Dynamic Data Object Reference Property (foreign key like dropdown)
 * You can specify the to be referenced object and what property values
 * to use for displayinig and to store in the (foreign key) field
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo match the type of the local field to the store property type (must be the same)
 * @todo extra option to limit displaying
 * @todo rules for when the referenced object prop value gets deleted etc.
 * @todo foreign keys which consist of multiple attributes (bad design, but in practice it might come in handy)
 * @todo make the different loops a bit more efficient.
*/

// We base it on the select property
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Handle the objectreference property
 *
 * @package dynamicdata
 */
class Dynamic_ObjectRef_Property extends Dynamic_Select_Property
{
    public $id         = 507;
    public $name       = 'objectref';
    public $desc       = 'Object reference';
    public $reqmodules  = 'dynamicdata';

    // We explicitly use names here instead of id's, so we are independent of
    // how dd assigns them at a given time. Otherwise the validation is not
    // exportable to other sites.
    public $xv_refobject    = 'objects';    // Name of the object we want to reference
    public $xv_store_prop   = 'name';   // Name of the property we want to use for storage
    public $xv_display_prop = 'name';       // Name of the property we want to use for displaying.

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule    = 'base';
        $this->filepath     = 'modules/dynamicdata/xarproperties';
    }

    public function showInput(Array $data = array())
    {
        extract($data);

        if (isset($data['refobject']))    $this->xv_refobject = $data['refobject'];
        if (isset($data['store_prop']))   $this->xv_store_prop = $data['store_prop'];
        if (isset($data['display_prop'])) $this->xv_display_prop = $data['display_prop'];
        if (isset($data['firstline']))    $this->xv_firstline = $data['firstline'];
       return parent::showInput($data);
    }
   // Produce option(id,value) and value to pass to template
    // We cant trust the parent right now because that is using xarTplModule and not xarTplProperty
    public function showOutput(Array $data= array())
    {
        extract($data);

        if (isset($data['refobject']))    $this->xv_refobject = $data['refobject'];
        if (isset($data['store_prop']))   $this->xv_store_prop = $data['store_prop'];
        if (isset($data['display_prop'])) $this->xv_display_prop = $data['display_prop'];
        if (isset($data['firstline']))    $this->xv_firstline = $data['firstline'];

        if (isset($value)) $this->value = $value;

        $data['value'] = $this->value;

        // get the option corresponding to this value
       // $result = $this->getOption();
        // only apply xarVarPrepForDisplay on strings, not arrays et al.
       // if (!empty($result) && is_string($result)) $result = xarVarPrepForDisplay($result);
       // $data['option'] = array('id' => $this->value, 'name' => $result);
        // If children call us, they can pass in template
        return parent::showOutput($data);
    }
    // Return a list of array(id => value) for the possible options
    function getOptions()
    {
        $options = $this->getFirstline();
        // The object we need to query is in $this->xv_refobject, we display the value of
        // the property in $this->xv_display_prop and the id comes from $this->xv_store_prop
        $objInfo  = Dynamic_Object_Master::getObjectInfo(array('name' => $this->xv_refobject));


        $items =  xarMod::apiFunc('dynamicdata', 'user', 'getitems', array (
                                    'modid'    => $objInfo['moduleid'],
                                    'itemtype' => $objInfo['itemtype'],
                                    'sort'     => $this->xv_display_prop,
                                    'fieldlist'=> $this->xv_display_prop . ',' . $this->xv_store_prop)
                             );

        foreach($items as $item) {
            $options[] = array('id' => $item[$this->xv_store_prop], 'name' => $item[$this->xv_display_prop]);
        }

        return $options;
   }
   // Show the validation output.
    //old validations supposed to be objectname:display_propname:store_propname
    function showValidation(Array $data = array())
    {

        if (!isset($data['validation'])) $data['validation'] = $this->validation;
        $this->parseValidation($data['validation']);

        $object =  Dynamic_Object_Master::getObjectList(array('name' => $this->xv_refobject));

        $props = $object->getProperties();
        $poptions = array();
        foreach ($props as $pname=>$pinfo) {
            $poptions[] = array('id'=>$pname, 'name'=>$pname);
        }
        $validationprops = $this->getValidationProperties();

        if (!empty($validationprops['xv_store_prop'])) {
            $validationprops['xv_store_prop']['propargs']['options'] = $poptions;
        }
        if (!empty($validationprops['xv_display_prop'])) {
            $validationprops['xv_display_prop']['propargs']['options'] = $poptions;
        }
        $data['validationprops'] =  $validationprops;

        return parent::showValidation($data);
    }


  /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    function getBaseValidationInfo()
    {
        static $validationarray = array();
       if (empty($validationarray)) {
            $parentvalidations = parent::getBaseValidationInfo();


            $validations = array('xv_refobject' =>  array(  'label'=>xarML('Object reference'),
                                                                'description'=>xarML('Object that supplies the property for reference'),
                                                                'propertyname'=>'object',
                                                                'propargs' => array('store_name'=> TRUE),
                                                                'ignore_empty'  =>1,
                                                                 'configinfo'    => xarML('[After selecting the object, click update before selecting storage and display value below.]'),
                                                                'ctype'=>'definition'
                                                          ),
                                'xv_store_prop' =>  array(  'label'=>xarML('Storage property'),
                                                                'description'=>xarML('Property  we want to use for value storage'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                'xv_display_prop' =>  array(  'label'=>xarML('Display property'),
                                                                'description'=>xarML('Property we want to use for label display'),
                                                                'propertyname'=>'dropdown',
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
                              'id'             => 507,
                              'name'           => 'objectref',
                              'label'          => 'Object reference',
                              'format'         => '507',
                              'validation'     => serialize($validation),
                              'source'         => $this->source,
                              'filepath'        => 'modules/dynamicdata/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}
?>
