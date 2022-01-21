<?php
/**
 * Utility Class to manage Dynamic Properties
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
// this is used in most methods below, so we import it here
sys::import('modules.dynamicdata.class.descriptor');

if (function_exists('xarModDBInfoLoad')) {
    xarModDBInfoLoad('dynamicdata','dynamicdata');
}
/**
 * Utility Class to manage Dynamic Properties
 *
 * @subpackage dynamicdata module
 */
//jojo - leave defines for now - remove when we know we have converted all to the class constants
    define('DD_DISPLAYSTATE_DISABLED',0);
    define('DD_DISPLAYSTATE_ACTIVE',1);
    define('DD_DISPLAYSTATE_DISPLAYONLY',2);
    define('DD_DISPLAYSTATE_HIDDEN',3);
    define('DD_DISPLAYSTATE_VIEWONLY',4);
    define('DD_DISPLAYSTATE_IGNORED',5);
    define('DD_DISPLAYSTATE_INPUTONLY',6);
    define('DD_DISPLAYSTATE_HIDDENDISPLAY',7);

    define('DD_INPUTSTATE_ADDMODIFY',32);
    define('DD_INPUTSTATE_NOINPUT',64);
    define('DD_INPUTSTATE_ADD',96);
    define('DD_INPUTSTATE_MODIFY',128);

    define('DD_DISPLAYMASK',31);

class Dynamic_Property_Master extends xarObject
{
    const DD_DISPLAYSTATE_DISABLED = 0;     //disable the property
    const DD_DISPLAYSTATE_ACTIVE = 1;       //active - input, display and view
    const DD_DISPLAYSTATE_DISPLAYONLY = 2;  //active - input and display only
    const DD_DISPLAYSTATE_HIDDEN = 3;       //active - hidden input, hidden display
    const DD_DISPLAYSTATE_VIEWONLY = 4;     //active - input and view only
    const DD_DISPLAYSTATE_IGNORED = 5;      //active - ignored on input, but shows for display and view
    const DD_DISPLAYSTATE_INPUTONLY = 6;    //active - input only - no view or display
    const DD_DISPLAYSTATE_HIDDENDISPLAY=7;  //active - hidden input - view and display
    //jojo - do we really want the following? Don't implement for now
    // more likely we just need to have Input or Ignored input only here and combine with display states (minus input states)
    const DD_INPUTSTATE_ADDMODIFY = 32;
    const DD_INPUTSTATE_NOINPUT = 64;
    const DD_INPUTSTATE_ADD = 96;
    const DD_INPUTSTATE_MODIFY = 128;

    const DD_DISPLAYMASK = 31;
    /**
     * Get the dynamic properties of an object
     *
     * @param $args['objectid'] the object id of the object, or
     * @param $args['moduleid'] the module id of the object +
     * @param $args['itemtype'] the itemtype of the object
     * @param $args['objectref'] a reference to the object to add those properties to (optional)
     * @param $args['allprops'] skip disabled properties by default
     */
    static function getProperties($args)
    {
        // we can't use our own classes here, because we'd have an endless loop :-)

        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;

        $dynamicprop = $xartable['dynamic_properties'];

        $bindvars = array();
        $query = "SELECT xar_prop_name,
                         xar_prop_label,
                         xar_prop_type,
                         xar_prop_id,
                         xar_prop_default,
                         xar_prop_source,
                         xar_prop_status,
                         xar_prop_order,
                         xar_prop_validation,
                         xar_prop_objectid,
                         xar_prop_moduleid,
                         xar_prop_itemtype
                  FROM $dynamicprop ";
        if (isset($args['objectid'])) {
            $query .= " WHERE xar_prop_objectid = ?";
            $bindvars[] = (int) $args['objectid'];
        } else {
            $query .= " WHERE xar_prop_moduleid = ?
                          AND xar_prop_itemtype = ?";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }
        $namedattributes = empty($args['namedattributes']) ? 0 : 1;
        if (empty($args['allprops'])) {
            $query .= " AND xar_prop_status > 0 ";
        }
        $query .= " ORDER BY xar_prop_order ASC, xar_prop_id ASC";

        $result = $dbconn->Execute($query,$bindvars);

        if (!$result) return;

        $properties = array();
        while (!$result->EOF) {
            list($name, $label, $type, $id, $default, $source, $fieldstatus, $order, $validation,
                 $_objectid, $_moduleid, $_itemtype) = $result->fields;
            if(xarSecurityCheck('ReadDynamicDataField',0,'Field',"$name:$type:$id")) {
                $property = array('name' => $name,
                                  'label' => $label,
                                  'type' => $type,
                                  'id' => $id,
                                  'default' => $default,
                                  'source' => $source,
                                  'status' => $fieldstatus,
                                  'order' => $order,
                                  'validation' => $validation,
                                  // some internal variables
                                  '_objectid' => $_objectid,
                                  '_moduleid' => $_moduleid,
                                  '_itemtype' => $_itemtype,
                                  'namedattributes' => $namedattributes
                                  );
                if (isset($args['objectref'])) {
                    Dynamic_Property_Master::addProperty($property,$args['objectref']);
                } else {
                    $properties[$name] = $property;
                }
            }
            $result->MoveNext();
        }

        $result->Close();

        return $properties;
    }

    /**
     * Add a dynamic property to an object
     *
     * @param $args['name'] the name for the dynamic property
     * @param $args['type'] the type of dynamic property
     * @param $args['label'] the label for the dynamic property
     * ...
     * @param $objectref a reference to the object to add this property to
     */
    static function addProperty($args, &$objectref)
    {
        if (!isset($objectref) || empty($args['name']) || empty($args['type'])) {
            return;
        }

        // "beautify" label based on name if not specified
        if (!isset($args['label']) && !empty($args['name'])) {
            $args['label'] = strtr($args['name'], '_', ' ');
            $args['label'] = ucwords($args['label']);
        }

        // get a new property
        $property = self::getProperty($args);

        // for dynamic object lists, put a reference to the $items array in the property
        if (method_exists($objectref, 'getItems')) {
            $property->_items =& $objectref->items;

        // for dynamic objects, put a reference to the $itemid value in the property
        } elseif (method_exists($objectref, 'getItem')) {
            $property->_itemid =& $objectref->itemid;
        }

        // add it to the list of properties
        $objectref->properties[$property->name] =& $property;

        // if the property wants a reference, give it
        if ($property->include_reference) {
            $objectref->properties[$property->name]->objectref = $objectref;
        }

        $objectref->properties[$property->name]->objectconfiguration =& $objectref->configuration;

        if (isset($property->upload)) {
            $objectref->upload = true;
        }
    }

    /**
     * Class method to get a new dynamic property of the right type
     * @param $args
     * @return object $property
     */
    static function getProperty($args)
    {
        if(!isset($args['name']) && !isset($args['type'])) {
            throw new BadParameterException(null,xarML('The getProperty method needs either a name or type parameter.'));
        }

        $proptypes = self::getPropertyTypes();
        if (!isset($proptypes)) {
            $proptypes = array();
        }
        $proptype['name'] ='';
        if (isset($args['name']) || !is_numeric($args['type']))  {
            if (!isset($args['type'])) {
                //<jojo> is this valid?
                if(isset($args['name'])) $args['type'] = $args['name'];
            }
            foreach ($proptypes as $typeid => $proptype) {
                if ($proptype['name'] == $args['type']) {
                    $args['type'] = $typeid;
                    break;
                }
            }
        }

        if( isset($proptypes[$args['type']]) && is_array($proptypes[$args['type']]) ) {
            $propertyInfo  = $proptypes[$args['type']];
            $propertyClass = $propertyInfo['propertyClass'];
            //jojo - cannot check for class exist here as we need to reload for alias data
            //if (!class_exists($propertyClass)) {
                // Filepath is complete rel path to the php file, and decoupled from the class name
                // We should load the MLS translations for the right context here, in case the property
                // PHP file contains xarML() statements
                // See bug 5097
                if(preg_match('/modules\/(.*)\/xarproperties/',$propertyInfo['filepath'],$matches) == 1) {
                    // @todo: The preg determines the module name (in a sloppy way, FIX this)
                    xarMLSLoadTranslations($propertyInfo['filepath']);
                } else {
                    xarLogMessage("WARNING: Property translations for $propertyClass NOT loaded");
                }

                if (!file_exists(sys::codeAbs().'/'.$propertyInfo['filepath'])) {
                    throw new FileNotFoundException($propertyInfo['filepath']);
                }

                $import = str_replace('/', '.', rtrim($propertyInfo['filepath'], '.php'));
                sys::import($import);

                if( isset($propertyInfo['args']) && ($propertyInfo['args'] != '') ) {
                    $baseArgs = unserialize($propertyInfo['args']);
                    $args = array_merge($baseArgs, $args);
                }
           //}
            $property = new $propertyClass($args);

        } else {
            $property = new Dynamic_Property($args);
        }
        return $property;
    }

    static function createProperty(Array $args)
    {
        $object = new Dynamic_Object(array('objectid' => 2)); // the Dynamic Properties = 2
        $objectid = $object->createItem($args);
        unset($object);
        return $objectid;
    }

    static function updateProperty(Array $args)
    {
        // TODO: what if the property type changes to something incompatible ?
    }
    /**
     * @param array $args
     * @return id objectid
     */
    static function deleteProperty(Array $args)
    {
        if (empty($args['itemid'])) return;

        // TODO: delete all the (dynamic ?) data for this property as well
        $object = new Dynamic_Object(array('objectid' => 2, // the Dynamic Properties = 2
                                           'itemid'   => $args['itemid']));
        if (empty($object)) return;

        $objectid = $object->getItem();
        if (empty($objectid)) return;

        $objectid = $object->deleteItem();
        unset($object);
        return $objectid;
    }

    /**
     * Class method listing all defined property types
     * @return array Array with property_types
     */

   static function getPropertyTypes()
    {
        if (xarCoreCache::isCached('DynamicData','PropertyTypes')) {
            return xarCoreCache::getCached('DynamicData','PropertyTypes');
        }

        // Attempt to retreive properties from DB
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;

        $dynamicproptypes = $xartable['dynamic_properties_def'];

        // Sort by required module(s) and then by id
        $query = "SELECT
                    xar_prop_id
                    , xar_prop_name
                    , xar_prop_label
                    , xar_prop_parent
                    , xar_prop_filepath
                    , xar_prop_class
                    , xar_prop_format
                    , xar_prop_validation
                    , xar_prop_source
                    , xar_prop_reqfiles
                    , xar_prop_reqmodules
                    , xar_prop_args
                    , xar_prop_aliases

                  FROM $dynamicproptypes
                  ORDER BY xar_prop_reqmodules, xar_prop_id";

        $result = $dbconn->Execute($query);

        if (!$result)
        {
            //TODO: Something interesting?  Probably an exception.
            return;
        }

        // If no properties are found, import them in.
        if( $result->EOF)
        {
            $property_types = xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush'=>false));
        } else {
            $property_types = array();
            while (!$result->EOF)
            {
                list($id,$name,$label,$parent,$filepath,$class,$format,$validation,$source,$reqfiles,$reqmodules,$args,$aliases) = $result->fields;

                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['format']         = $format;
                $property['filepath']       = $filepath;
                $property['validation']     = $validation;
                $property['source']         = $source;
                $property['dependancies']   = $reqfiles;
                $property['requiresmodule'] = $reqmodules;
                $property['args']           = $args;
                $property['propertyClass']  = $class;
                $property['aliases']        = $aliases;

                $property_types[$id] = $property;

                $result->MoveNext();
            }
        }
        $result->Close();

        xarCoreCache::setCached('DynamicData','PropertyTypes',$property_types);
        return $property_types;
    }
}

/**
 * Base Class for Dynamic Properties
 *
 * @subpackage dynamicdata module
 */
class Dynamic_Property extends xarObject
{
    //Registration
    public $id             = NULL;
    public $name           = 'Property';
    public $label          = 'Value';
    public $type           = 1; //property type id
    public $default        = ''; //default value
    public $source         = 'dynamic_data';    //data source
    public $status         = Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE;
    public $order          = 0; //2x is called $seq
    public $validation     = '';        //specific validation rule values for this property
    public $format         = '0';       //property format as per parent
    public $class          = '';         // this property's class
    public $filepath       = 'modules/dynamicdata/xarproperties';
    public $desc           = 'propertyDescription';
    public $requiresmodule = '';
    public $aliases         = array();

    //runtime
    public $template        = '';
    public $layout          = '';
    public $tplmodule       = 'dynamicdata';

    public $dependancies    = '';    // 2x semi-colon seperated list of files that must be present for this property to be available (optional)
    public $args           = array();   //args that hold alias info
    public $operation       = NULL;
    public $datastore       = '';    // name of the data store where this property comes from
    public $aliasname       = NULL;
    public $namedattributes = 0;    //use named attributes for id and name in tags instead of dd_xx

    public $value           = NULL;      // value of this property for a particular DataObject
    public $invalid         = '';      // result of the checkInput/validateValue methods
    public $fieldname       = NULL;     // fieldname used by checkInput() for configurations that need it (e.g. file uploads)

    public $include_reference = 0; // tells the object that this property belongs to, to add a reference of itself to me
    public $objectref       = NULL;
    public $_objectid       = NULL; // objectid this property belongs to
    public $_fieldprefix = ''; // the object's fieldprefix
    public $_moduleid       = NULL; // moduleid this property belongs to
    public $_itemtype       = NULL; // itemtype this property belongs to
    public $_itemid;          // reference to $itemid in Dynamic_Object, where the current itemid is kept
    public $_items;           // reference to $items in Dynamic_Object_List, where the different item values are kept

    //public validation attributes
    public $configtypes = array('definition','validation','display');
    public $xv_autocomplete    = FALSE;    //On of off - allow auto-fill of a form element
    public $xv_form            = '';       //one or more forms the input field belongs to
    public $xv_required        = FALSE;    //defines if input field value is required for submit
    public $xv_allowempty      = TRUE;     //allow empty field
    public $xv_transform       = FALSE;    //transform this field
    public $xv_cansearch       = TRUE;    //searchable
    public $xv_disabled        = FALSE;    //disabled - not used with hidden
    public $xv_tooltip         = '';       //tooltip
    public $xv_classname       = '';       // css class
    public $xv_display_layout = 'default';

    /**
     * Default constructor setting the variables
     */
    function __construct($args)
    {
        if (!empty($args) && is_array($args) && count($args) > 0) {
            foreach ($args as $key => $val) {
                $this->$key = $val;
            }
        }

         // load the configuration, if one exists
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }

        if (!isset($args['value'])) {
            // if the default field looks like xar<something>(...), we'll assume that this is
            // a function call that returns some dynamic default value
           // In 2x we use the commented form below - in xarigami we have the xar name space
           // if(!empty($this->defaultvalue) && preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(.*\)/',$this->defaultvalue)) {
            if (!empty($this->default) && preg_match('/^xar\w+\(.*\)$/',$this->default)) {
                eval('$value = ' . $this->default .';');
                if (isset($value)) {
                    $this->default = $value;
                } else {
                    $this->default = null;
                }
            }

            try {
                 $this->setValue($this->default);
            } catch (Exception $e) {
            }

        } else {
            $this->setValue($args['value']);
        }

        //  minimum for alias info
        if (!empty($this->args)) {
            try {
                $this->args = unserialize($this->args);
            } catch (Exception $e) {
                xarLogMessage('DD PROPERTIES: '.getMessage($e));
            }
        }

    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param $value the new value for the property
     */
    public function setValue($value=null)
    {
        $this->value = $value;
    }

    /**
     * Fetch the input value of this property
     *
     * @param string $name name of the input field
     * @return array an array containing a flag whether the value was found and the found value itself
     */
    public function fetchValue($name = '')
    {
        $found = false;
        $value = null;
        xarVarFetch('dd_'.$this->id, 'isset', $ddvalue,  NULL, XARVAR_DONT_SET);
        if(isset($ddvalue)) {
            $found = true;
            $value = $ddvalue;
        } else {
            xarVarFetch($name, 'isset', $namevalue,  NULL, XARVAR_DONT_SET);
            if(isset($namevalue)) {
                $found = true;
                $value = $namevalue;
            } else {
                xarVarFetch($this->name, 'isset', $fieldvalue,  NULL, XARVAR_DONT_SET);
                if(isset($fieldvalue)) {
                    $found = true;
                    $value = $fieldvalue;
                }
            }
        }
        return array($found,$value);
    }
    /**
     * Check if a given value is unique for this module/itemtype
     */
    public function checkUnique($fieldname='',$value = null)
    {
        $where = "$fieldname eq '$value'";
        $objectref = $this->objectref;
        $itemid = $objectref->itemid;
        $argslist =  array('moduleid'  => $objectref->moduleid,
                       'itemtype'  =>  $objectref->itemtype,
                       'where' => $where,
                       'join' =>  $objectref->join,
                       'table' =>  $objectref->table);

        $dynamicMaster = new Dynamic_Object_Master($argslist);
        $object =$dynamicMaster->getObjectList($argslist);

        $check = TRUE;
        if (!isset($object)) {
            //What do we want to do here?
        } else {
            $result = $object->getItems();
            if (empty($itemid) && (count($result) > 0)) { //creating an item and checking
                $check = FALSE;
            } elseif (($itemid > 0) && (count($result) == 1)) { //updating an item but changed value
                $check = array_key_exists($itemid,$result); //if false the value was changed and value exists already
            } elseif (($itemid> 0) && (count($result) > 1)) { //updating an item
                 $check = FALSE;
            }
        }
        return $check;
    }
    /**
     * Check the input value of this property
     *
     * @param $name name of the input field (default is 'dd_NN' with NN the property id)
     * @param $value value of the input field (default is retrieved via xarVarFetch())
     */
    public function checkInput($name = '', $value = null)
    {

        //store the fieldname in case it is required by other methods/functions (e.g. file uploads)
        $name = empty($name) ? 'dd_'.$this->id : $name;
          $this->fieldname = $name;
        $namelabel = isset($this->label) && !empty($this->label)?$this->label:$this->name;

        $this->invalid = '';
         if (!isset($value)) {
            list($found,$value) = $this->fetchValue($name);

            if (!$found) {
                $this->objectref->missingfields[] = $namelabel;//$this->name;
                return null;
            }
        }
        return $this->validateValue($value);
    }

    /**
     * Validate the value of this property
     *
     * @param $value value of the property (default is the current value)
     */
    public function validateValue($value = null)
    {
        $isvalid = true;

        if (!isset($value)) {
            $value = $this->getValue();
        }else {
            $this->setValue($value);
        }

        $thename = !empty($this->label)?$this->label:$this->name;
        if (isset($this->xv_notequal)  && $value == $this->xv_notequal) {
            if (!empty($this->xv_notequals_invalid)) {
                $this->invalid = xarML($this->xv_notequal_invalid);
            } else {
            $thevalue = $this->value;
            $thevalue = $this->showOutput(array($thevalue));
                $this->invalid = xarML("The value of '#(2)' is not allowed for '#(1)'", $thename,$thevalue );
            }
            //$this->value = null; - retain for display so user can see what they typed
             $isvalid = false;
        } elseif ($this->xv_allowempty != 1 && $value=='') {

            if (!empty($this->xv_allowempty_invalid)) {
                $this->invalid = xarML($this->xv_allowempty_invalid);
            } else {
                $this->invalid = xarML('#(1) cannot be empty', $thename);
            }
            $this->value = null;
            $isvalid = false;
        } elseif (isset($this->xv_isunique) && ($this->xv_isunique ==1)) {
            if (!isset($this->objectref))  {
                //probably some faux property - eg Articles title, just return for now until we decide how to handle it
            } else {
                $isunique = $this->checkUnique($this->name,$value);
                if ($isunique === FALSE ) {
                    $this->invalid = xarML('Invalid: #(1) already exists but must be unique', $thename);
                   // $this->value = null;
                    $isvalid = false;
                }
            }
        }

        return $isvalid;
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param $itemid the item id we want the value for
     */
    function getItemValue($itemid)
    {
        return $this->_items[$itemid][$this->name];
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     */
    function setItemValue($itemid, $value)
    {
        $this->_items[$itemid][$this->name] = $value;
    }

   /**
     * Get and set the value of this property's display status
     */
    function getDisplayStatus()
    {
        return ($this->status & Dynamic_Property_Master::DD_DISPLAYMASK);
    }
    function setDisplayStatus($status)
    {
         $this->status = $status & Dynamic_Property_Master::DD_DISPLAYMASK;
    }

    /**
     * Get and set the value of this property's input status
     * jojo- we don't use this atm
     */
    function getInputStatus()
    {
        return $this->status - $this->getDisplayStatus();
    }
    function setInputStatus($status)
    {
        $this->status = $status - $this->getDisplayStatus();
    }

  /**
     * Show an input field for setting/modifying the value of this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showInput(Array $args = array())
    {
      if (!empty($args['hidden'])) {
            if ($args['hidden'] == 'active') {
                $this->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE);
            } elseif ($args['hidden'] == 'display') {
                $this->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_DISPLAYONLY);
            } elseif ($args['hidden'] == 'hidden') {
                $this->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN);
            }
        }

        if (($this->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN) ||
            ($this->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDENDISPLAY) ||
            ($this->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)) {
            return $this->showHidden($args);
        }

        if (!isset($args['validation'])) {
             $args['validation']= $this->validation;
        }

        // Display directive for the name
        if (!isset($args['name'])) {
            if (isset($this->namedattributes) && $this->namedattributes == true)
            {
                $args['name'] = $this->name;
            } else {
                $args['name'] = 'dd_'.$this->id;
            }
        }

        if(!isset($args['id'])) $args['id']   = $args['name'];

        // Add the object's field prefix if there is one
        $prefix = '';
        // Allow 0 as a fieldprefix
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === 0)  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($args['fieldprefix']))  $prefix = $args['fieldprefix'] . '_';
        if(!empty($prefix)) $args['name'] = $prefix . $args['name'];
        if(!empty($prefix)) $args['id'] = $prefix . $args['id'];

        if(!isset($args['tplmodule']))   $args['tplmodule']   = $this->tplmodule;
        if(!isset($args['template'])) $args['template'] = $this->template;
        if(!isset($args['layout']))   $args['layout']   = $this->xv_display_layout;

        if(!isset($args['tabindex'])) $args['tabindex'] = 0;

        if(!isset($args['value']))    $args['value']    = $this->value;

        if (!empty($this->invalid)) {
            $args['invalid']  = !empty($args['invalid']) ? $args['invalid'] : xarML($this->invalid);
        } else {
            $args['invalid']  = '';
        }
        $args['class']      = !empty($args['class'])  ?  $args['class']  : $this->xv_classname;
        if(!isset($args['style'])) $args['style'] = '';
         // Add the configuration options defined via UI
        if(isset($args['validation'])) {
            $this->parseValidation($args['validation']);
            $props = $this->getValidationProperties();
            foreach($props as $propname=>$prop) {
                if (strpos($propname,'xv_')===0) {
                      $propname =  substr_replace($propname,'',0,3);
                }
                if (!isset($args[$propname])) {
                    $args[$propname] = isset($prop['value'])?$prop['value']:null;
                } else {
                    $args[$propname] = isset($args[$propname])?$args[$propname]:$prop['value'];
                }

            }
            unset($args['validation']);
        }

        $evts = '';
        $evtlist = array('onblur','onchange','onfocus','onreset','onselect','onsubmit','onabort','onkeydown','onkeypress',
                         'onkeyup','onclick','ondblclick','onmousedown','onmousemove','onmouseout','onmouseover','onmouseup');
        foreach ($evtlist as $eattr)
        {
            if (!empty($args[$eattr])) {
                $evts .=" $eattr=\"$args[$eattr]\"";
            }
        }
        $args['evts'] = $evts;

        $args['label'] = isset($label) ? $label :$this->label;
        $args['autocomplete'] = isset($args['autocomplete']) && ($args['autocomplete'] == TRUE) ? 'off':'';

        $args['disabled'] = (isset($args['disabled']) && !empty($args['disabled']) && $args['disabled'] == 1 ) ? 'disabled':'';

        $args['required'] = (isset($args['required']) && !empty($args['required']) && $args['required'] == 1) ? 'required':'';
        $html5tags = array('disabled','required','aria-required','aria-describedby','placeholder','aria-valuemin','aria-valuemax',
                           'contenteditable','accesskey','contextmenu','dir','draggable','dropzone','spellcheck','tel','search','url',
                           'email','datetime','date','month','week','time','datetime-local','mumber','range','color','autocomplete',
                           'autofocus','form','height','width','list','step','multiple','novalidate','pattern',
                           'readonly','formnovalidate','formtarget','accept');
        $html5= '';
        foreach ($html5tags as $attr)
        {
            if (!empty($args[$attr])) {
                $html5 .=" $attr=\"$args[$attr]\"";
            }
        }

        $args['html5'] = $html5;
        return xarTplProperty($args['tplmodule'], $args['template'], 'showinput', $args);
    }

    /**
     * Show some default output for this property
     *
     * @param $args['value'] value of the property (default is the current value)
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showOutput(Array $args = array())
    {
        extract($args);
        if (!empty($args['hidden'])) {
            if ($args['hidden'] == 'active') {
                $this->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE);
            } elseif ($args['hidden'] == 'display') {
                $this->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_DISPLAYONLY);
            } elseif ($args['hidden'] == 'hidden') {
                $this->setDisplayStatus(Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN);
            }
        }

        if (($this->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN) ||
            ($this->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_INPUTONLY)) {
            return $this->showHidden($args);
        }
        //this is usually taken care of in a get properties or object display but
        //in some cases we want to use the properties stand alone
        if ($this->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED) {
            return ;
        }
         // Display directive for the name
        if(!isset($args['name'])) {
            if (isset($this->namedattributes) && $this->namedattributes == true)
            {
                $args['name'] = $this->name;
            } else {
                $args['name'] = 'dd_'.$this->id;
            }
        }
        //if(!isset($args['id'])) $args['id']   = $args['name'];
        //$args['id']   = $this->id;
        //$args['name'] = $this->name;
        if (empty($args['_itemid'])) $args['_itemid'] = 0;

        if(!isset($args['tplmodule']))   $args['tplmodule']   = $this->tplmodule;
        if(!isset($args['template'])) $args['template'] = $this->template;
        if(!isset($args['layout']))   $args['layout']   = $this->xv_display_layout;
        if (!isset($args['validation'])) $args['validation'] = $this->validation;

         // Add the validation options defined via UI
        if(isset($args['validation'])) {
            $this->parseValidation($args['validation']);
        }

        $props = $this->getValidationProperties();
        foreach($props as $propname=>$prop) {
                if (strpos($propname,'xv_') === 0) {
                      $propname =  substr_replace($propname,'',0,3);
                }
                $args[$propname] = isset($args[$propname])?$args[$propname]:$prop['value'];
        }

        unset($args['validation']);
        //allow gui to override
        //make sure value is set for template that needs it
        if (!isset($args['value'])) $args['value']= $this->value;

        return xarTplProperty($args['tplmodule'], $args['template'], 'showoutput', $args);
    }

    /**
     * Show the label for this property
     *
     * @param $args['for'] label id to use for this property (id, name or nothing)
     * @param $args['label'] label of the property (default is the current label)
     * @return string containing the HTML (or other) text to output in the BL template
     * jojo - so in this case of for or label, 'id' and 'name' are both reseved and cannot be used as label name
     *  'for' - use it for the normal use of the word so we take that as override of label if present for the for attributes
     */
    function showLabel(Array $args = array())
    {
         if($this->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN ||
            $this->type == 18)  return '';
        if (empty($args))
        {
        // old syntax was showLabel($label = null)
        } elseif (is_string($args)) {
            $label = $args;

        } elseif (is_array($args)) {
            extract($args);
        }

        if (!isset($args['label'])) $args['label'] = '';
        // Display directive for the name in the input tag - if this is set we need 'for' to match it
        //This is in effect the same as having 'label' => name - to use in the for attribute
        if(!isset($args['name']) || empty($args['name'])) {
            if ((isset($this->namedattributes) && $this->namedattributes == 1) || ($args['label'] == 'name')) //
            {
                $args['name'] = !empty($args['name']) ? $args['name'] : $this->name;
            } else {
                $args['name'] = 'dd_'.$this->id;
            }
        }
        if(!isset($args['id']) ) $args['id']   = $args['name'];

        //we need to make 'for' match the name of the property if it is not passed in via args
        $args['for'] =  $args['name'] ;
        //jojo - it would be better to do all this here rather than pass 'for' and assign in the template
        // however we need to maintain some backward compat or do we? Let's take it out and tidy template and hope not too many people override
        /*if ((isset($this->namedattributes) && $this->namedattributes == 1) || ($args['label'] == 'name')) //
        {
            $strtest= substr($args['name'],0,3) == 'dd_' ? 'id':'name';
            $args['for']   = isset($for) ? $for : $strtest;
        } else {
            $args['for']   = isset($for) ? $for : 'name';
        }
        */
        $args['for'] = $args['name'];
        $args['label'] = (isset($label) && ($label !='id') && $label !='name') ? $label: xarVarPrepForDisplay($this->label);

        if(!empty($this->_fieldprefix) || $this->_fieldprefix === 0)  $args['fieldprefix'] = $this->_fieldprefix;
        // A field prefix added here can override the previous one
        if(isset($args['fieldprefix']))  $prefix = $args['fieldprefix'] . '_';
        if(!empty($prefix)) $args['name'] = $prefix . $args['name'];
        if(!empty($prefix)) $args['id'] = $prefix . $args['id'];

        $args['required'] = isset($required) && !empty($required)? 'xar-required': '';

        if(!isset($args['tplmodule']))   $args['tplmodule']   = $this->tplmodule;
        if(!isset($args['template'])) $args['template'] = $this->template;
        if(!isset($args['layout']))   $args['layout']   = $this->layout;
        if(!isset($args['class']))   $args['class']   =  ''; //can't pass class from GUI - this is the property class for eg a textbox etc
        if(!isset($args['title']))   $args['title']   = isset($this->xv_tooltip)?($this->xv_tooltip):'';

        return xarTplProperty($args['tplmodule'], $args['template'], 'label', $args);

    }

    /**
     * Show a hidden field for this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showHidden(Array $args = array())
    {
        extract($args);
        $args['name']     = !empty($name) ? $name : 'dd_'.$this->id;
        $args['id']       = !empty($id)   ? $id   : 'dd_'.$this->id;

        if (!isset($args['validation'])) {
             $args['validation']= $this->validation;
        }

        // Display directive for the name
        if (!isset($args['name'])) {
            if (isset($this->namedattributes) && $this->namedattributes == true)
            {
                $args['name'] = $this->name;
            } else {
                $args['name'] = 'dd_'.$this->id;
            }
        }

        if(!isset($args['id'])) $args['id']   = $args['name'];

        // Add the object's field prefix if there is one
        $prefix = '';
        // Allow 0 as a fieldprefix
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === 0)  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($args['fieldprefix']))  $prefix = $args['fieldprefix'] . '_';
        if(!empty($prefix)) $args['name'] = $prefix . $args['name'];
        if(!empty($prefix)) $args['id'] = $prefix . $args['id'];

        $args['value']    = isset($value) ? $value : $this->getValue();

        if (is_array($value)){
            $temp = array();
            foreach ($value as $key => $tmp) $temp[$key] = xarVarPrepForDisplay($tmp);
            $args['value'] = $temp;
        } else {
            $args['value'] = xarVarPrepForDisplay($value);
        }

       // $args['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $args['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        if(isset($args['validation'])) {
            $this->parseValidation($args['validation']);
            $props = $this->getValidationProperties();
            foreach($props as $propname=>$prop) {
                if (strpos($propname,'xv_')===0) {
                      $propname =  substr_replace($propname,'',0,3);
                }
                if (!isset($args[$propname])) {
                    $args[$propname] = isset($prop['value'])?$prop['value']:null;
                } else {
                    $args[$propname] = isset($args[$propname])?$args[$propname]:$prop['value'];
                }

            }
            unset($args['validation']);
        }

        if(!isset($args['tplmodule']))   $args['tplmodule']   = $this->tplmodule;
        if(!isset($args['template'])) $args['template'] = $this->template;
        if(!isset($args['layout']))   $args['layout']   = $this->layout;

        return xarTplProperty($args['tplmodule'], $args['template'], 'showhidden', $args);
    }

    /**
     * For use in DD tags : preset="yes" - this can typically be used in admin-new.xd templates
     * for individual properties you'd like to automatically preset via GET or POST parameters
     *
     * Note: don't use this if you already check the input for the whole object or in the code
     * See also preview="yes", which can be used on the object level to preview the whole object
     *
     * @access private (= do not sub-class)
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function _showPreset(Array $args = array())
    {
            if (empty($args['name'])) {
                $isvalid = $this->checkInput();
            } else {
                $isvalid = $this->checkInput($args['name']);
            }
            if ($isvalid) {
                // remove the original input value from the arguments
                unset($args['value']);
            } else {
                $isvalid = $this->checkInput($this->name);
                //$this->invalid = '';
            }


        if (!empty($args['hidden'])) {
            return $this->showHidden($args);
        } else {
            return $this->showInput($args);
        }
    }


    /**
     * Get the base information for this property.
     *
     * @return base information for this property
     **/
    function getBasePropertyInfo()
    {
        //$validation = $this->getBaseValidationInfo(); //we don't need this here in parent
        $baseInfo = array(
                          'id'         => $this->id,
                          'name'       => $this->name,
                          'label'      => $this->label,
                          'format'     => $this->format,
                          'validation' => serialize(array()), //serialized array of validation/configuration options
                          'source'     => $this->source,
                          'filepath'   => $this->filepath, //base file path
                          'dependancies' => isset($this->dependencies)?$this->dependencies:'',    // semi-colon seperated list of files that must be present for this property to be available (optional)
                          'requiresmodule' => $this->requiresmodule, // this module must be available before this property is enabled (optional)
                          'aliases' => $this->aliases,        // If the same property class is reused directly with just different base info, supply the alternate base properties here (optional)
                          'args' => serialize( array() ),
                          // ...
                         );
        return $baseInfo;
    }
    /**
     * Return the specific base validation options for this property and global shared ones
     *
     * @param $type:  type of option (shared, base, public, all)
     * @return array of validation options
     */
    public function getValidationProperties($valtype="all")
    {
             //validation props inherited by all props - do not cache here
        $sharedvalidations = $this->getBaseValidationInfo();
       //the specific property validations
        $baseinfo= array();
        $baseinfo= $this->getBasePropertyInfo();
        //this should be a serialized array - but during this rework let's make sure
        $basevalidations = is_array($baseinfo['validation'])?$baseinfo['validation']:unserialize($baseinfo['validation']);
        if (!is_array($basevalidations)) $basevalidations = array();

        if ($valtype == 'all') {
            //make sure we merge correctly to allow specific property overrides for validation options
            $validationprops = array_merge($sharedvalidations, $basevalidations);
        } elseif ($valtype == 'base') {
            $validationprops = $basevalidations;
        } elseif ($valtype =='shared') {
            $validationprops = $sharedvalidations;
        } else {
             $validationprops = $pubproperties;
        }

        if (empty($validationprops)) return $validationprops;

        $valpropertylist = array();
        //all property attributes
        $pubproperties = $this->getPublicProperties();
        //now we have a list of the validationprops
        foreach($validationprops as $name=>$args) {
            //if (!isset($pubproperties[$name])) continue;
            $valpropertylist[$name] = $validationprops[$name];
            $valpropertylist[$name]['value'] = isset($pubproperties[$name])?$pubproperties[$name]:null;
            $valpropertylist[$name]['configinfo'] = isset($validationprops[$name]['configinfo'])?$validationprops[$name]['configinfo']:'';
            $valpropertylist[$name]['ctype'] = isset($validationprops[$name]['ctype'])?$validationprops[$name]['ctype']:'definition';
             $valpropertylist[$name]['propargs'] = isset($validationprops[$name]['propargs'])?$validationprops[$name]['propargs']:array();
        }
       return $valpropertylist;
    }
    /**
     * The following methods provide an interface to show and update validation rules
     * when editing dynamic properties. They should be customized for each property
     * type, based on its specific format and interpretation of the validation rules.
     *
     * This allows property type developers to support more complex validation rules,
     * while keeping them easy to modify for the site admins afterwards.
     *
     * If no validation methods are specified for a particular property type, the
     * corresponding methods from its parent class will be used.
     *
     * Note: the methods can be called by DD's showpropval() function, or if you set the
     *       type of the 'validation' property (21) to Dynamic_Validation_Property also
     *       via DD's modify() and update() functions if you edit some dynamic property.
     */

    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showValidation(Array $data= array())
    {
        extract($data);

        if (!isset($validation)) $validation = $this->validation;
        $this->parseValidation($validation);

        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : (!empty($this->id)?'dd_'.$this->id:$data['name']);
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['required']   = isset($data['required']) && ($data['required'])? true:false;
        if(!isset($data['module']))   $data['module']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->xv_display_layout;
        if (!isset($validationprops) || empty($validationprops)) {
            $validationprops = $this->getValidationProperties();
        }
        $configtypes = array();
        //get invalid messages and ensure validation value is set in case of preview
        if (is_array($validation)) {
            foreach ($validation as $name=>$value) {
                $validationprops[$name]['value'] = $value;
            }
        }
        foreach($validationprops as $valitem=>$vinfo) {
            if (!isset($vinfo['value'])) {
                $validationprops[$valitem]['value'] = '';
            }
            //build options
            if (!isset($vinfo['propertyname'])) continue;

            $proparray = array(
                'type'=> $vinfo['propertyname'],
                'name'=> $data['name'] . '[' . $valitem . ']',
                'id'  => $data['id'] . '_' . $valitem,
                'value'=>isset($vinfo['value'])?$vinfo['value']:'',
                );

            $validationprops[$valitem]['propargs'] = array_merge($proparray, $vinfo['propargs']);

            $msgname = $valitem. '_invalid';
            if (isset($validation[$msgname])) {
                $this->$msgname = $validation[$msgname];
            }

            //setup array of data for displaying our validation props nicely
            $ctype = $validationprops[$valitem]['ctype'];
            $configtypes[$ctype][$valitem]= $validationprops[$valitem];
        }
        ksort($configtypes);
        $data['configtypes'] = $configtypes;
        // allow template override by child classes
        if (!isset($data['template'])) {
            $data['template'] = null;
        }

        return xarTplProperty($data['module'], $data['template'], 'configuration', $data);
    }

    /**
     * Update the current validation rule in a specific way for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @return bool true if the validation rule could be processed, false otherwise
     */
    function updateValidation(Array $args = array())
    {
        extract($args);
        $valid = true;

        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;

        // do something with the validation and save it in $this->validation
        if (isset($validation) && is_array($validation)) {

            $validationsave = array();
            $valproperties = $this->getValidationProperties();
            foreach ($valproperties as $name => $valarg) {
                 if (isset($validation[$name]) && !empty($name)) {
                        if ($valarg['ignore_empty'] && ($validation[$name] == '')) continue;
                        $validationsave[$name] = $validation[$name];
                        //get the info we want to add to pass to property
                        $valarray = array('type' => $valarg['propertyname'],
                                           'value' => $validation[$name],
                                           'label'=> $valarg['label']);
                         //get the property for validation
                        $proptype = Dynamic_Property_Master::getProperty($valarray);
                       if (!empty($valarg['propargs'])) {
                            foreach($valarg['propargs'] as $opt=>$optvalue)
                            {
                                $proptype->$opt= $optvalue;
                            }
                        }
                        if(is_object($proptype)) {
                            $isvalid = $proptype->validateValue($validation[$name]);
                            if (!$isvalid) {
                                $validation[$name.'_invalid'] = $proptype->invalid;
                            }
                        }
                    }
                // Invalid messages only get stored if they are non-empty. For all others we check whether they exist (for now)
                 $msgname = $name . '_invalid';
                if (isset($validation[$msgname]) && !empty($validation[$msgname])) {
                    $validationsave[$msgname] = $validation[$msgname];
                    $valid = false;
                }
            }
            $this->validation = serialize($validationsave);
        } else {
            $this->validation = serialize(array());
            $valid = true;
        }

        return $valid;
    }

    /**
     * Parse the validation rule
     */
    function parseValidation($validation = '')
    {

       //we want to have a serialized array, ideally but we have a lot of legacy
        if (is_array($validation)) {
            $fields = $validation;
        } elseif (empty($validation)) {
            return true;
        // fall back to the old N:M validation for text boxes et al. (cfr. utilapi_getstatic/getmeta)
        //legacy support
        //} elseif (is_string($validation) && strchr($validation, ':')) {
        } elseif (preg_match('/^(\d+):(\d+)$/', $validation, $matches)) {
             $fields = array('min_length' => $matches[1],
                            'max_length' => $matches[2],
                            'maxlength'     => $matches[2]);

            if (isset($matches[3])) {
                //$this->pattern = $fields[2]; // the rest belongs to the regular expression
                 $fields['pattern'] = $matches[3];
            }

        // try normal serialized configuration
        } else {
            try {
                $fields = @unserialize($validation);
            } catch (Exception $e) {
                // if the configuration is malformed just return an empty configuration
                $fields = array();
                return true;
            }
        }
        $valprops = array();

        if (!empty($fields) && is_array($fields)) {
            $valprops = $this->getValidationProperties();
             foreach ($valprops as $name=>$detail) {
                if (isset($fields[$name])) {
                    $this->$name = $fields[$name];
                    $valprops[$name]['value'] = $fields[$name];
                } else {
                    $this->$name = null;
                }
                $msgname = $name . '_invalid';
                if (isset($fields[$msgname])) {
                    $this->$msgname = $fields[$msgname];
                }
            }
        }
    }
    public function getBaseValidationInfo()
    {
         //validation props inherited by all props
        static  $sharedvalidations = array();

        if (empty($sharedvalidations)) {

            $sharedvalidations=
                array(

                    'xv_allowempty'   =>  array('label'         => xarML('Allow empty'),
                                                'description'   => xarML('Allow empty as valid entry'),
                                                'propertyname'  => 'checkbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  => 1,
                                                'configinfo'    => xarML("Note: Empty and 0 may be treated differently depending on the property."),
                                                'ctype'         => 'validation'),
                    'xv_notequal'     =>  array('label'         =>xarML('Does not equal'),
                                                'description'   =>xarML('Input must not be equal to this value'),
                                                'propertyname'  => 'textbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => '',
                                                'ctype'         =>'validation'),
                    'xv_other'        =>  array('label'         => xarML('Other general rule'),
                                                'description'   => xarML('Other rule for this field'),
                                                'propertyname'  => 'textbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => '',
                                                'ctype'         => 'validation',
                                                'configinfo'    => ''),
                    'xv_required'     =>  array('label'         => xarML('Tag as Required'),
                                                'description'   => xarML('Tag this field as required in input form'),
                                                'propertyname'  => 'checkbox',
                                                'propargs'      => array(),
                                                'configinfo'    => '',
                                                'ignore_empty'  => 1,
                                                'ctype'         => 'display'),
                    'xv_disabled'     =>  array('label'         => xarML('Disabled'),
                                                'description'   => xarML('Disable this field'),
                                                'propertyname'  => 'checkbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => '',
                                                'ctype'         => 'display'),

                    'xv_tooltip'      =>  array('label'         => xarML('Tool tip'),
                                                'description'   => xarML('Tool tip for this field'),
                                                'propertyname'  => 'textbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => '',
                                                'ctype'=>      'display'),
                    'xv_display_layout'=>  array('label'        => xarML('Template layout'),
                                                'description'   => xarML('Template layout for display'),
                                                'propertyname'  => 'textbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => '',
                                                'ctype'=>      'display'),
                    'xv_form'          =>  array('label'        => xarML('Bound forms'),
                                                'description'   => xarML('Form ids of forms the input field belongs to'),
                                                'propertyname'  => 'textbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => '',
                                                'ctype'         => 'display'),
                    'xv_transform'     =>  array('label'        => xarML('Run transform'),
                                                'description'   => xarML('Run transform on this field value'),
                                                'propertyname'  => 'checkbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => '',
                                                'ctype'         => 'definition'),
                    'xv_cansearch'    =>  array('label'         => xarML('Search field'),
                                                'description'   => xarML('Enable searching on field contents'),
                                                'propertyname'  => 'checkbox',
                                                'propargs'      => array(),
                                                'ignore_empty'  =>1,
                                                'configinfo'    => xarML('Property will display in search forms'),
                                                'ctype'         => 'display'),
                    'xv_classname'   =>  array('label'=>xarML('Class'),
                                                'description'=>xarML('A class set here will override a class set in the showinput property template.'),
                                                'propertyname'=>'textbox',
                                                'ignore_empty'  =>1,
                                                'ctype'=>'display',
                                                'propargs'=> array('size'=>40),
                                                 'configinfo'    => xarML('Enter a space to force no class else a default is added')
                                                  ),
                    );
        }
        return $sharedvalidations;
    }
    /**
     * Return the name this property uses in its templates
     *
     * @return string template name
     */
    protected function getTemplate()
    {
        // If not specified, default to the registered name of the prop
        $template = empty($this->template) ? $this->name : $this->template;
        return $template;
    }

    private function getPrefix($args=null)
    {
        // Add the object's field prefix if there is one
        $prefix = '';
        // Allow 0 as a fieldprefix
        if(!empty($this->_fieldprefix) || $this->_fieldprefix === 0)  $prefix = $this->_fieldprefix . '_';
        // A field prefix added here can override the previous one
        if(isset($args['fieldprefix']))  $prefix = $args['fieldprefix'] . '_';
        return $prefix;
    }

    /**
     * Return the module this property belongs to
     *
     * @return string module name
     */
    function getModule()
    {
        $info = $this->getBasePropertyInfo();
        $modulename = $info['requiresmodule'];

        if (empty($modulename)) {
            //Do some funky error handling thing here
            return;
        }
        return $modulename;
    }
/**
     * Return the configuration value for a given property
     * @return array $config array of configuration attributes
     */
    function getConfiguration()
    {
        $vars = get_object_vars($this);
        $config = array();
        foreach($vars as $name=>$value)
        {
            if (substr($name,0,3) == 'xv_'  && isset($value) && !empty($value) )
            {
                $config[$name] = $value;
            }
        }
        return $config;
    }
}

/**
 * Class to model registration information for a property
 *
 * This corresponds directly to the db info we register for a property.
 * jojo - this seems to add time to retrieval without any identified benefit at this time = not used for Retrieval
 */
class PropertyRegistration extends xarDataContainer
{
    public $id         = 0;                      // id of the property, hardcoded to make things easier
    public $name       = 'propertyType';         // what type of property are we dealing with
    public $desc       = 'Property Description'; // description of this type
    public $label      = 'propertyLabel';        // the label of the property are we dealing with
    public $type       = 1;
    public $parent     = '';                     // this type is derived from?
    public $class      = '';                     // what is the class?
    public $validation = '';                     // what is its default validation?
    public $source     = 'dynamic_data';         // what source is default for this type?
    public $reqfiles   = array();                // do we require some files to be present?
    public $reqmodules = '';                // do we require some modules to be present?
    public $args       = array();                // special args needed?
    public $aliases    = array();                // aliases for this property
    public $filepath   = "";                     // path to the directory where the property lives
    public $format     = 0;                      // what format type do we have here?
                                                 // 0 = ? what?
                                                 // 1 =

    public $default        = '';
    public $status         = 0;
    public $order          = 0;

     function __construct($args)
    {
        if (!empty($args) && is_array($args) && count($args) > 0) {
            foreach ($args as $key => $val) {
                $this->$key = $val;
            }
        }
    }

    static function clearCache()
    {
        $dbconn = xarDB::$dbconn;
        xarModDBInfoLoad('dynamicdata','dynamicdata');
        $tables = xarDBGetTables();
        $sql = "DELETE FROM  ".$tables['dynamic_properties_def'];
        $res = $dbconn->Execute($sql);
        return $res;
    }

    static public function getRegistrationInfo(Object $class)
    {
        $this->id   = $class->id;
        $this->name = $class->name;
        $this->desc = $class->desc;
        $this->reqmodules = $class->reqmodules;
        $this->args = $class->args;
        $this->filepath = $class->filepath;
        $this->template = $class->template;
        return $this;
    }
    /*
     * Register a dynamic data property
     */
    function Register()
    {
        static $types = array();

        // Sanity checks (silent)
        foreach($this->reqfiles as $required)
            if(!file_exists($required))
                return false;
/*
        foreach($this->reqmodules as $required)
            if(!xarMod::isAvailable($required))
                return false;
*/
        $dbconn = xarDB::$dbconn;
        xarModDBInfoLoad('dynamicdata','dynamicdata');
        $tables = xarDBGetTables();
        $propdefTable = $tables['dynamic_properties_def'];


        assert(count($this->reqmodules)==1, 'The reqmodules registration should only contain the name of the owning module');
       if (is_array($this->reqmodules)) $this->reqmodules = $this->reqmodules[0];

       if (!isset($this->reqmodules) || empty($this->reqmodules)) $this->reqmodules = 'base';

        $modInfo = xarMod::getBaseInfo($this->reqmodules);
        $modId = $modInfo['systemid'];
        $modName = $modInfo['name'];

        if($this->format == 0) $this->format = $this->id;
        if (empty($types)) {
            $sql = "SELECT xar_prop_id FROM $propdefTable ";
            $result = $dbconn->Execute($sql);
            while (!$result->EOF) {
                list($id) = $res->fields;
                $result->MoveNext();
                $types[] = $id;
            }
        }
        $seqId = $dbconn->GenId($propdefTable);
        $sql = "INSERT INTO $propdefTable
                (xar_prop_id, xar_prop_name, xar_prop_label,
                 xar_prop_parent, xar_prop_filepath, xar_prop_class,
                 xar_prop_format, xar_prop_validation, xar_prop_source,
                 xar_prop_reqfiles, xar_prop_reqmodules, xar_prop_args, xar_prop_aliases)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $bindvars = array((int) $this->id,
             $this->name, $this->desc,
            $this->parent, $this->filepath, $this->class,
            $this->format, $this->validation, $this->source,
            serialize($this->reqfiles), $modName, is_array($this->args) ? serialize($this->args) : $this->args, serialize($this->aliases)

        );
         if (!in_array($this->id, $types)) {
            $res = $dbconn->Execute($sql,$bindvars);
            $types[] = $this->id;
        } else {
            $res = true;
        }

        if(!empty($this->aliases))
        {
            foreach($this->aliases as $aliasInfo)
            {
                if (!isset($aliasInfo['filepath'])) $aliasInfo['filepath'] = $this->filepath;
                if (!isset($aliasInfo['class'])) $aliasInfo['class'] = $this->class;
                if (!isset($aliasInfo['format'])) $aliasInfo['format'] = $this->format;
                if (!isset($aliasInfo['reqmodules'])) $aliasInfo['reqmodules'] = $this->reqmodules;
                $res = $aliasInfo->Register();
            }
        }
        return $res;
    }

    static function Retrieve()
    {
         if(xarCoreCache::isCached('DynamicData','PropertyTypes')) {
            return xarCoreCache::getCached('DynamicData','PropertyTypes');
        }
        $dbconn = xarDB::$dbconn;
        xarModDBInfoLoad('dynamicdata','dynamicdata');
        $tables = xarDBGetTables();
        $dynamicproptypes = $tables['dynamic_properties_def'];
        $modtable = $tables['modules'];
        // Sort by required module(s) and then by name
        $query = "SELECT
                    xar_prop_id
                    , xar_prop_name
                    , xar_prop_label
                    , xar_prop_parent
                    , xar_prop_filepath
                    , xar_prop_class
                    , xar_prop_format
                    , xar_prop_validation
                    , xar_prop_source
                    , xar_prop_reqfiles
                    , xar_prop_reqmodules
                    , xar_prop_args
                    , xar_prop_aliases

                  FROM $dynamicproptypes
                  ORDER BY xar_prop_reqmodules, xar_prop_id";

        $result = $dbconn->Execute($query);
        $proptypes = array();
        if( $result->EOF)
        {
            $property_types = xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush'=>false));

        } else {
            $property_types = array();
            while (!$result->EOF)
            {
                list($id,$name,$label,$parent,$filepath,$class,$format,$validation,$source,$reqfiles,$reqmodules,$args,$aliases) = $result->fields;

                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['format']         = $format;
                $property['filepath']       = $filepath;
                $property['validation']     = $validation;
                $property['source']         = $source;
                $property['dependancies']   = $reqfiles;
                $property['requiresmodule'] = $reqmodules;
                $property['args']           = $args;
                $property['propertyClass']  = $class;
                $property['aliases']        = $aliases;

                $property_types[$id] = $property;

                $proptypes[$id] = $property;
                $result->MoveNext();
            }
        }
        $result->close();
         xarCoreCache::setCached('DynamicData','PropertyTypes',$proptypes);
        return $proptypes;
    }
}
?>
