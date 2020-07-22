<?php
/**
 * Metaclass for Dynamic Objects
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
if (!class_exists('Dynamic_Property_Master') || !class_exists('Dynamic_Property')) sys::import('modules.dynamicdata.class.properties');
if (!class_exists('Dynamic_DataStore_Master') || !class_exists('Dynamic_DataStore')) sys::import('modules.dynamicdata.class.datastores');
if (!class_exists('Dynamic_Data_ObjectDescriptor') ) sys::import('modules.dynamicdata.class.descriptor');
/**
 * Metaclass for Dynamic Objects
 */
class Dynamic_Object_Master extends xarObject
{
    public $objectid = null;
    public $name = null;
    public $label = null;

    public $moduleid = null;
    public $itemtype = null;

    public $urlparam    = 'itemid';
    public $maxid       = 0;
    public $configuration;
    public $config      = 'a:0:{}';
    public $isalias     = 0;
    public $join        = '';
    public $table       = '';

    public $class       = 'DataObject'; // the class name of this DD object
    public $filepath    = 'auto';       // the path to the class of this DD object (can be empty or 'auto' for DataObject)
    public $properties  = array();      // list of properties for the DD object
    public $datastores  = array();      // similarly the list of datastores (arguably in the wrong place here)
    public $fieldlist   = array();      // array of properties to be displayed
    public $fieldorder  = array();      // displayorder for the properties
    public $fieldprefix = '';           // prefix to use in field names etc.
    public $status      = 65;           // inital status of property (active)
    public $namedattributes   = 0;      // name display for id and name attributes instead of dd_XX. On (1) or off (0). This is currently  experimental
    public $layout = 'default';         // optional layout inside the templates
    public $template = '';              // optional sub-template, e.g. user-objectview-[template].xd (defaults to the object name)
    public $tplmodule = 'dynamicdata';  // optional module where the object templates reside (defaults to 'dynamicdata')
    public $urlmodule = '';             // optional module for use in xarModURL() (defaults to the object module)

    public $viewfunc = 'view';          // optional view function for use in xarModURL() (defaults to 'view')
    public $primary = null;             // primary key is item id
    public $secondary = null;           // secondary key could be item type (e.g. for articles)
    public $filter;                     // set this true to automatically filter by current itemtype on secondary key

    public $upload = false;             // flag indicating if this object has some property that provides file upload
    public $visibility = 'public';
   /**
     * Default constructor to set the object variables, retrieve the dynamic properties
     * and get the corresponding data stores for those properties
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve, or
     * @param $args['table'] database table to turn into an object
     * @param $args['catid'] categories we're selecting in (if hooked)
     *
     * @param $args['fieldlist'] optional list of properties to use, or
     * @param $args['status'] optional status of the properties to use
     * @param $args['allprops'] skip disabled properties by default
     */

    function __construct($args)
    {
        $this->properties = array();

        $this->fieldlist = array();
        // fill in the default object variables
        if (!empty($args) && is_array($args) && count($args) > 0) {
            foreach ($args as $key => $val) {
                $this->$key = $val;
            }
        }

        if (!empty($this->table)) {
            $meta = xarMod::apiFunc('dynamicdata','util','getmeta',
                                  array('table' => $this->table));
            // we throw an exception here because we assume a table should always exist (for now)
            if (!isset($meta) || !isset($meta[$this->table])) {
                $msg = xarML('Invalid #(1) #(2) for dynamic object #(3)',
                             'table',$this->table,$this->table);
                 throw new BadParameterException(null,$msg);
            }
            foreach ($meta[$this->table] as $name => $propinfo) {
                $this->addProperty($propinfo);
            }
        }
        if (empty($this->moduleid)) {
            if (empty($this->objectid)) {
                $this->moduleid = xarMod::getId(xarMod::getName());
            }
        } elseif (!is_numeric($this->moduleid) && is_string($this->moduleid)) {
            $this->moduleid = xarMod::getId($this->moduleid);
        }
        if (empty($this->itemtype)) {
            $this->itemtype = 0;
        }
        if (empty($this->name)) {
            $info = Dynamic_Object_Master::getObjectInfo($args);
            if (isset($info) && count($info) > 0) {
                foreach ($info as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
            // use the object name as default template override (*-*-[template].x*)
        if (empty($this->template) && !empty($this->name)) {
            $this->template = $this->name;
        }
        if (isset($this->config) && !empty($this->config) && !is_array($this->config))
        {
            try {
                $configs   = @unserialize($this->config);
                //foreach ( $configs as $key => $value) $this->{$key} = $value;
                $this->configuration = $configs;
            } catch (Exception $e) {
                //don't do anything here
            }
        }

        // get the properties defined for this object
        if (count($this->properties) == 0 &&
            (isset($this->objectid) || (isset($this->moduleid) && isset($this->itemtype)))
           ) {

           $args = $this->toArray();
           $args['objectref'] =& $this;

           if(!isset($args['allprops']))   //FIXME is this needed??
                $args['allprops'] = null;
               Dynamic_Property_Master::getProperties($args); // we pass this object along
        }

        if (!empty($this->join)) {
            $meta = xarMod::apiFunc('dynamicdata','util','getmeta',
                                  array('table' => $this->join));
            // we throw an exception here because we assume a table should always exist (for now)
            if (!isset($meta) || !isset($meta[$this->join])) {
                $msg = xarML('Invalid #(1) #(2) for dynamic object #(3)',
                             'join',$this->join,$this->name);
                 throw new BadParameterException(null,$msg);
            }
            $count = count($this->properties);
            foreach ($meta[$this->join] as $name => $propinfo) {
                $this->addProperty($propinfo);
            }
            if (count($this->properties) > $count) {
                // put join properties in front
                $joinprops = array_splice($this->properties,$count);
                $this->properties = array_merge($joinprops,$this->properties);
            }
        }
       // create the list of fields, filtering where necessary
        $this->fieldlist = $this->getFieldList($this->fieldlist,$this->status);

        // filter on property status if necessary.
        //jojo - no not here - review
        /*
        if (isset($this->status) && count($this->fieldlist) == 0) {
            $this->fieldlist = array();
            foreach ($this->properties as $name => $property) {
                if ($property->status == $this->status) {
                    $this->fieldlist[] = $name;
                }
            }
        }
        */
        // build the list of relevant data stores where we'll get/set our data
        if (count($this->datastores) == 0 &&
            count($this->properties) > 0) {
           $this->getDataStores();
        }
    }

    function toArray(Array $args=array())
    {
        $properties = $this->getPublicProperties();
        foreach ($properties as $key => $value) if (!isset($args[$key])) $args[$key] = $value;
        //FIXME where do we need to define the modname best?
        if (!empty($args['moduleid'])) $args['modname'] = xarMod::getName($args['moduleid']); //FIXME change to systemid
        return $args;
    }
    private function getFieldList($fieldlist=array(),$status=null)
    {
        $properties = $this->properties;
        $fields = array();
        if(count($fieldlist) != 0) {
            foreach($fieldlist as $field)
            {
                // Ignore those disabled AND those that don't exist
                if(isset($properties[$field]) && ($properties[$field]->getDisplayStatus() != Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED))
                {
                    $fields[$properties[$field]->id] = $properties[$field]->name;
                }
            }
        } else {
            if ($status) {
                // we have a status: filter on it
                foreach($properties as $property)
                {
                    if($property->status && $this->status)
                    {
                        $fields[$property->id] = $property->name;
                    }
                }
            } else {
                // no status filter: return those that are not disabled
                foreach($this->properties as $property)
                {
                    if($property->getDisplayStatus() != Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED)
                    {
                        $fields[$property->id] = $property->name;
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * Get the data stores where the dynamic properties of this object are kept
     */
    function &getDataStores($reset = false)
    {
        // if we already have the datastores
        if (!$reset && isset($this->datastores) && count($this->datastores) > 0) {
            return $this->datastores;
        }

        // if we're filtering on property status and there are no properties matching this status
        if (!$reset && !empty($this->status) && count($this->fieldlist) == 0) {
            return $this->datastores;
        }
        // reset field list of datastores if necessary
        if ($reset && count($this->datastores) > 0) {
            foreach (array_keys($this->datastores) as $storename) {
                $this->datastores[$storename]->fields = array();
            }
        }
        // check the fieldlist for valid property names and for operations like COUNT, SUM etc.
        if (!empty($this->fieldlist) && count($this->fieldlist) > 0) {
            $cleanlist = array();
            foreach ($this->fieldlist as $name) {
                if (!strstr($name,'(')) {
                    if (isset($this->properties[$name])) {
                        $cleanlist[] = $name;
                    }
                } elseif (preg_match('/^(.+)\((.+)\)/',$name,$matches)) {
                    $operation = $matches[1];
                    $field = $matches[2];
                    if (isset($this->properties[$field])) {
                        $this->properties[$field]->operation = $operation;
                        $cleanlist[] = $field;
                        $this->isgrouped = 1;
                    }
                }
            }
            $this->fieldlist = $cleanlist;
        }
        foreach ($this->properties as $name => $property) {
            // skip properties we're not interested in (but always include the item id field)
            if (!empty($this->fieldlist) &&
                !in_array($name,$this->fieldlist) &&
                $property->type != 21 ||
                ($property->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED)  // or the property is disabled
                )
            {
                $this->properties[$name]->datastore = '';
                continue;
            }

            list($storename, $storetype) = $this->property2datastore($property);
            if (!isset($this->datastores[$storename])) {
                $this->addDataStore($storename, $storetype);
            }
            $this->properties[$name]->datastore = $storename;

            if (empty($this->fieldlist) || in_array($name,$this->fieldlist)) {
                // we add this to the data store fields
                $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
            } else {
                // we only pass this along as being the primary field
                $this->datastores[$storename]->setPrimary($this->properties[$name]);
            }

            // keep track of what property holds the primary key (item id)
            if (!isset($this->primary) && $property->type == 21) {
                $this->primary = $name;
            }
            // keep track of what property holds the secondary key (item type)
            if (!isset($this->secondary) && $property->type == 20 && !empty($this->filter)) {
                $this->secondary = $name;
            }
        }
        return $this->datastores;
    }

    /**
     * Find the datastore name and type corresponding to the data source of a property
     */
    function property2datastore($property)
    {
        // normal dynamic data field
        if ($property->source == 'dynamic_data') {
            $storename = '_dynamic_data_';
            $storetype = 'data';

        // data field coming from some static table : [database.]table.field
        } elseif (preg_match('/^(.+)\.(\w+)$/', $property->source, $matches)) {
            $table = $matches[1];
            $field = $matches[2];
            $storename = $table;
            $storetype = 'table';

        // data managed by a hook/utility module
        } elseif ($property->source == 'hook module') {
            $storename = '_hooks_';
            $storetype = 'hook';

        // data managed by some user function (specified in validation for now)
        } elseif ($property->source == 'user function') {
            $storename = '_functions_';
            $storetype = 'function';

        // data available in user variables
        } elseif ($property->source == 'user settings') {
            // we'll keep a separate data store per module/itemtype here for now
        // TODO: (don't) integrate user variable handling with DD
            $storename = 'uservars_'.$this->moduleid.'_'.$this->itemtype;
            $storetype = 'uservars';

        // data available in module variables
        } elseif ($property->source == 'module variables') {
            // we'll keep a separate data store per module/itemtype here for now
        // TODO: (don't) integrate module variable handling with DD
            $storename = 'modulevars_'.$this->moduleid.'_'.$this->itemtype;
            $storetype = 'modulevars';
        } elseif ($property->source == 'theme variables') {
            // we'll keep a separate data store per theme/itemtype here for now
        // TODO: (don't) integrate module variable handling with DD
            $storename = 'themevars_'.$this->moduleid.'_'.$this->itemtype;
            $storetype = 'themevars';

        // no data storage
        } elseif ($property->source == 'dummy') {
            $storename = '_dummy_';
            $storetype = 'dummy';

        // TODO: extend with LDAP, file, ...
        } else {
            $storename = '_todo_';
            $storetype = 'todo';
        }

        return array($storename, $storetype);
    }

    /**
     * Add a data store for this object
     *
     * @param $name the name for the data store
     * @param $type the type of data store
     */
    function addDataStore($name = '_dynamic_data_', $type='data')
    {
        // get a new data store
        $datastore = Dynamic_DataStore_Master::getDataStore($name, $type);

        // add it to the list of data stores
        $this->datastores[$datastore->name] = $datastore;

        // for dynamic object lists, put a reference to the $itemids array in the data store
        if (method_exists($this, 'getItems')) {
            $this->datastores[$datastore->name]->_itemids =& $this->itemids;
        }
    }

    /**
     * Get the selected dynamic properties for this object
     */
    function &getProperties(Array $args = array())
    {

        if (empty($args['fieldlist']))
        {
            if(count($this->fieldlist) > 0) {
               $args['fieldlist'] = $this->fieldlist;
            } else {
                return $this->properties;
            }
        } else {
            //  read in  a list or an array
            if (!is_array($args['fieldlist'])) $args['fieldlist'] = explode(',',$args['fieldlist']);
           $args['fieldlist'] = $args['fieldlist'];
        }
        $properties = array(); //initialise our array
        // return only the properties we're interested in (might be none)
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            $properties = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $properties[$name] = & $this->properties[$name];
                    if (isset($args['namedattributes'])) $this->properties[$name]->namedattributes = $args['namedattributes'];
                }
            }
        } else {
            $properties = & $this->properties;
        }

        return $properties;
    }

    /**
     * Add a property for this object
     *
     * @param $args['name'] the name for the dynamic property (required)
     * @param $args['type'] the type of dynamic property (required)
     * @param $args['label'] the label for the dynamic property
     * @param $args['id'] the id for the dynamic property
     * ...
     */
    function addProperty($args)
    {
        // TODO: find some way to have unique IDs across all objects if necessary
        if (!isset($args['id'])) {
            $args['id'] = count($this->properties) + 1;
        }
        Dynamic_Property_Master::addProperty($args,$this);
    }

    /**
     * Class method to retrieve information about all Dynamic Objects
     *
     * @return array of object definitions
     */
    function &getObjects(Array $args=array())
    {
        $nullreturn = NULL;
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;

        $dynamicobjects = $xartable['dynamic_objects'];
        $bindvars = array();
        $query = "SELECT xar_object_id,
                         xar_object_name,
                         xar_object_label,
                         xar_object_moduleid,
                         xar_object_itemtype,
                         xar_object_urlparam,
                         xar_object_maxid,
                         xar_object_config,
                         xar_object_isalias
                  FROM $dynamicobjects ";
        if (isset($args['moduleid']) && !empty($args['moduleid'])) {
                $query .= "WHERE xar_object_moduleid = ?";
                $bindvars[]=$args['moduleid'];
        }
        $result = $dbconn->Execute($query,$bindvars);

        if (!$result) return $nullreturn;

        $objects = array();
        while (!$result->EOF) {
            $info = array();
            list($info['objectid'],
                 $info['name'],
                 $info['label'],
                 $info['moduleid'],
                 $info['itemtype'],
                 $info['urlparam'],
                 $info['maxid'],
                 $info['config'],
                 $info['isalias']) = $result->fields;
             $objects[$info['objectid']] = $info;
             $result->MoveNext();
        }
        $result->Close();
        return $objects;
    }

    /**
     * Class method to retrieve information about a Dynamic Object
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['name'] name of the object you're looking for, or
     * @param $args['moduleid'] module id of the object you're looking for +
     * @param $args['itemtype'] item type of the object you're looking for
     * @return array containing the name => value pairs for the object
     */
    static function getObjectInfo(Array $args=array())
    {
        if (!empty($args['table'])) {
            $info = array();
            $info['objectid'] = 0;
            $info['name'] = $args['table'];
            $info['label'] = xarML('Table #(1)',$args['table']);
            $info['moduleid'] = 182;
            $info['itemtype'] = 0;
            $info['filepath'] = 'auto';
            $info['urlparam'] = 'itemid';
            $info['maxid'] = 0;
            $info['config'] = '';
            $info['isalias'] = 0;
            return $info;
        }

         $cacheKey = 'DynamicData.ObjectInfo';
        if (!empty($args['name'])) {
            $infoid = $args['name'];
        } elseif (!empty($args['objectid'])) {
            $infoid = (int)$args['objectid'];
        } else {
            if (empty($args['moduleid'])) {
                // try to get the current module from elsewhere
                $args = Dynamic_Data_ObjectDescriptor::getModID($args);
            }
            if (empty($args['itemtype'])) {
                // set default itemtype
                $args['itemtype'] = 0;
            }
            $infoid = $args['moduleid'].':'.$args['itemtype'];
        }
        if(xarCoreCache::isCached($cacheKey,$infoid)) {
            return xarCoreCache::getCached($cacheKey,$infoid);
        }

        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;

        $dynamicobjects = $xartable['dynamic_objects'];

        $bindvars = array();
        $query = "SELECT xar_object_id,
                         xar_object_name,
                         xar_object_label,
                         xar_object_moduleid,
                         xar_object_itemtype,
                         xar_object_urlparam,
                         xar_object_maxid,
                         xar_object_config,
                         xar_object_isalias
                  FROM $dynamicobjects ";
        if (!empty($args['objectid'])) {
            $query .= " WHERE xar_object_id = ? ";
            $bindvars[] = (int) $args['objectid'];
        } elseif (!empty($args['name'])) {
            $query .= " WHERE xar_object_name = ? ";
            $bindvars[] = (string) $args['name'];
        } else {
            if (empty($args['moduleid'])) {
                $args['moduleid'] = xarMod::getId(xarMod::getName());
            }
            if (empty($args['itemtype'])) {
                $args['itemtype'] = 0;
            }
            $query .= " WHERE xar_object_moduleid = ?
                          AND xar_object_itemtype = ? ";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result || $result->EOF) return;

        $info = array();
        list($info['objectid'],
             $info['name'],
             $info['label'],
             $info['moduleid'],
             $info['itemtype'],
             $info['urlparam'],
             $info['maxid'],
             $info['config'],
             $info['isalias']) = $result->fields;

        $result->Close();
        if (!empty($args['join'])) {
            $info['label'] .= ' + ' . $args['join'];
        }
        xarCoreCache::setCached($cacheKey,$infoid,$info);
        return $info;
    }

    /**
     * Class method to retrieve a particular object definition, with sub-classing
     * (= the same as creating a new Dynamic Object with itemid = null)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object)
     * @return object the requested object definition
     */
    static function getObject($args)
    {

        if (!isset($args['itemid'])) $args['itemid'] = null;
        $classname = 'Dynamic_Object';
        if (!empty($args['classname']) && class_exists($args['classname'])) {
            $classname = $args['classname'];
/*
        // TODO: automatic sub-classing per module (and itemtype) ?
        } elseif (!empty($args['moduleid'])) {
            $modInfo = xarMod::getInfo($args['moduleid']);
            $modName = strtolower($modInfo['name']);
            if ($modName != 'dynamicdata') {
                $classname = "{$modName}_Dynamic_Object";
                if (!class_exists($classname))
                    $classname = 'Dynamic_Object';
            }
*/
        }
        // here we can use our own classes to retrieve this
        $object = new $classname($args);
        return $object;
    }

    /**
     * Class method to retrieve a particular object list definition, with sub-classing
     * (= the same as creating a new Dynamic Object List)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object[_List])
     * @return object the requested object definition
     */
    static function getObjectList(Array $args=array())
    {
         // Complete the info if this is a known object
        $info = self::getObjectInfo($args);
       if ($info != null) $args = array_merge($args,$info);
         $classname = 'Dynamic_Object_List';
        if (!empty($args['classname'])) {
            if (class_exists($args['classname'] . '_List')) {
                // this is a generic classname for the object, list and interface
                $classname = $args['classname'] . '_List';
            } elseif (class_exists($args['classname'])) {
                // this is a specific classname for the list
                $classname = $args['classname'];
            }
/*
        // TODO: automatic sub-classing per module (and itemtype) ?
        } elseif (!empty($args['moduleid'])) {
            $modInfo = xarMod::getInfo($args['moduleid']);
            $modName = strtolower($modInfo['name']);
            if ($modName != 'dynamicdata') {
                $classname = "{$modName}_Dynamic_Object_List";
                if (!class_exists($classname))
                    $classname = 'Dynamic_Object_List';
            }
*/
        }
        // here we can use our own classes to retrieve this
        $object = new $classname($args);
        return $object;
    }

    /**
     * Class method to retrieve a particular object interface definition, with sub-classing
     * (= the same as creating a new Dynamic Object Interface)
     *
     * @param $args['objectid'] id of the object you're looking for, or
     * @param $args['moduleid'] module id of the object to retrieve +
     * @param $args['itemtype'] item type of the object to retrieve
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object[_Interface])
     * @return object the requested object definition
     */
    function getObjectInterface($args)
    {
         sys::import('modules.dynamicdata.class.interface');

        $classname = 'Dynamic_Object_Interface';
        if (!empty($args['classname'])) {
            if (class_exists($args['classname'] . '_Interface')) {
                // this is a generic classname for the object, list and interface
                $classname = $args['classname'] . '_Interface';
            } elseif (class_exists($args['classname'])) {
                // this is a specific classname for the interface
                $classname = $args['classname'];
            }
/*
        // TODO: automatic sub-classing per module (and itemtype) ?
        } elseif (!empty($args['moduleid'])) {
            $modInfo = xarMod::getInfo($args['moduleid']);
            $modName = strtolower($modInfo['name']);
            if ($modName != 'dynamicdata') {
                $classname = "{$modName}_Dynamic_Object_Interface";
                if (!class_exists($classname))
                    $classname = 'Dynamic_Object_Interface';
            }
*/
        }
        // here we can use our own classes to retrieve this
        $object = new $classname($args);
        return $object;
    }

    /**
     * Class method to create a new type of Dynamic Object
     *
     * @param $args['objectid'] id of the object you want to create (optional)
     * @param $args['name'] name of the object to create
     * @param $args['label'] label of the object to create
     * @param $args['moduleid'] module id of the object to create
     * @param $args['itemtype'] item type of the object to create
     * @param $args['urlparam'] URL parameter to use for the object items (itemid, exid, aid, ...)
     * @param $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
     * @param $args['config'] some configuration for the object (free to define and use)
     * @param $args['isalias'] flag to indicate whether the object name is used as alias for short URLs
     * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object)
     * @return int the object id of the created item
     */
    static function createObject($args)
    {
        if (!isset($args['moduleid'])) {
            $args['moduleid'] = null;
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = null;
        }
        if (!isset($args['classname'])) {
            $args['classname'] = null;
        }
        // create the Dynamic Objects item corresponding to this object
        $object = Dynamic_Object_Master::getObject(array('objectid' => 1, // the Dynamic Objects = 1
                                                          'moduleid' => $args['moduleid'],
                                                          'itemtype' => $args['itemtype'],
                                                          'classname' => $args['classname']));
        $objectid = $object->createItem($args);
        unset($object);
        return $objectid;
    }

    static function updateObject($args)
    {
        if (empty($args['objectid'])) {
            return;
        }
        if (!isset($args['moduleid'])) {
            $args['moduleid'] = null;
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = null;
        }
        if (!isset($args['classname'])) {
            $args['classname'] = null;
        }
        // update the Dynamic Objects item corresponding to this object
        $object = Dynamic_Object_Master::getObject(array('objectid' => 1, // the Dynamic Objects = 1
                                                          'moduleid' => $args['moduleid'],
                                                          'itemtype' => $args['itemtype'],
                                                          'classname' => $args['classname']));
        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if (empty($itemid)) return;
        $itemid = $object->updateItem($args);
        unset($object);
        return $itemid;
    }

    static function deleteObject($args)
    {
        if (empty($args['objectid'])) {
            return;
        }
        if (!isset($args['moduleid'])) {
            $args['moduleid'] = null;
        }
        if (!isset($args['itemtype'])) {
            $args['itemtype'] = null;
        }
        if (!isset($args['classname'])) {
            $args['classname'] = null;
        }
        // get the Dynamic Objects item corresponding to this object
        $object = Dynamic_Object_Master::getObject(array('objectid' => 1, // the Dynamic Objects = 1
                                                          'moduleid' => $args['moduleid'],
                                                          'itemtype' => $args['itemtype'],
                                                          'classname' => $args['classname']));
        if (empty($object)) return;

        $itemid = $object->getItem(array('itemid' => $args['objectid']));
        if (empty($itemid)) return;

        // get an object list for the object itself, so we can delete its items
        $mylist = Dynamic_Object_Master::getObjectList(array('objectid' => $args['objectid'],
                                                              'moduleid' => $args['moduleid'],
                                                              'itemtype' => $args['itemtype'],
                                                              'classname' => $args['classname']));
        if (empty($mylist)) return;

        // TODO: delete all the (dynamic ?) data for this object

        // delete all the properties for this object
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = Dynamic_Property_Master::deleteProperty(array('itemid' => $propid));
        }

        // delete the Dynamic Objects item corresponding to this object
        $result =  $object->deleteItem();
        unset($object);
        return $result;
    }

    /**
     * Join another database table to this object (unfinished)
     * The difference with the 'join' argument above is that we don't create a new datastore for it here,
     * and the join is handled directly in the original datastore, i.e. more efficient querying...
     *
     * @param $args['table'] the table to join with
     * @param $args['key'] the join key for this table
     * @param $args['fields'] the fields you want from this table
     * @param $args['where'] optional where clauses for those table fields
     * @param $args['andor'] optional combination of those clauses with the ones from the object
     * @param $args['sort'] optional sort order in that table (TODO)
     * ...
     */
    function joinTable($args)
    {
        if (empty($args['table'])) return;
        $meta = xarMod::apiFunc('dynamicdata','util','getmeta',
                              array('table' => $args['table']));
        // we throw an exception here because we assume a table should always exist (for now)
        if (!isset($meta) || !isset($meta[$args['table']])) {
            $msg = xarML('Invalid #(1) #(2) for dynamic object #(3)',
                         'join',$args['table'],$this->name);
             throw new BadParameterException(null,$msg);
        }
        $count = count($this->properties);
        foreach ($meta[$args['table']] as $name => $propinfo) {
            $this->addProperty($propinfo);
        }
        $table = $args['table'];
        $key = null;
        if (!empty($args['key']) && isset($this->properties[$args['key']])) {
            $key = $this->properties[$args['key']]->source;
        }
        $fields = array();
        if (!empty($args['fields'])) {
            foreach ($args['fields'] as $field) {
                if (isset($this->properties[$field])) {
                    $fields[$field] =& $this->properties[$field];
                    if (count($this->fieldlist) > 0 && !in_array($field,$this->fieldlist)) {
                        $this->fieldlist[] = $field;
                    }
                }
            }
        }
        $where = array();
        if (!empty($args['where'])) {
            // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
            $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
            $replaceLogic   = array( ' = ', ' != ',  ' < ',  ' > ',  ' = ', ' != ', ' <= ', ' >= ');

            $args['where'] = str_replace($findLogic, $replaceLogic, $args['where']);

            $parts = preg_split('/\s+(and|or)\s+/',$args['where'],-1,PREG_SPLIT_DELIM_CAPTURE);
            $join = '';
            foreach ($parts as $part) {
                if ($part == 'and' || $part == 'or') {
                    $join = $part;
                    continue;
                }
                $pieces = preg_split('/\s+/',$part);
                $name = array_shift($pieces);
                // sanity check on SQL
                if (count($pieces) < 2) {
                    $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                                 'query ' . $args['where'], 'Dynamic_Object_Master', 'joinTable', 'DynamicData');
                     throw new BadParameterException(null,$msg);
                }
                // for many-to-1 relationships where you specify the foreign key in the original table here
                // (e.g. properties joined to xar_dynamic_objects -> where object_id eq objectid)
                if (!empty($pieces[1]) && is_string($pieces[1]) && isset($this->properties[$pieces[1]])) {
                    $pieces[1] = $this->properties[$pieces[1]]->source;
                }
                if (isset($this->properties[$name])) {
                    $where[] = array('property' => &$this->properties[$name],
                                     'clause' => join(' ',$pieces),
                                     'join' => $join);
                }
            }
        }
        if (!empty($args['andor'])) {
            $andor = $args['andor'];
        } else {
            $andor = 'and';
        }
        foreach (array_keys($this->datastores) as $name) {
             $this->datastores[$name]->addJoin($table, $key, $fields, $where, $andor);
        }
    }

    function getObjectID(Array $args=array())
    {
        $dbconn = xarDB::$dbconn;
        $xartable = xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT xar_object_id,
                         xar_object_name,
                         xar_object_moduleid,
                         xar_object_itemtype
                  FROM $dynamicobjects ";

        $bindvars = array();
        if (isset($args['name'])) {
            $query .= " WHERE xar_object_name = ? ";
            $bindvars[] = $args['name'];
        } elseif (!empty($args['objectid'])) {
            $query .= " WHERE xar_object_id = ? ";
            $bindvars[] = (int) $args['objectid'];
        }
        /*else {

            $query .= " WHERE xar_object_moduleid = ?
                          AND xar_object_itemtype = ? ";
            $bindvars[] = (int) $args['moduleid'];
            $bindvars[] = (int) $args['itemtype'];
        }*/

        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;
        list($objectid, $name,$modid,$itemtype) = $result->fields;
            $args = array(
                        'moduleid' => isset($modid)?$modid: null,
                        'itemtype' => isset($itemtype)?$itemtype: null,
                        'objectid' => isset($objectid)?$objectid: null,
                        'name' => isset($name)?$name: null,
                        );
        $result->close();

        return $args;
    }

}

/**
 * Dynamic Object
 *
 * @subpackage dynamicdata module
 */
class Dynamic_Object extends Dynamic_Object_Master
{
    public $itemid = 0;
    public $missingfields  = array();      // reference to fields not found by checkInput
    public $invalid_object = '';           // object error message for forms

    /**
     * Inherits from Dynamic_Object_Master and sets the requested item id
     *
     * @param $args['itemid'] item id of the object to get
     */
    function __construct($args)
    {
        // get the object type information from our parent class
        parent::__construct($args);
        // set the specific item id (or 0)
        if (isset($args['itemid'])) {
            $this->itemid = $args['itemid'];
        }
        //set the configuration

        if (!empty($args['config']) && !is_array($args['config']))
        {
            try {
                $configs = @unserialize($args['config']);
            } catch (Exception $e) {
               $configs = array();
            }
            foreach ($configs as $key => $value) {
                 $this->{$key} = $value;
                 $this->configuration['fields'] = $configs;
            }
        }

        foreach ($this->properties as $property) {
            $this->configuration['fields'][$property->name] = array('type' => &$property->type, 'value' => &$property->value);
        }
        // see if we can access this object, at least in overview
        if(!xarSecurityCheck('ViewDynamicDataItems',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$this->itemid)) return xarResponseForbidden();
        // don't retrieve the item here yet !
        //$this->getItem();
    }

    /**
     * Retrieve the values for this item
     */
    public function getItem(Array $args = array())
    {
        if (!empty($args['itemid'])) {
            if ($args['itemid'] != $this->itemid) {
                // initialise the properties again
                foreach (array_keys($this->properties) as $name) {
                    $this->properties[$name]->value = $this->properties[$name]->default;
                    $this->configuration['fields'][$name] = array('type' => $this->properties[$name]->type, 'value' =>$this->properties[$name]->value);
                }
            }
            $this->itemid = $args['itemid'];
        }
        if (empty($this->itemid)) {
            $msg = xarML('Invalid item id in method #(1)() for dynamic object [#(2)] #(3)',
                         'getItem',$this->objectid,$this->name);
             throw new BadParameterException(null,$msg);
        }
        if (!empty($this->primary) && !empty($this->properties[$this->primary])) {
            $primarystore = $this->properties[$this->primary]->datastore;
        }
        $modinfo = xarMod::getInfo($this->moduleid);
        foreach ($this->datastores as $name => $datastore) {
            $itemid = $datastore->getItem(array('modid'    => $this->moduleid,
                                                'itemtype' => $this->itemtype,
                                                'itemid'   => $this->itemid,
                                                'modname'  => $modinfo['name']));
            // only worry about finding something in primary datastore (if any)
            if (empty($itemid) && !empty($primarystore) && $primarystore == $name) return;
        }
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if (!empty($args['preview'])) {
            $this->checkInput();
        }

        return $this->itemid;
    }
    //from 2x
    public function getInvalids(Array $args = array())
    {
        if (!empty($args['fields'])) {
            $fields = $args['fields'];
        } else {
            $fields = !empty($this->fieldlist) ? $this->fieldlist : array_keys($this->properties);
        }
        $invalids = array();
        foreach($fields as $name) {
            if (!empty($this->properties[$name]->invalid))
                $invalids[$name] = $this->properties[$name]->invalid;
        }
        return $invalids;
    }
    //from 2x
    public function clearInvalids()
    {
        if (!empty($args['fields'])) {
            $fields = $args['fields'];
        } else {
            $fields = !empty($this->fieldlist) ? $this->fieldlist : array_keys($this->properties);
        }
        foreach($fields as $name) {
            $this->properties[$name]->invalid = '';
        }
        return true;
    }
    /**
     * Check the different input values for this item
     */
    public function checkInput(Array $args = array())
    {
        if (!empty($args['itemid']) && $args['itemid'] != $this->itemid) {
            $this->itemid = $args['itemid'];
            $this->getItem($args);
        }

        // Allow 0 as a fieldprefix
        if(empty($args['fieldprefix']) || (isset($args['fieldprefix']) && $args['fieldprefix'] !== '0')) {
            $args['fieldprefix'] = $this->fieldprefix;
        } else {
            $this->fieldprefix = $args['fieldprefix'];
        }
        $isvalid = true;
        if (!empty($args['fields'])) {
            $fields = $args['fields'];
        } else {
            $fields = !empty($this->fieldlist) ? $this->fieldlist : array_keys($this->properties);
        }
        $this->missingfields = array();
        $this->invalid_object = '';

        foreach ( $fields as $name) {
            // Ignore disabled or ignored properties
            if(($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED)
            || ($this->properties[$name]->getDisplayStatus() ==Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED))
                continue;

            $this->properties[$name]->objectref =& $this;

            // We need to check both the given name and the dd_ name
            // checking for any transitory name given a property via $args needs to be done at the property level
            $ddname = 'dd_' . $this->properties[$name]->id;
            /*if (!empty($args['fieldprefix']) || $args['fieldprefix'] === '0') {
                $name1 = $args['fieldprefix'] . "_" .$name;
                $name2 = $args['fieldprefix'] . "_" .$ddname;
            } else {
                $name1 = $name;
                $name2 = $ddname;
            }*/

            if(isset($args[$name])) {

                // Name based check
                $passed = $this->properties[$name]->checkInput($name,$args[$name]);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($ddname,$args[$name]);
                }
            } elseif(isset($args[$ddname])) {
                // No name, check based on field
                $passed = $this->properties[$name]->checkInput($ddname,$args[$ddname]);
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($name,$args[$ddname]);
                }
            } else {

                // Check without values
                $passed = $this->properties[$name]->checkInput();
                if ($passed === null) {
                    array_pop($this->missingfields);
                    $passed = $this->properties[$name]->checkInput($ddname);
                }
            }
            if (($passed === null) || ($passed === false)) $isvalid = false;
        }
        if (!$isvalid) {

            // Set a default error message for the property, if not already set.
           if (!empty($this->missingfields)) {
                $fieldlist = implode(', ',$this->missingfields);
                $namelabel = isset($this->label)?$this->label: $this->name;
                $this->invalid_object = xarML('Please check #(1) for problems in the fields: #(2)',$namelabel,$fieldlist);
                //throw new VariableNotFoundException(array($this->name,implode(', ',$this->missingfields)),'The following fields were not found: #(1): [#(2)]');
            }
        }
        return $isvalid;

    }

    /**
     * Show an input form for this item
     */
    public function showForm(Array $args = array())
    {
        $args = $this->toArray($args);

        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if (!empty($args['preview'])) {
            $this->checkInput();
        }
        //initialize the property array
        $args['properties'] = array();

        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)
                    ) continue;
                    $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
            foreach ($this->properties as $property) {
                if(($property->getDisplayStatus() ==  Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED)
                || ($property->getDisplayStatus()  ==  Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)
                ) continue;
                    $args['properties'][$property->name] = $property;
            }

        }
        // Order the fields if this is an extended object
        if (!empty($this->fieldorder)) {
            $tempprops = array();
            foreach ($this->fieldorder as $field)
                if (isset($args['properties'][$field]))
                    $tempprops[$field] = $args['properties'][$field];
            $args['properties'] = $tempprops;
        }
        // pass some extra template variables for use in BL tags, API calls etc.
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['moduleid'] = $this->moduleid;
        $modinfo = xarMod::getInfo($this->moduleid);
        $args['modname'] = $modinfo['name'];
        if (empty($this->itemtype)) {
            $args['itemtype'] = null; // don't add to URL
        } else {
            $args['itemtype'] = $this->itemtype;
        }
        $args['itemid'] = $this->itemid;
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        } else {
            $args['isprimary'] = false;
        }
        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }
        $args['objecterror'] = $this->invalid_object;
        return xarTplObject($args['tplmodule'],$args['template'],'showform',$args);
    }

    /**
     * Show an output display for this item
     */
    function showDisplay(Array $args = array())
    {
        $args = $this->toArray($args);
        /*
        if (empty($args['layout'])) {
            $args['layout'] = $this->layout;
        }
        if (empty($args['template'])) {
            $args['template'] = $this->template;
        }
        if (empty($args['tplmodule'])) {
            $args['tplmodule'] = $this->tplmodule;
        }
        if (empty($args['viewfunc'])) {
            $args['viewfunc'] = $this->viewfunc;
        }
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        */
        // for use in DD tags : preview="yes" - don't use this if you already check the input in the code
        if (!empty($args['preview'])) {
            $this->checkInput();
        }
       if (!empty($args['fieldlist']) && !is_array($args['fieldlist'])) {
            $args['fieldlist'] = explode(',',$args['fieldlist']);
            if (!is_array($args['fieldlist'])) throw new Exception('Field list is not in correct format');
        }

        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            $args['properties'] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_INPUTONLY)) continue;
                    $thisprop = $this->properties[$name];
                    $args['properties'][$name] = & $this->properties[$name];
                }
            }
        } else {
           /*
            foreach ($this->properties as $property) {
                if ($property->status != 3)
                    $args['properties'][$property->name] = $property;
            }
            */

            $args['properties'] =& $this->properties;
            // TODO: this is exactly the same as in the display function, consolidate it.
            $totransform = array(); $totransform['transform'] = array();
            foreach($this->properties as $pname => $pobj) {
                if(($pobj->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED)
                || ($pobj->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                || ($pobj->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN)
                || ($pobj->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_INPUTONLY)) continue;
                // *never* transform an ID
                // TODO: there is probably lots more to skip here.
                if($pobj->type == '21') continue;
                $totransform['transform'][] = $pname;
                $totransform[$pname] = $pobj->value;
            }

            // CHECKME: is $this->tplmodule safe here?
            $transformed = xarMod::callHooks(
                'item','transform',$this->itemid,
                $totransform, $this->tplmodule,$this->itemtype
            );

            foreach($this->properties as $property) {
                if(
                    ($property->getDisplayStatus() != Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED) &&
                    ($property->getDisplayStatus() != Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY) &&
                    ($property->getDisplayStatus() != Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN) &&
                    ($property>getDisplayStatus() != Dynamic_Property_Master::DD_DISPLAYSTATE_INPUTONLY) &&
                    ($property->type != 21) &&
                    isset($transformed[$property->name])
                )
                {
                    // sigh, 5 letters, but so many hours to discover them
                    // anyways, clone the property, so we can safely change it, PHP 5 specific!!
                    $args['properties'][$property->name] = clone $property;
                    $args['properties'][$property->name]->value = $transformed[$property->name];
                }
            }

            // Order the fields if this is an extended object
            if (!empty($this->fieldorder)) {
                $tempprops = array();
                foreach ($this->fieldorder as $field)
                    if (isset($args['properties'][$field]))
                        $tempprops[$field] = $args['properties'][$field];
                $args['properties'] = $tempprops;
            }

        }


        // pass some extra template variables for use in BL tags, API calls etc.
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['moduleid'] = $this->moduleid;
        $modinfo = xarMod::getInfo($this->moduleid);
        $args['modname'] = $modinfo['name'];
        if (empty($this->itemtype)) {
            $args['itemtype'] = null; // don't add to URL
        } else {
            $args['itemtype'] = $this->itemtype;
        }
        $args['itemid'] = $this->itemid;
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        } else {
            $args['isprimary'] = false;
        }
        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }
         if (empty($args['template']) && isset($args['objectname'])) {
            $args['template'] = $args['objectname'];
        }
        return xarTplObject($args['tplmodule'],$args['template'],'showdisplay',$args);
    }

    /**
     * Get the names and values of
     */
    public function getFieldValues(Array $args = array())
    {

        $fields = array();
        if (empty($args['fieldlist'])) {

            if (count($this->fieldlist) > 0) {
                $fieldlist = $this->fieldlist;
            } else {
                $fieldlist = array_keys($this->properties);
            }
        }else{
            $fieldlist = $args['fieldlist'];
        }

        $fields = array();
        foreach ($fieldlist as $name) {
            $property = $this->properties[$name];
            if(xarSecurityCheck('ReadDynamicDataField',0,'Field',$property->name.':'.$property->type.':'.$property->id)) {
                $fields[$name] = $property->value;
            }
        }

        return $fields;
    }


    /**
     * Get the labels and values to include in some output display for this item
     */
    function getDisplayValues(Array $args = array())
    {
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        $displayvalues = array();
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    $displayvalues[$label] = $this->properties[$name]->showOutput();
                }
            }
        } else {
            foreach (array_keys($this->properties) as $name) {
                $label = xarVarPrepForDisplay($this->properties[$name]->label);
                $displayvalues[$label] = $this->properties[$name]->showOutput();
            }
        }
        return $displayvalues;
    }

     /**
     * Get and set for field prefixes
     * jojo - added from 2x - not used here atm
     */
    public function getFieldPrefix()
    {
        return $this->fieldprefix;
    }
    public function setFieldPrefix($prefix)
    {
        $this->fieldprefix = $prefix;
        foreach (array_keys($this->properties) as $property)
            $this->properties[$property]->_fieldprefix = $prefix;
        return true;
    }
    function createItem(Array $args = array())
    {
        if (count($args) > 0) {
            if (isset($args['itemid'])) {
                $this->itemid = $args['itemid'];
            }
            foreach ($args as $name => $value) {
                if (isset($this->properties[$name])) {
                    $this->properties[$name]->setValue($value);
                }
            }
        }
        $modinfo = xarMod::getInfo($this->moduleid);

        // special case when we try to create a new object handled by dynamicdata
        if ($this->objectid == 1 &&
            $this->properties['moduleid']->value == xarMod::getId('dynamicdata'))
            //&& $this->properties['itemtype']->value < 2)
        {
            $this->properties['itemtype']->setValue($this->getNextItemtype($args));
        }

        // check that we have a valid item id, or that we can create one if it's set to 0
        if (empty($this->itemid)) {
            // no primary key identified for this object, so we're stuck
            if (!isset($this->primary)) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                             'primary key', 'Dynamic_Object', 'createItem', 'DynamicData');
                 throw new BadParameterException(null,$msg);

            } else {
                $value = $this->properties[$this->primary]->getValue();

                // we already have an itemid value in the properties
                if (!empty($value)) {
                    $this->itemid = $value;

                // we'll let the primary datastore create an itemid for us
                } elseif (!empty($this->properties[$this->primary]->datastore)) {
                    $primarystore = $this->properties[$this->primary]->datastore;
                    // add the primary to the data store fields if necessary
                    if (!empty($this->fieldlist) && !in_array($this->primary,$this->fieldlist)) {
                        $this->datastores[$primarystore]->addField($this->properties[$this->primary]); // use reference to original property
                    }
                    $this->itemid = $this->datastores[$primarystore]->createItem(array('objectid' => $this->objectid,
                                                                                       'modid'    => $this->moduleid,
                                                                                       'itemtype' => $this->itemtype,
                                                                                       'itemid'   => $this->itemid,
                                                                                       'modname'  => $modinfo['name']));

                } else {
                    $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                                 'primary key datastore', 'Dynamic Object', 'createItem', 'DynamicData');
                     throw new BadParameterException(null,$msg);
                }
            }
        }
        if (empty($this->itemid)) return;

    // TODO: this won't work for objects with several static tables !
        // now let's try to create items in the other data stores
        foreach (array_keys($this->datastores) as $store) {
            // skip the primary store
            if (isset($primarystore) && $store == $primarystore) {
                continue;
            }
            $itemid = $this->datastores[$store]->createItem(array('objectid' => $this->objectid,
                                                                  'modid'    => $this->moduleid,
                                                                  'itemtype' => $this->itemtype,
                                                                  'itemid'   => $this->itemid,
                                                                  'modname'  => $modinfo['name']));
            if (empty($itemid)) return;
        }

        // call create hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        /* Move to api func
        if (!empty($this->primary) && ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')) {
            $item = array();
            foreach (array_keys($this->properties) as $name) {
                $item[$name] = $this->properties[$name]->value;
            }
            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarMod::callHooks('item', 'create', $this->itemid, $item, $modinfo['name']);
        }
          */
        return $this->itemid;
    }

    function updateItem(Array $args = array())
    {
        if (count($args) <=0 ) {
            //try get the args elsewhere
             $args = $this->getFieldValues();
             $args['itemid'] = $this->itemid;
        }

        if (count($args) > 0) {
            if (!empty($args['itemid'])) {
                $this->itemid = $args['itemid'];
            }
            foreach ($args as $name => $value) {
                if (isset($this->properties[$name])) {
                    $this->properties[$name]->setValue($value);
                }
            }
        }


        if(empty($this->itemid)) {
            // Try getting the id value from the item ID property if it exists
            foreach($this->properties as $property)
                if ($property->type == 21) $this->itemid = $property->value;
        }


    /*
        if (empty($this->itemid)) {
            $msg = xarML('Invalid item id in method #(1)() for dynamic object [#(2)] #(3)',
                         'updateItem',$this->objectid,$this->name);
             throw new BadParameterException(null,$msg);
        }
*/
        $modinfo = xarMod::getInfo($this->moduleid);
        // TODO: this won't work for objects with several static tables !
        // update all the data stores
        foreach (array_keys($this->datastores) as $store) {
            $itemid = $this->datastores[$store]->updateItem(array('objectid' => $this->objectid,
                                                                  'modid'    => $this->moduleid,
                                                                  'itemtype' => $this->itemtype,
                                                                  'itemid'   => $this->itemid,
                                                                  'modname'  => $modinfo['name']));
            if (empty($itemid)) return;
        }

        // call update hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
        //  jojo - this has been moved for over a year now - seems to be working well and removed prior problems with them here
        /* Move to API Func
        if (!empty($this->primary) && ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')) {
            $item = array();
            foreach (array_keys($this->properties) as $name) {
                $item[$name] = $this->properties[$name]->value;
            }
            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarMod::callHooks('item', 'update', $this->itemid, $item, $modinfo['name']);
        }
       */
        return $this->itemid;
    }

    function deleteItem(Array $args = array())
    {
        if (!empty($args['itemid'])) {
            $this->itemid = $args['itemid'];
        }

        if (empty($this->itemid)) {
            $msg = xarML('Invalid item id in method #(1)() for dynamic object [#(2)] #(3)',
                         'deleteItem',$this->objectid,$this->name);
             throw new BadParameterException(null,$msg);
        }

        $modinfo = xarMod::getInfo($this->moduleid);

    // TODO: this won't work for objects with several static tables !
        // delete the item in all the data stores
        foreach (array_keys($this->datastores) as $store) {
            $itemid = $this->datastores[$store]->deleteItem(array('objectid' => $this->objectid,
                                                                  'modid'    => $this->moduleid,
                                                                  'itemtype' => $this->itemtype,
                                                                  'itemid'   => $this->itemid,
                                                                  'modname'  => $modinfo['name']));
            if (empty($itemid)) return;
        }

        // call delete hooks for this item
        // Added: check if module is articles or roles to prevent recursive hook calls if using an external table for those modules
        // TODO:  somehow generalize this to prevent recursive calls in the general sense, rather then specifically for articles / roles
       /* Move to api func
       if (!empty($this->primary) && ($modinfo['name'] != 'articles') && ($modinfo['name'] != 'roles')) {
            $item = array();
            foreach (array_keys($this->properties) as $name) {
                $item[$name] = $this->properties[$name]->value;
            }
            $item['module'] = $modinfo['name'];
            $item['itemtype'] = $this->itemtype;
            $item['itemid'] = $this->itemid;
            xarMod::callHooks('item', 'delete', $this->itemid, $item, $modinfo['name']);
        }*/

        return $this->itemid;
    }

    /**
     * Get the next available item type (for objects that are assigned to the dynamicdata module)
     *
     * @param $args['moduleid'] module id for the object
     *
     * @return integer value of the next item type
     */
    function getNextItemtype(Array $args = array())
    {
        if (empty($args['moduleid'])) {
            $args['moduleid'] = $this->moduleid;
        }
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;

        $dynamicobjects = $xartable['dynamic_objects'];

        $query = "SELECT MAX(xar_object_itemtype)
                    FROM $dynamicobjects
                   WHERE xar_object_moduleid = ?";

        $result = $dbconn->Execute($query,array((int)$args['moduleid']));
        if (!$result || $result->EOF) return;

        $nexttype = $result->fields[0];

        $result->Close();

        // Note: this is *not* reliable in "multi-creator" environments
        $nexttype++;
        return $nexttype;
    }
}

/**
 * Dynamic Object List
 * Note : for performance reasons, we won't use an array of objects here,
 *        but a single object with an array of item values
 *
 * @subpackage dynamicdata module
 */
class Dynamic_Object_List extends Dynamic_Object_Master
{
    public $itemids;           // the list of item ids used in data stores
    public $where;
    public $sort;
    public $sortorder;
    public $groupby;
    public $numitems = null;
    public $startnum = null;
    public $count    = 0;       //option to count items before calling getItems for the pager

    public $startstore = null; // the data store we should start with (for sort)

    public $items = array();             // the result array of itemid => (property name => value)
    public $itemcount = null;       // the number of items given by countItems()

    // optional URL style for use in xarModURL() (defaults to itemtype=...&...)
    public $urlstyle = 'itemtype'; // TODO: table or object, or wrapper for all, or all in template, or...
    // optional display function for use in xarModURL() (defaults to 'display')
    public $linkfunc = 'display';

    /**
     * Inherits from Dynamic_Object_Master and sets the requested item ids, sort, where, ...
     *
     * @param $args['itemids'] array of item ids to return
     * @param $args['sort'] sort field(s)
     * @param $args['sortorder'] ASC or DESC
     * @param $args['where'] WHERE clause to be used as part of the selection
     * @param $args['numitems'] number of items to retrieve
     * @param $args['startnum'] start number
     */
    public function __construct($args)
    {
        // initialize the list of item ids
        $this->itemids = array();
        // initialize the items array
        $this->items = array();

        // get the object type information from our parent class
        parent::__construct($args);

        // see if we can access these objects, at least in overview
        if(!xarSecurityCheck('ViewDynamicDataItems',0,'Item',$this->moduleid.':'.$this->itemtype.':All')) return xarResponseForbidden();

        // set the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);
    }

    public function setArguments(Array $args = array())
    {

        // set the number of items to retrieve
        if (!empty($args['numitems'])) {
            $this->numitems = $args['numitems'];
        }
        // set the start number to retrieve
        if (!empty($args['startnum'])) {
            $this->startnum = $args['startnum'];
        }
        // set the list of requested item ids
        if (!empty($args['itemids'])) {
            if (is_numeric($args['itemids'])) {
                $this->itemids = array($args['itemids']);
            } elseif (is_string($args['itemids'])) {
                $this->itemids = explode(',',$args['itemids']);
            } elseif (is_array($args['itemids'])) {
                $this->itemids = $args['itemids'];
            }
        }
        if (!isset($this->itemids)) {
            $this->itemids = array();
        }

        // reset fieldlist and datastores if necessary
        if (isset($args['fieldlist']) && (!isset($this->fieldlist) || $args['fieldlist'] != $this->fieldlist)) {
            $this->fieldlist = $args['fieldlist'];
            $this->getDataStores(true);
        } elseif (isset($args['status']) && (!isset($this->status) || $args['status'] != $this->status)) {
            $this->status = $args['status'];
            $this->fieldlist = array();
            foreach ($this->properties as $name => $property) {
                if ($property->status == $this->status) {
                    $this->fieldlist[] = $name;
                }
            }
            $this->getDataStores(true);
        }

        // add where clause if itemtype is one of the properties (e.g. articles)
        if (isset($this->secondary) && !empty($this->itemtype) && $this->objectid > 2) {
           if (empty($args['where'])) {
               $args['where'] = $this->secondary . ' eq ' . $this->itemtype;
           } else {
               $args['where'] .= ' and ' . $this->secondary . ' eq ' . $this->itemtype;
           }
        }

        // Note: they can be empty here, which means overriding any previous criteria
        if (isset($args['sort']) || isset($args['where']) || isset($args['groupby']) || isset($args['cache']) ) {
            foreach (array_keys($this->datastores) as $name) {
                // make sure we don't have some left-over sort criteria
                if (isset($args['sort'])) {

                    $this->datastores[$name]->cleanSort();
                }
                // make sure we don't have some left-over where clauses
                if (isset($args['where'])) {
                    $this->datastores[$name]->cleanWhere();
                }
                // make sure we don't have some left-over group by fields
                if (isset($args['groupby'])) {
                    $this->datastores[$name]->cleanGroupBy();
                }
                // pass the cache value to the datastores
                if (isset($args['cache'])) {
                    $this->datastores[$name]->cache = $args['cache'];
                }
            }
        }


        // set the sort criteria
        $args['sortorder'] = isset($args['sortorder'])?$args['sortorder']: '';

        if (!empty($args['sort'])) {
            $this->setSort($args['sort'],$args['sortorder']);
        }
        // set the where clauses
        if (!empty($args['where'])) {
            $this->setWhere($args['where']);
        }

        // set the group by fields
        if (!empty($args['groupby'])) {
            $this->setGroupBy($args['groupby']);
        }

        // set the categories
        if (!empty($args['catid'])) {
            $this->setCategories($args['catid']);
        }
    }

    function setSort($sort='',$sortorder='')
    {
        if (is_array($sort)) {
            $this->sort = $sort;
        } else {
            $this->sort = explode(',',$sort);
        }

        foreach ($this->sort as $criteria) {
            if (is_array($sort)) {
                $this->sort = $sort;
            } else {
                $this->sort = explode(',',$sort);
            }
            foreach ($this->sort as $criteria) {
                // split off trailing ASC or DESC
                if (preg_match('/^(.+)\s+(ASC|DESC)\s*$/',$criteria,$matches)) {
                    $criteria = trim($matches[1]);
                    $sortorder = $matches[2];
                } else {
                    if (isset($sortorder) && !empty($sortorder)) {
                        $sortorder = strtoupper($sortorder);
                    } else {
                        $sortorder = 'ASC';
                    }
                }

                if (isset($this->properties[$criteria])) {
                    // pass the sort criteria to the right data store
                    $datastore = $this->properties[$criteria]->datastore;
                    // assign property to datastore if necessary
                    if (empty($datastore)) {
                        list($storename, $storetype) = $this->property2datastore($this->properties[$criteria]);
                        if (!isset($this->datastores[$storename])) {
                            $this->addDataStore($storename, $storetype);
                        }
                        $this->properties[$criteria]->datastore = $storename;
                        $this->datastores[$storename]->addField($this->properties[$criteria]); // use reference to original property
                        $datastore = $storename;
                    } elseif ($this->properties[$criteria]->type == 21) {
                        $this->datastores[$datastore]->addField($this->properties[$criteria]); // use reference to original property
                    }
                   $criteriatype = $this->properties[$criteria]->type;
                   $sorttype = ($criteriatype == 15 || $criteriatype == 17) ? 'NUMERIC':'STRING';
                    $this->datastores[$datastore]->addSort($this->properties[$criteria],$sortorder,$sorttype);
                    // if we're sorting on some field, we should start querying by the data store that holds it
                    if (!isset($this->startstore)) {
                       $this->startstore = $datastore;
                    }
                }
            }
        }
    }
    /**
     * Add where clause for a property
     *
     * @param string $name property name
     * @param string $clause SQL clause, e.g. = 123, IN ('this', 'that'),  LIKE '%something%', etc.
     * @param string $join '' for the first, 'and' or 'or' for the next
     * @param string $pre optional pre (
     * @param string $post optional post )
     */
    public function addWhere($name, $clause, $join='', $pre='', $post='')
    {
        if (!isset($this->properties[$name])) return;

        // pass the where clause to the right data store
        $datastore = $this->properties[$name]->datastore;
        // assign property to datastore if necessary
        if(empty($datastore)) {
            list($storename, $storetype) = $this->properties[$name]->getDataStore();
            if(!isset($this->datastores[$storename]))
                $this->addDataStore($storename, $storetype);

            $this->properties[$name]->datastore = $storename;
            $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
            $datastore = $storename;
        } elseif($this->properties[$name]->type == 21)
            $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property

        if ($datastore == '_dummy_') {
            // CHECKME: could the dummy datastore actually do something here ?
            return;
        }

        $this->datastores[$datastore]->addWhere(
            $this->properties[$name],
            $clause,
            $join,
            $pre,
            $post
        );
    }
    function setWhere($where)
    {
        if (empty($where)) return;
        if (is_array($where)) {
            $join = '';
            foreach ($where as $name => $val) {
                if (empty($name) || !isset($val) || $val === '') continue;
                if (!isset($this->properties[$name])) continue;
                if (is_numeric($val)) {
                    $mywhere = " = " . $val;
                } elseif (is_string($val)) {
                    $val = str_replace("'","\\'",$val);
                    $mywhere = " = '" . $val . "'";
                } elseif (is_array($val) && count($val) > 0) {
                    if (is_numeric($val[0])) {
                        $mywhere = " IN (" . implode(", ", $val) . ")";
                    } elseif (is_string($val[0])) {
                        $val = str_replace("'","\\'",$val);
                        $mywhere = " IN ('" . implode("', '", $val) . "')";
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
                $this->addWhere($name, $mywhere, $join);

                // default AND when using array format
                $join = 'and';
            }
            return;
        }
        // find all single-quoted pieces of text with and/or and replace them first, to
        // allow where clauses like : title eq 'this and that' and body eq 'here or there'
        $idx = 0;
        $found = array();
        // if (preg_match_all("/'(.*?)'/",$where,$matches)) {
        //jojo - single quotes break the current regex
        // Fix with new regex but assume here that any single quotes in the string are already escaped
        //first attempt at the regex more than likely requires improvement
          if (preg_match_all("/'(.*?(?<!\\\\))\'*?'/",$where,$matches)) {
            foreach ($matches[1] as $match) {
                // skip if it doesn't contain and/or
                if (!preg_match('/\s+(and|or)\s+/',$match)) {
                    continue;
                }
                $found[$idx] = $match;
                $match = preg_quote($match);

                $match = str_replace("#","\#",$match);

                $where = trim(preg_replace("#'$match'#","'~$idx~'",$where));
                $idx++;
            }
        }

        // cfr. BL compiler - adapt as needed (I don't think == and === are accepted in SQL)
        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
        $replaceLogic   = array( ' = ', ' != ',  ' < ',  ' > ',  ' = ', ' != ', ' <= ', ' >= ');

        $where = str_replace($findLogic, $replaceLogic, $where);

    // TODO: reject multi-source WHERE clauses :-)
       if (empty($where)) {
            $parts = array();
        } else {
            $parts = preg_split('/\s+(and|or)\s+/',$where,-1,PREG_SPLIT_DELIM_CAPTURE);
            $join = '';
        }
        foreach ($parts as $part) {
            if ($part == 'and' || $part == 'or') {
                $join = $part;
                continue;
            }
            $pieces = preg_split('/\s+/',$part);
            $pre = '';
            $post = '';
            $name = array_shift($pieces);
            if ($name == '(') {
                $pre = '(';
                $name = array_shift($pieces);
            }
            $last = count($pieces) - 1;
            if ($pieces[$last] == ')') {
                $post = ')';
                array_pop($pieces);
            }
            // sanity check on SQL
            if (count($pieces) < 2) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                             'query ' . $where, 'Dynamic_Object_List', 'getWhere', 'DynamicData');
                 throw new BadParameterException(null,$msg);
            }
            if (isset($this->properties[$name])) {
                // pass the where clause to the right data store
                $datastore = $this->properties[$name]->datastore;
                // assign property to datastore if necessary
                if (empty($datastore)) {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$name]);
                    if (!isset($this->datastores[$storename])) {
                        $this->addDataStore($storename, $storetype);
                    }
                    $this->properties[$name]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
                    $datastore = $storename;
                } elseif ($this->properties[$name]->type == 21) {
                    $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property
                }
                if (empty($idx)) {
                    $mywhere = join(' ',$pieces);
                } else {
                    $mywhere = '';
                    foreach ($pieces as $piece) {
                        // replace the pieces again if necessary
                        if (preg_match("#'~(\d+)~'#",$piece,$matches) && isset($found[$matches[1]])) {
                            $original = $found[$matches[1]];
                            $piece = preg_replace("#'~(\d+)~'#","'$original'",$piece);
                        }
                        $mywhere .= $piece . ' ';
                    }
                }
                $this->datastores[$datastore]->addWhere($this->properties[$name],
                                                        $mywhere,
                                                        $join,
                                                        $pre,
                                                        $post);
            }
        }
    }

    function setGroupBy($groupby)
    {
        if (is_array($groupby)) {
            $this->groupby = $groupby;
        } else {
            $this->groupby = explode(',',$groupby);
        }
        $this->isgrouped = 1;

        foreach ($this->groupby as $name) {
            if (isset($this->properties[$name])) {
                // pass the sort criteria to the right data store
                $datastore = $this->properties[$name]->datastore;
                // assign property to datastore if necessary
                if (empty($datastore)) {
                    list($storename, $storetype) = $this->property2datastore($this->properties[$name]);
                    if (!isset($this->datastores[$storename])) {
                        $this->addDataStore($storename, $storetype);
                    }
                    $this->properties[$name]->datastore = $storename;
                    $this->datastores[$storename]->addField($this->properties[$name]); // use reference to original property
                    $datastore = $storename;
                } elseif ($this->properties[$name]->type == 21) {
                    $this->datastores[$datastore]->addField($this->properties[$name]); // use reference to original property
                }
                $this->datastores[$datastore]->addGroupBy($this->properties[$name]);
                // if we're grouping by some field, we should start querying by the data store that holds it
                if (!isset($this->startstore)) {
                   $this->startstore = $datastore;
                }
            }
        }
    }

    function setCategories($catid)
    {
        if (!xarMod::isAvailable('categories')) return;
        $categoriesdef = xarMod::apiFunc('categories','user','leftjoin',
                                       array('modid' => $this->moduleid,
                                             'itemtype' => $this->itemtype,
                                             'catid' => $catid));
        foreach (array_keys($this->datastores) as $name) {
            $this->datastores[$name]->addJoin($categoriesdef['table'], $categoriesdef['field'], array(), $categoriesdef['where'], 'and', $categoriesdef['more']);
        }
    }

    function &getItems(Array $args = array())
    {
        // initialize the items array
        $this->items = array();

        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        if (empty($args['numitems'])) {
            $args['numitems'] = $this->numitems;
        }
        if (empty($args['startnum'])) {
            $args['startnum'] = $this->startnum;
        }

        // if we don't have a start store yet, but we do have a primary datastore, we'll start there
        if (empty($this->startstore) && !empty($this->primary)) {
            $this->startstore = $this->properties[$this->primary]->datastore;
        }

        // count the items first if we haven't done so yet, but only on demand (args['count'] = 1)
        if (!empty($this->count) && !isset($this->itemcount)) {
            $this->itemcount = $this->countItems();
        }
        // first get the items from the start store (if any)
        if (!empty($this->startstore)) {
            $this->datastores[$this->startstore]->getItems($args);

            // check if we found something - if not, no sense looking further
            if (count($this->itemids) == 0) {
                return $this->items;
            }
        }

        // then retrieve the other info about those items
        foreach (array_keys($this->datastores) as $name) {
            if (!empty($this->startstore) && $name == $this->startstore) {
                continue;
            }
            $this->datastores[$name]->getItems($args);
        }

        return $this->items;
    }

    /**
     * Count the number of items that match the selection criteria
     * Note : this must be called *before* getItems() if you're using numitems !
     */
    function countItems(Array $args = array())
    {
        // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
        $this->setArguments($args);

        // if we don't have a start store yet, but we do have a primary datastore, we'll count there
        if (empty($this->startstore) && !empty($this->primary)) {
            $this->startstore = $this->properties[$this->primary]->datastore;
        }

        // try to count the items in the start store (if any)

        if (!empty($this->startstore)) {
            $itemcount = $this->datastores[$this->startstore]->countItems($args);
            $this->itemcount = $itemcount;
            return $itemcount;

        // else if we don't have a start store, we're probably stuck, but we'll try the first one anyway :)
        } else {
        // TODO: find some better way to determine which data store to count in
            foreach (array_keys($this->datastores) as $name) {
                $itemcount = $this->datastores[$name]->countItems($args);
                  $this->itemcount = $itemcount;
                return $itemcount;
            }
        }
    }

function showList(Array $args = array())
    {
        $args = $this->toArray($args);

        if(!empty($this->status)) {
            $state = $this->status;
        } else {
            $state = Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE;
        }
        //initialize the array
        $args['properties'] = array();
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == ($state & Dynamic_Property_Master::DD_DISPLAYMASK))
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)
                    ) {
                        $args['properties'][$name] =& $this->properties[$name];
                    }
                }
            }
        } else {
            foreach($this->properties as $name => $property)
                if(($this->properties[$name]->getDisplayStatus() == ($state & Dynamic_Property_Master::DD_DISPLAYMASK))
                || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE)
                || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)
                ) {
                        $args['properties'][$name] =& $this->properties[$name];
            }

        }

        //cumulus - added
        // Order the fields if this is an extended object
        if (!empty($this->fieldorder)) {
            $tempprops = array();
            foreach ($this->fieldorder as $field)
                if (isset($args['properties'][$field]))
                    $tempprops[$field] = $args['properties'][$field];
            $args['properties'] = $tempprops;
        }
        //end - added
        $args['items'] =& $this->items;

        // add link to display the item
        if (empty($args['linkfunc'])) {
            $args['linkfunc'] = $this->linkfunc;
        }
        if (empty($args['linklabel'])) {
            $args['linklabel'] = xarML('Display');
        }
        if (empty($args['param'])) {
            $args['param'] = $this->urlparam;
        }
        if (empty($args['linkfield'])) {
            $args['linkfield'] = '';
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        $args['moduleid'] = $this->moduleid;

        $modinfo = xarMod::getInfo($this->moduleid);
        $modname = $modinfo['name'];
        $itemtype = $this->itemtype;

        //cumulus - added
        // override for viewing dynamic objects
        if ($modname == 'dynamicdata' && $this->itemtype == 0 && empty($this->table)) {
            $linktype = 'admin'; //showview has 'user' linktype here
            $linkfunc = 'view';
            // Don't show link to view items that don't belong to the DD module
            // Set to 0 when interested in viewing them anyway...
            $dummy_mode = 1;
            $spriteview = 'xs-item-list';
        } else {
            $linktype = 'admin';
            $linkfunc = $args['linkfunc'];
            $dummy_mode = 0;
            $spriteview = 'xs-display';
        }
        $args['linktype'] = $linktype;
        //end - added
         $itemtype = $this->itemtype;
        if (empty($itemtype)) {
            $itemtype = null; // don't add to URL
        }
        if (empty($this->table)) {
            $table = null;
        } else {
            $table = $this->table;
        }
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['modname'] = $modname;
        $args['itemtype'] = $itemtype;
        $args['links'] = array();
       //jojo - added
        $args['objectid'] = $this->objectid;

        if (empty($args['template']) && !empty($args['objectname'])) {
            $args['template'] = $args['objectname'];
        }

        if(empty($args['tplmodule'])) {
            if(!empty($this->tplmodule)) {
                $args['tplmodule'] = $this->tplmodule;
            } else {
                $args['tplmodule'] = $modname;
            }
        }

        if (empty($args['urlmodule'])) {
            if (!empty($this->urlmodule)) {
                $args['urlmodule'] = $this->urlmodule;
            } else {
                $args['urlmodule'] = $modname;
            }
        }
        // initialize
        $args['protected'] = array();
        foreach (array_keys($this->items) as $itemid) {
            // TODO: improve this + SECURITY !!!

            $options = array();
            if (!empty($this->isgrouped)) {
                $args['links'][$itemid] = $options;
                continue;
            }
            $displaylabel= xarML('Display');
            $args['itemid'] = $itemid;

            //work out if this is a protected 'system object'
            $sysobs= xarModGetVar('dynamicdata', 'systemobjects');
            try {
                $sysobs = @unserialize($sysobs);
            } catch (Exception $e) {

            }
            $sysobs = is_array($sysobs)?$sysobs:array();
            $checkid = isset($this->items[$itemid]['objectid']) ?$this->items[$itemid]['objectid']:'';
            $protectedob = 0;

            //we need admin level to access protected objects
            if (in_array($checkid,$sysobs) && !xarSecurityCheck('AdminDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                $protectedob = 1;
            }
                     //jojo - try this here for now - we need to modify it for other areas eg in the admin but ok here for now
                //$args['links'][$itemid] = $this->getViewOptions($args);
                //TODO - fix so this whole section can be replaced with the $args['links'] above

                if(xarSecurityCheck('DeleteDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                    if (isset($this->items[$itemid]['objectid']) && ($this->items[$itemid]['objectid']< 3) ){
                          $options[] = array('otitle' => xarML('Disabled'),
                                            'olink'  => '',
                                            'ojoin'  => '',
                                             'oclass' => 'sprite xs-disabled');
                    } else {
                          $options[] = array('otitle' => xarML('Delete'),
                                             'olink'  => xarModURL($args['urlmodule'],'admin','delete',
                                                   array('itemtype'     => $itemtype,
                                                         'table'        => $table,
                                                         $args['param'] => $itemid,

                                                         'template'     => $args['template'])),
                                             'ojoin'  => '|',
                                             'oclass' => 'esprite xs-delete');
                    }
                    if (($this->moduleid == 182) && ($this->objectid == 1)) {
                              $displaylabel= xarML('Items');
                    }
                    if ($dummy_mode && $this->items[$itemid]['moduleid'] != 182) { //hooked
                        $options[] = array('otitle' => $displaylabel,
                                           'olink'  => '',
                                           'ojoin'  => '',
                                           'oclass' => 'xar-icon-disabled esprite '.$spriteview);

                    } else {

                        $options[] = array('otitle' => $displaylabel,
                                           'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                       array('itemtype'     => $itemtype,
                                                             'table'        => $table,
                                                             $args['param'] => $itemid,
                                                             'template'     => $args['template'])),
                                           'ojoin'  => '',
                                           'oclass' => 'esprite '.$spriteview);
                    }

                    $options[] = array('otitle' => xarML('Edit'),
                                       'olink'  => xarModURL($args['urlmodule'],'admin','modify',
                                                   array('itemtype'     => $itemtype,
                                                         'table'        => $table,
                                                         $args['param'] => $itemid,
                                                         'template'     => $args['template'])),
                                       'ojoin'  => '|',
                                       'oclass' => 'esprite xs-modify');

                    if (($this->moduleid == 182) && ($this->objectid == 1)) {//onlyshow properties for Data Objects
                        $options[] = array('otitle' => xarML('Properties'),

                                           'olink'  => xarModURL($args['urlmodule'],'admin','modifyprop',
                                                       array('itemtype'     => $itemtype,
                                                             'table'        => $table,
                                                             $args['param'] => $itemid,
                                                             'template'     => $args['template'])),
                                            'ojoin'  => '|',
                                            'oclass' => 'sprite xs-document-properties');
                    }
                } elseif(xarSecurityCheck('ModerateDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {

                    if ($dummy_mode && $this->items[$itemid]['moduleid'] != 182) {
                        $options[] = array('otitle' => xarML('View'),
                                           'olink'  => '',
                                           'ojoin'  => '',
                                           'oclass' => 'xar-icon-disabled esprite '.$spriteview);


                    } else {

                        $options[] = array('otitle' =>  $displaylabel,
                                           'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                       array('itemtype'     => $itemtype,
                                                             'table'        => $table,
                                                             $args['param'] => $itemid,
                                                             'template'     => $args['template'])),
                                           'ojoin'  => '',
                                           'oclass' => 'esprite '.$spriteview);
                    }

                      $options[] = array('otitle' => xarML('Edit'),
                                               'olink'  => xarModURL($args['urlmodule'],'admin','modify',
                                                           array('itemtype'     => $itemtype,
                                                                 'table'        => $table,
                                                                 $args['param'] => $itemid,
                                                                 'template'     => $args['template'])),
                                               'ojoin'  => '|',
                                               'oclass' => 'esprite xs-modify');


                } elseif(xarSecurityCheck('EditDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                    //we want to protect the editing of objects at this level but editing items is fine
                    if ($dummy_mode && $this->items[$itemid]['moduleid'] != 182) {
                        $options[] = array('otitle' => xarML('View'),
                                           'olink'  => '',
                                           'ojoin'  => '',
                                           'oclass' => 'xar-icon-disabled esprite '.$spriteview);

                    } else {

                        $options[] = array('otitle' =>  $displaylabel, //display or list
                                           'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                       array('itemtype'     => $itemtype,
                                                             'table'        => $table,
                                                             $args['param'] => $itemid,
                                                             'template'     => $args['template'])),
                                           'ojoin'  => '',
                                           'oclass' => 'esprite '.$spriteview);
                    }
                    if ($this->moduleid == 182 && $this->objectid ==1) {
                        //don't allow editing of object itself at this level
                        $options[] = array('otitle' => xarML('Edit'),
                                               'olink'  => '',
                                               'ojoin'  => '',
                                               'oclass' => 'xar-icon-disabled esprite xs-modify');
                    } else {
                      $options[] = array('otitle' => xarML('Edit'),
                                               'olink'  => xarModURL($args['urlmodule'],'admin','modify',
                                                           array('itemtype'     => $itemtype,
                                                                 'table'        => $table,
                                                                 $args['param'] => $itemid,
                                                                 'template'     => $args['template'])),
                                               'ojoin'  => '|',
                                               'oclass' => 'esprite xs-modify');

                    }
                } elseif(xarSecurityCheck('ReadDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                    if ($dummy_mode && $this->items[$itemid]['moduleid'] != 182) {
                        $options[] = array('otitle' => xarML('Items'),
                                           'olink'  => '',
                                           'ojoin'  => '',
                                           'oclass' => 'xar-icon-disabled esprite '.$spriteview);
                    } else {
                        $options[] = array('otitle' =>  $displaylabel,
                                           'olink'  => xarModURL($args['urlmodule'],$linktype,$linkfunc,
                                                       array('itemtype'     => $itemtype,
                                                             'table'        => $table,
                                                             $args['param'] => $itemid,
                                                             'template'     => $args['template'])),
                                           'ojoin'  => '',
                                           'oclass' => 'esprite '.$spriteview);
                    }
                }

            $args['links'][$itemid] = $options;
            $args['protected'][$itemid] = $protectedob;
        }

        if (!empty($this->isgrouped)) {
            foreach (array_keys($args['properties']) as $name) {
                if (!empty($this->properties[$name]->operation)) {
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
                }
            }
            $args['linkfield'] = 'N/A';
        }
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        } else {
            $args['isprimary'] = false;
        }
        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }

       if (xarSecurityCheck('AddDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':All')) {
            $args['newlink'] = xarModURL($args['urlmodule'],'admin','new',
                                         array('itemtype' => $itemtype,
                                               'table'    => $table));
        } else {
            $args['newlink'] = '';
        }

        if (!isset($args['managepropslinks'])) { //improve
            $args['managepropslink'] = '';
            if ($this->moduleid == 182 && $this->objectid ==1 || isset($table) && !empty($table)) {

            }elseif (xarSecurityCheck('AdminDynamicData',0))  {
                $args['managepropslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                         array('itemid' => $this->objectid,
                                               'template'    => 'objects'));
            }
        }
        // see if we have an itemcount we can use for the pager
        if (!empty($args['itemcount'])) {
            // the item count was passed to showList()
            $this->itemcount = $args['itemcount'];
        }

        if (empty($args['pagerurl'])) {
            $args['pagerurl'] = '';
        }

         $this->pagerurl = $args['pagerurl'];

        if (!isset($this->startnum)) $this->startnum = 1;


        list($args['prevurl'],
             $args['nexturl'],
             $args['sorturl']) = $this->getPager($args['pagerurl']);
        $this->sorturl = $args['sorturl'];
          $args['object'] = $this;
        // Pass the objectid too, comfy for customizing the templates
        // with custom tags.
        $args['objectid'] = $this->objectid;
         //add support for asc desc sorting and sprites in one place
        $args['sortimgclass'] = '';
        $args['sortimglabel']= '';
        $args['sortorder'] = isset( $args['sortorder'] ) ? $args['sortorder']  : '';
        if ($args['sortorder']== 'asc') {
            $args['sortimgclass'] = 'esprite xs-sorted-asc';
            $args['sortimglabel'] = xarML('Sorted ascending');
            $args['sortimg'] = xarTplGetImage('icons/sorted-asc.png','base');
        } else {
            $args['sortimgclass'] = 'esprite xs-sorted-desc';
            $args['sortimglabel']= xarML('Sorted descending');
            $args['sortimg'] = xarTplGetImage('icons/sorted-desc.png','base');
        }
        //decide what image goes where
        $args['sortimage'] = array();
        foreach ($args['properties'] as $propname=>$value) {
            $args['sortimage'][$propname] = false;
            //hmm - need to take the first i guess
            $sorted = is_array($args['sort']) ? current($args['sort']) :$args['sort'];
             if ($sorted == $propname) $args['sortimage'][$propname] = true;
        }

        $args['dsort']= ($args['sortorder'] == 'asc') ? 'desc' : 'asc';
        $args['dummyimage'] = xarTplGetImage('blank.gif','base');
        $args['objectlabel'] = !isset( $args['objectlabel']) ? $args['label'] :  $args['objectlabel'];
        //filter our the system objects from the list
        foreach ($args['protected'] as $protectedid=>$isprotected) {
            if ($isprotected ==1) {
                unset($args['items'][$protectedid]);
                unset($args['links'][$protectedid]);
            }
        }
        return xarTplObject($args['tplmodule'],$args['template'],'showlist',$args);
    }

    function showView(Array $args = array())
{
        $args = $this->toArray($args);

        if(!empty($this->status)) {
            $state = $this->status;
        } else {
            $state = Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE;
        }

        //initialize
        $args['properties'] = array();
        if (count($args['fieldlist']) > 0 || !empty($this->status)) {

            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    if(($this->properties[$name]->getDisplayStatus() == ($state & Dynamic_Property_Master::DD_DISPLAYMASK))
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                    || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)
                    ) {
                        $args['properties'][$name] =& $this->properties[$name];
                    }
                }
            }
        } else {

            foreach($this->properties as $name => $property)
                if(($this->properties[$name]->getDisplayStatus() == ($state & Dynamic_Property_Master::DD_DISPLAYMASK))
                || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE)
                || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_VIEWONLY)
                || ($this->properties[$name]->getDisplayStatus() == Dynamic_Property_Master::DD_DISPLAYSTATE_IGNORED)
                ) {
                        $args['properties'][$name] =& $this->properties[$name];
                }

            // Order the fields if this is an extended object
           /* if (!empty($this->fieldorder)) {
                $tempprops = array();
                foreach ($this->fieldorder as $field)
                    if (isset($args['properties'][$field]))
                        $tempprops[$field] = $args['properties'][$field];
                $args['properties'] = $tempprops;
            }
            */
        }

        //cumulus - added
        // Order the fields if this is an extended object
        if (!empty($this->fieldorder)) {
            $tempprops = array();
            foreach ($this->fieldorder as $field)
                if (isset($args['properties'][$field]))
                    $tempprops[$field] = $args['properties'][$field];
            $args['properties'] = $tempprops;
        }
        //end - added

        $args['items'] =& $this->items;

        // add link to display the item
        if (empty($args['linkfunc'])) {
            $args['linkfunc'] = $this->linkfunc;
        }
        if (empty($args['linklabel'])) {
            $args['linklabel'] = xarML('Display');
        }
        if (empty($args['param'])) {
            $args['param'] = $this->urlparam;
        }
        if (empty($args['linkfield'])) {
            $args['linkfield'] = '';
        }

        // pass some extra template variables for use in BL tags, API calls etc.
        $args['moduleid'] = $this->moduleid;

        $modinfo = xarMod::getInfo($this->moduleid);
        $modname = $modinfo['name'];
        $itemtype = $this->itemtype;

        //cumulus - added
        // override for viewing dynamic objects
        if($modname == 'dynamicdata' && $this->itemtype == 0 && empty($this->table)) {
            $linktype = 'user';
            $linkfunc = 'view';
            // Don't show link to view items that don't belong to the DD module
        } else {
            $linktype = 'user';
            $linkfunc = $args['linkfunc'];
        }
        $args['linktype'] = $linktype;

        //end - added

        if (empty($itemtype)) {
            $itemtype = null; // don't add to URL
        }
        if (empty($this->table)) {
            $table = null;
        } else {
            $table = $this->table;
        }
        if (empty($this->name)) {
           $args['objectname'] = null;
        } else {
           $args['objectname'] = $this->name;
        }
        $args['modname'] = $modname;
        $args['itemtype'] = $itemtype;
        $args['links'] = array();
        //jojo - added
        $args['objectid'] = $this->objectid;

        if (empty($args['template']) && !empty($args['objectname'])) {
            $args['template'] = $args['objectname'];
        }

        if(empty($args['tplmodule'])) {
            if(!empty($this->tplmodule)) {
                $args['tplmodule'] = $this->tplmodule;
            } else {
                $args['tplmodule'] = $modname;
            }
        }
        if (empty($args['urlmodule'])) {
            if (!empty($this->urlmodule)) {
                $args['urlmodule'] = $this->urlmodule;
            } else {
                $args['urlmodule'] = $modname;
            }
        }

        //work out if this is a protected 'system object'
        $sysobs= xarModGetVar('dynamicdata', 'systemobjects');
        try {
                $sysobs = @unserialize($sysobs);
            } catch (Exception $e) {
                $sysobs = array();
            }

        $checkid = isset($this->objectid) ?$this->objectid:'';
        $protectedob = 0;
        $args['protected'] = array();
        foreach (array_keys($this->items) as $itemid) {
            $options = array();
            if (!empty($this->isgrouped)) {
                $args['links'][$itemid] = array();
                continue;
            }
           $args['itemid'] = $itemid;

            //jojo - we need to modify it for other areas eg in the admin but ok here for now
            $args['links'][$itemid] = $this->getViewOptions($args);

            //we need admin level to access protected objects
            if (in_array($checkid,$sysobs) && !xarSecurityCheck('AdminDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
                $protectedob = 1;
            }
            $args['protected'][$itemid] = $protectedob;
        }

        if (!empty($this->isgrouped)) {
            foreach (array_keys($args['properties']) as $name) {
                if (!empty($this->properties[$name]->operation)) {
                    $this->properties[$name]->label = $this->properties[$name]->operation . '(' . $this->properties[$name]->label . ')';
                }
            }
            $args['linkfield'] = 'N/A';
        }
        if (!empty($this->primary)) {
            $args['isprimary'] = true;
        }  else {
            $args['isprimary'] = false;
        }

        if (!empty($this->catid)) {
            $args['catid'] = $this->catid;
        } else {
            $args['catid'] = null;
        }
        if (!empty($args['itemcount'])) {
            // the item count was passed to showView()
            $this->itemcount = $args['itemcount'];
        }
        if (empty($args['pagerurl'])) {
            $args['pagerurl'] = '';
        }
        //paging
        $this->pagerurl = $args['pagerurl'];

        if (!isset($this->startnum)) $this->startnum = 1;
        list($args['prevurl'],
             $args['nexturl'],
             $args['sorturl']) = $this->getPager($args['pagerurl']);
             $this->sorturl = $args['sorturl'];

        //add support for asc desc sorting and sprites in one place
        $args['sortimgclass'] = '';
        $args['sortimglabel']= '';
        $args['sortorder'] = isset( $args['sortorder'] ) ? $args['sortorder']  : '';
        if ($args['sortorder']== 'asc') {
            $args['sortimgclass'] = 'esprite xs-sorted-asc';
            $args['sortimglabel'] = xarML('Sorted ascending');
            $args['sortimg'] = xarTplGetImage('icons/sorted-asc.png','base');
        } else {
            $args['sortimgclass'] = 'esprite xs-sorted-desc';
            $args['sortimglabel']= xarML('Sorted descending');
            $args['sortimg'] = xarTplGetImage('icons/sorted-desc.png','base');
        }
        //decide what image goes where
        $args['sortimage'] = array();
        foreach ($args['properties'] as $propname=>$value) {
            $args['sortimage'][$propname] = false;
            //hmm - need to take the first i guess
            $sorted = is_array($args['sort'])? current($args['sort']) : $args['sort'];
             if ($sorted == $propname) $args['sortimage'][$propname] = true;
        }

        $args['dsort']= ($args['sortorder'] == 'asc') ? 'desc' : 'asc';
        $args['dummyimage'] = xarTplGetImage('blank.gif','base');

        $args['objectlabel'] = !isset( $args['objectlabel']) ? $args['label'] :  $args['objectlabel'];
        //filter our the system objects from the list
        foreach ($args['protected'] as $protectedid=>$isprotected) {
            if ($isprotected ==1) {
                unset($args['items'][$protectedid]);
                unset($args['links'][$protectedid]);
            }
        }
        $args['object'] = $this;

        return xarTplObject($args['tplmodule'],$args['template'],'showview',$args);
    }

   /**
      * Get List to fill showView template options
      *
      * @return array
      *
      * @TODO add this function for now, needs to be reviewed for xarigami and adjust other
      * functions/classes appropriately to deal with this
      */
    public function getViewOptions(Array $args = array())
    {
        extract($args);
        if (empty($return_url)) $return_url = xarServer::getCurrentURL();
        $urlargs = array();
        $urlargs['table'] = isset($table) ? $table : NULL;
        $urlargs[$args['param']] = $itemid;
        $urlargs['tplmodule'] = $args['tplmodule'];
        $urlargs['return_url'] = $return_url;
        // The next 3 lines make the DD modify/display routines work for overlay objects
        // TODO: do we need the concept of urlmodule at all?
        $info = Dynamic_Object_Master::getObjectInfo($args);

        $urlargs['name'] = $info['name'];
        $args['tplmodule'] = 'dynamicdata';
        if (!isset($args['urlmodule'])) {
            $args['urlmodule'] = 'dynamicdata';
        }
        $options = array();

        if (xarSecurityCheck('ReadDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {

             $options['view'] = array('otitle' => xarML('Display'),
                                     'olink'  =>           xarModURL($args['urlmodule'],'user',$args['linkfunc'],
                                                              array('itemtype'     => $itemtype,
                                                                    'table'        =>  $urlargs['table'] ,
                                                                    $args['param'] => $itemid,
                                                                    'template'     => $args['template'],
                                                                    'return_url'   => $return_url
                                                                    )),
                                                            'ojoin'  => '');
              /*
              if ($this->itemtype == 0 ) {
                    $options['viewitems'] = array('otitle' => xarML('Items'),
                                                  'olink'  => xarModURL('dynamicdata','admin','view',
                                                                  array('itemid' => $args['itemid'])),
                                                  'ojoin'  => '|'
                                                 );
              }*/
        }
        if (xarSecurityCheck('EditDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {
            //don't allow editing of an objects configuration at edit level
            if ($this->moduleid == 182 && $this->objectid ==1) {
             $options['modify'] = array('otitle' => xarML('Edit'),
                                     'olink'  =>           '',
                                                            'ojoin'  => '');
            } else {
             $options['modify'] = array('otitle' => xarML('Edit'),
                                     'olink'  =>           xarModURL($args['urlmodule'],'admin','modify',
                                                              array('itemtype'     => $itemtype,
                                                                    'table'        =>  $urlargs['table'] ,
                                                                    $args['param'] => $itemid,
                                                                    'template'     => $args['template'],
                                                                    'return_url'   => $return_url)),
                                                            'ojoin'  => '|');
            }
        }
        if (xarSecurityCheck('ModerateDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid)) {

             $options['modify'] = array('otitle' => xarML('Edit'),
                                     'olink'  =>           xarModURL($args['urlmodule'],'admin','modify',
                                                              array('itemtype'     => $itemtype,
                                                                    'table'        =>  $urlargs['table'] ,
                                                                    $args['param'] => $itemid,
                                                                    'template'     => $args['template'],
                                                                    'return_url'   => $return_url)),
                                                            'ojoin'  => '|');
             if ($this->objectid == 1) {
                $options['modifyprops'] = array('otitle' => xarML('Properties'),
                                     'olink'  => xarModURL($args['urlmodule'],'admin','modifyprop',$urlargs),
                                     'ojoin'  => '|');
             }
        }
        if (xarSecurityCheck('DeleteDynamicDataItem',0,'Item',$this->moduleid.':'.$this->itemtype.':'.$itemid))  {
            if($this->objectid == 1){
                $options['modifyprops'] = array('otitle' => xarML('Properties'),
                                     'olink'  => xarModURL($args['urlmodule'],'admin','modifyprop',
                                                   $urlargs),
                                       'ojoin'  => '|');
                                       /*
                $options['viewitems'] = array('otitle' => xarML('Items'),
                                              'olink'  => xarModURL('dynamicdata','admin','view',
                                                              array('itemid' => $itemid)),
                                              'ojoin'  => '|'
                                             );
                                             */
            }
            $options['delete'] = array('otitle' => xarML('Delete'),
                                       'olink'  =>           xarModURL($args['urlmodule'],'admin','delete',
                                                              array('itemtype'     => $itemtype,
                                                                    'table'        =>  $urlargs['table'] ,
                                                                    $args['param'] => $itemid,
                                                                    'template'     => $args['template'],
                                                                    'return_url'   => $return_url)),
                                                            'ojoin'  => '|');
        }



        return $options;
    }


    /**
     * Get the labels and values to include in some output view for these items
     */
    function &getViewValues(Array $args = array())
    {
        if (empty($args['fieldlist'])) {
            $args['fieldlist'] = $this->fieldlist;
        }
        if (count($args['fieldlist']) == 0 && empty($this->status)) {
            $args['fieldlist'] = array_keys($this->properties);
        }

        $viewvalues = array();
        foreach ($this->itemids as $itemid) {
            $viewvalues[$itemid] = array();
            foreach ($args['fieldlist'] as $name) {
                if (isset($this->properties[$name])) {
                    $label = xarVarPrepForDisplay($this->properties[$name]->label);
                    if (isset($this->items[$itemid][$name])) {
                        $value = $this->properties[$name]->showOutput(array('value' => $this->items[$itemid][$name]));
                    } else {
                        $value = '';
                    }
                    $viewvalues[$itemid][$label] = $value;
                }
            }
        }
        return $viewvalues;
    }

    function getPager($currenturl = '')
    {
        $prevurl = '';
        $nexturl = '';
        $sorturl = '';

        if (empty($this->startnum)) {
            $this->startnum = 1;
        }

    // TODO: count items before calling getItems() if we want some better pager

        // Get current URL (this uses &amp; by default now)
        if (empty($currenturl)) {
            $currenturl = xarServer::getCurrentURL();
        }

    // TODO: clean up generation of sort URL

        // get rid of current startnum and sort params and sortby params
        $sorturl = $currenturl;
        $sorturl = preg_replace('/&amp;startnum=\d+/','',$sorturl);
        $sorturl = preg_replace('/\?startnum=\d+&amp;/','?',$sorturl);
        $sorturl = preg_replace('/\?startnum=\d+$/','',$sorturl);
        $sorturl = preg_replace('/&amp;sort=\w+/','',$sorturl);
       $sorturl = preg_replace('/&amp;sortorder=\w+/','',$sorturl);
        $sorturl = preg_replace('/\?sort=\w+&amp;/','?',$sorturl);
        $sorturl = preg_replace('/\?sort=\w+$/','',$sorturl);
        // add sort param at the end of the URL
        if (preg_match('/\?/',$sorturl)) {
            $sorturl = $sorturl . '&amp;sort';
        } else {
            $sorturl = $sorturl . '?sort';
        }

        if (empty($this->numitems) || ( (count($this->items) < $this->numitems) && $this->startnum == 1 )) {
            return array($prevurl,$nexturl,$sorturl);
        }

        if (preg_match('/startnum=\d+/',$currenturl)) {
            if (count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = preg_replace('/startnum=\d+/',"startnum=$next",$currenturl);
            }
            if ($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = preg_replace('/startnum=\d+/',"startnum=$prev",$currenturl);
            }
        } elseif (preg_match('/\?/',$currenturl)) {
            if (count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '&amp;startnum=' . $next;
            }
            if ($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '&amp;startnum=' . $prev;
            }
        } else {
            if (count($this->items) == $this->numitems) {
                $next = $this->startnum + $this->numitems;
                $nexturl = $currenturl . '?startnum=' . $next;
            }
            if ($this->startnum > 1) {
                $prev = $this->startnum - $this->numitems;
                $prevurl = $currenturl . '?startnum=' . $prev;
            }
        }

        return array($prevurl,$nexturl,$sorturl);
    }

    /**
     * Get items one at a time, instead of storing everything in $this->items
     */
    function getNext(Array $args = array())
    {
        static $start = true;

        if ($start) {
            // set/override the different arguments (item ids, sort, where, numitems, startnum, ...)
            $this->setArguments($args);

            if (empty($args['numitems'])) {
                $args['numitems'] = $this->numitems;
            }
            if (empty($args['startnum'])) {
                $args['startnum'] = $this->startnum;
            }

            // if we don't have a start store yet, but we do have a primary datastore, we'll start there
            if (empty($this->startstore) && !empty($this->primary)) {
                $this->startstore = $this->properties[$this->primary]->datastore;
            }

            $start = false;
        }

        $itemid = null;
        // first get the items from the start store (if any)
        if (!empty($this->startstore)) {
            $itemid = $this->datastores[$this->startstore]->getNext($args);

            // check if we found something - if not, no sense looking further
            if (empty($itemid)) {
                return;
            }
        }
/* skip this for now !
        // then retrieve the other info about those items
        foreach (array_keys($this->datastores) as $name) {
            if (!empty($this->startstore) && $name == $this->startstore) {
                continue;
            }
            //$this->datastores[$name]->getItems($args);
            $args['itemid'] = $itemid;
            $this->datastores[$name]->getItem($args);
        }
*/
        return $itemid;
    }

}

?>
