<?php
/**
 * Dynamic data initilazation
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * initialise the dynamicdata module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_init()
{
    /**
     * Create tables
     */
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $dynamic_objects = $xartable['dynamic_objects'];
    $dynamic_properties = $xartable['dynamic_properties'];
    $dynamic_data = $xartable['dynamic_data'];
    $dynamic_relations = $xartable['dynamic_relations'];
    $dynamic_properties_def = $xartable['dynamic_properties_def'];
    $modulestable = $xartable['modules'];

    //Load Table Maintenance API
    xarDBLoadTableMaintenanceAPI();

    /**
     * Dynamic Objects table
     */
    $objectfields = array('xar_object_id' => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0',
                                                  'increment'   => true,
                                                  'primary_key' => true),
                /* the name used to reference an object and for short urls (eventually) */
                    'xar_object_name'     => array('type'        => 'varchar',
                                                  'size'        => 30,
                                                  'null'        => false,
                                                  'default'     => ''),
                /* the label used for display */
                    'xar_object_label'    => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'null'        => false,
                                                  'default'     => ''),
                /* the module this object relates to */
                    'xar_object_moduleid' => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                /* the optional item type within this module */
                    'xar_object_itemtype' => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                /* the URL parameter used to pass on the item id to the original module */
                    'xar_object_urlparam' => array('type'        => 'varchar',
                                                  'size'        => 30,
                                                  'null'        => false,
                                                  'default'     => 'itemid'),
                /* the highest item id for this object (used if the object has a dynamic item id field) */
                    'xar_object_maxid'    => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                /* any configuration settings for this object (future) */
                    'xar_object_config'   => array('type'=>'text'),
                /* use the name of this object as alias for short URLs */
                    'xar_object_isalias'  => array('type'        => 'integer',
                                                  'size'        => 'tiny',
                                                  'null'        => false,
                                                  'default'     => '0')
              );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($dynamic_objects,$objectfields);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;
    xarDB::importTables(array('dynamic_objects' => xarDB::$prefix . '_dynamic_objects'));
// TODO: evaluate efficiency of combined index vs. individual ones
    // the combination of module id + item type *must* be unique
    $query = xarDBCreateIndex($dynamic_objects,
                           array('name'   => 'i_' . xarDB::$prefix . '_dynobjects_combo',
                                 'fields' => array('xar_object_moduleid',
                                                   'xar_object_itemtype'),
                                 'unique' => 'true'));
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // the object name *must* be unique
    $query = xarDBCreateIndex($dynamic_objects,
                           array('name'   => 'i_' . xarDB::$prefix . '_dynobjects_name',
                                 'fields' => array('xar_object_name'),
                                 'unique' => 'true'));
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    /**
     * Note : Classic chicken and egg problem - we can't use createobject() here
     *        because dynamicdata doesn't know anything about objects yet :-)
     */

    $modid = xarMod::getId('dynamicdata');

    // create default objects for dynamic data
    $objects = array(
                     // 1 -> 3
                     "'objects','Dynamic Objects',$modid,0,'itemid',0,'',0",
                     "'properties','Dynamic Properties',$modid,1,'itemid',0,'',0",
                     "'sample','Sample Object',$modid,2,'itemid',3,'',0"
                    );
    $objectid = array();
    $idx = 0;
    foreach ($objects as $object) {
        $nextId = $dbconn->GenId($dynamic_objects);
        $query = "INSERT INTO $dynamic_objects
                         (xar_object_id, xar_object_name, xar_object_label, xar_object_moduleid, xar_object_itemtype, xar_object_urlparam, xar_object_maxid, xar_object_config, xar_object_isalias)
                  VALUES (?, $object)";
        $result = $dbconn->Execute($query,array($nextId));
        if (!isset($result)) return;
        $idx++;
        $objectid[$idx] = $dbconn->PO_Insert_ID($dynamic_objects,'xar_object_id');
    }


    /**
     * Dynamic Properties table
     */
    $propfields = array('xar_prop_id'     => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0',
                                                  'increment'   => true,
                                                  'primary_key' => true),
                /* the name used to reference a particular property, e.g. in function calls and templates */
                    'xar_prop_name'       => array('type'        => 'varchar',
                                                  'size'        => 30,
                                                  'null'        => false,
                                                  'default'     => ''),
                /* the label used for display */
                    'xar_prop_label'      => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'null'        => false,
                                                  'default'     => ''),
                /* the object this property belong to */
                    'xar_prop_objectid'   => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
           /* we keep those 2 for efficiency, even though they're known via the object id as well */
                /* the module this property relates to */
                    'xar_prop_moduleid'   => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                /* the optional item type within this module */
                    'xar_prop_itemtype'   => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => '0'),
                /* the property type of this property */
                    'xar_prop_type'       => array('type'        => 'integer',
                                                  'null'        => false,
                                                  'default'     => NULL),
                /* the default value for this property */
                    'xar_prop_default'    => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'default'     => NULL),
                /* the data source for this property (dynamic data, static table, hook, user function, LDAP (?), file, ... */
                    'xar_prop_source'     => array('type'        => 'varchar',
                                                  'size'        => 254,
                                                  'null'        => false,
                                                  'default'     => 'dynamic_data'),
                /* is this property active ? (unused at the moment) */
                    'xar_prop_status'     => array('type'        => 'integer',
                                                  'size'        => 'tiny',
                                                  'null'        => false,
                                                  'default'     => '1'),
                /* the order of this property */
                    'xar_prop_order'      => array('type'        => 'integer',
                                                  'size'        => 'tiny',
                                                  'null'        => false,
                                                  'default'     => '0'),
                /* specific validation rules for this property (e.g. basedir, size, ...) */
                    'xar_prop_validation' => array('type'        => 'text')
              );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($dynamic_properties,$propfields);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;
    xarDB::importTables(array('dynamic_properties' => xarDB::$prefix . '_dynamic_properties'));
// TODO: evaluate efficiency of combined index vs. individual ones
    // the combination of module id + item type + property name *must* be unique !
    $query = xarDBCreateIndex($dynamic_properties,
                           array('name'   => 'i_' . xarDB::$prefix . '_dynprops_combo',
                                 'fields' => array('xar_prop_moduleid',
                                                   'xar_prop_itemtype',
                                                   'xar_prop_name'),
                                 'unique' => 'true'));
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    $query = xarDBCreateIndex($dynamic_properties,
                           array('name'   => 'i_' . xarDB::$prefix . '_dynprops_name',
                                 'fields' => array('xar_prop_name')));
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    $query = xarDBCreateIndex($dynamic_properties,
                           array('name'   => 'i_' . xarDB::$prefix . '_dynprops_objectid',
                                 'fields' => array('xar_prop_objectid')));
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    /**
     * Note : same remark as above - we can't use createproperty() here
     *        because dynamicdata doesn't know anything about properties yet :-)
     */

    // create default properties for dynamic data objects
    $properties = array(
        // 1 -> 9
        "'objectid','Id',$objectid[1],182,0,21,'','" . $dynamic_objects . ".xar_object_id',1,1,''",
        "'name','Name',$objectid[1],182,0,2,'','" . $dynamic_objects . ".xar_object_name',1,2,'a:5:{s:13:\"xv_min_length\";s:1:\"1\";s:13:\"xv_max_length\";s:2:\"30\";s:7:\"xv_size\";s:2:\"50\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'label','Label',$objectid[1],182,0,2,'','" . $dynamic_objects . ".xar_object_label',1,3,'a:5:{s:13:\"xv_min_length\";s:1:\"0\";s:13:\"xv_max_length\";s:3:\"254\";s:7:\"xv_size\";s:2:\"50\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'moduleid','Module',$objectid[1],182,0,19,'182','" . $dynamic_objects . ".xar_object_moduleid',1,4,'a:3:{s:7:\"xv_size\";s:1:\"1\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'itemtype','Item Type',$objectid[1],182,0,20,'0','" . $dynamic_objects . ".xar_object_itemtype',1,5,'a:4:{s:11:\"xv_itemtype\";s:1:\"0\";s:7:\"xv_size\";s:1:\"0\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'urlparam','URL Param',$objectid[1],182,0,2,'itemid','" . $dynamic_objects . ".xar_object_urlparam',1,6,'a:5:{s:13:\"xv_min_length\";s:1:\"0\";s:13:\"xv_max_length\";s:2:\"30\";s:7:\"xv_size\";s:2:\"50\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'maxid','Max Id',$objectid[1],182,0,15,'0','" . $dynamic_objects . ".xar_object_maxid',2,7,''",
        "'config','Config',$objectid[1],182,0,999,'','" . $dynamic_objects . ".xar_object_config',2,8,'a:12:{s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";s:12:\"xv_cansearch\";s:1:\"1\";s:10:\"xv_columns\";s:1:\"1\";s:7:\"xv_rows\";s:1:\"0\";s:11:\"xv_max_rows\";s:1:\"0\";s:12:\"xv_prop_type\";s:1:\"2\";s:12:\"xv_key_label\";s:3:\"Key\";s:14:\"xv_value_label\";s:4:\"Name\";s:15:\"xv_suffix_label\";s:3:\"Row\";s:20:\"xv_associative_array\";s:1:\"1\";s:12:\"xv_addremove\";s:1:\"2\";}'",
        "'isalias','Alias in short URLs',$objectid[1],182,0,14,'1','" . $dynamic_objects . ".xar_object_isalias',2,9,''",

        // 10 -> 21
        "'id','Id',$objectid[2],182,1,21,'','" . $dynamic_properties . ".xar_prop_id',1,1,''",
        "'name','Name',$objectid[2],182,1,2,'','" . $dynamic_properties . ".xar_prop_name',2,2,'a:5:{s:13:\"xv_min_length\";s:1:\"1\";s:13:\"xv_max_length\";s:2:\"30\";s:7:\"xv_size\";s:2:\"50\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'label','Label',$objectid[2],182,1,2,'','" . $dynamic_properties . ".xar_prop_label',1,3,'a:5:{s:13:\"xv_min_length\";s:1:\"0\";s:13:\"xv_max_length\";s:3:\"254\";s:7:\"xv_size\";s:2:\"50\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'objectid','Object',$objectid[2],182,1,24,'','" . $dynamic_properties . ".xar_prop_objectid',1,4,''",
        "'moduleid','Module',$objectid[2],182,1,19,'','" . $dynamic_properties . ".xar_prop_moduleid',2,5,''",
        "'itemtype','Item Type',$objectid[2],182,1,20,'','" . $dynamic_properties . ".xar_prop_itemtype',2,6,'a:4:{s:11:\"xv_itemtype\";s:1:\"0\";s:7:\"xv_size\";s:1:\"0\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'type','Property Type',$objectid[2],182,1,22,'','" . $dynamic_properties . ".xar_prop_type',1,7,''",
        "'default','Default',$objectid[2],182,1,3,'','" . $dynamic_properties . ".xar_prop_default',1,8,'a:4:{s:7:\"xv_rows\";s:1:\"2\";s:7:\"xv_cols\";s:2:\"35\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",
        "'source','Source',$objectid[2],182,1,23,'dynamic_data','" . $dynamic_properties . ".xar_prop_source',1,9,''",
        "'status','Status',$objectid[2],182,1,25,'1','" . $dynamic_properties . ".xar_prop_status',1,10,''",
        "'order','Order',$objectid[2],182,1,15,'0','" . $dynamic_properties . ".xar_prop_order',2,11,''",
        "'validation','Configuration',$objectid[2],182,1,4,'','" . $dynamic_properties . ".xar_prop_validation',3,12,'a:2:{s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";}'",

        // 22 -> 25
        "'id','Id',$objectid[3],182,2,21,'','dynamic_data',2,1,''",
        "'name','Name',$objectid[3],182,2,2,'please enter your name...','dynamic_data',1,2,'a:6:{s:13:\"xv_min_length\";s:1:\"1\";s:13:\"xv_max_length\";s:2:\"30\";s:7:\"xv_size\";s:2:\"50\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";s:12:\"xv_cansearch\";s:1:\"1\";}'",
        "'age','Age',$objectid[3],182,2,15,'','dynamic_data',1,3,'a:6:{s:6:\"xv_min\";s:1:\"0\";s:6:\"xv_max\";s:3:\"100\";s:7:\"xv_size\";s:2:\"10\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";s:12:\"xv_cansearch\";s:1:\"1\";}'",
        "'location','Location',$objectid[3],182,2,12,'','dynamic_data',2,4,'a:8:{s:7:\"xv_size\";s:2:\"50\";s:13:\"xv_allowempty\";s:1:\"1\";s:17:\"xv_display_layout\";s:7:\"default\";s:12:\"xv_cansearch\";s:1:\"1\";s:15:\"xv_image_source\";s:3:\"url\";s:11:\"xv_file_ext\";s:20:\"gif,jpg,jpeg,png,bmp\";s:19:\"xv_allow_duplicates\";s:1:\"0\";s:16:\"xv_max_file_size\";s:7:\"1000000\";}'"
        );
    $propid = array();
    $idx = 0;
    foreach ($properties as $property) {
        $nextId = $dbconn->GenId($dynamic_properties);
        $query = "INSERT INTO $dynamic_properties
                         (xar_prop_id, xar_prop_name, xar_prop_label, xar_prop_objectid, xar_prop_moduleid, xar_prop_itemtype, xar_prop_type, xar_prop_default, xar_prop_source, xar_prop_status, xar_prop_order, xar_prop_validation)
                  VALUES (?,$property)";
        $result = $dbconn->Execute($query,array($nextId));
        if (!isset($result)) return;
        $idx++;
        $propid[$idx] = $dbconn->PO_Insert_ID($dynamic_properties,'xar_prop_id');
    }


    /**
      * Dynamic Data table (= one of the possible data sources for properties)
      */
    $datafields = array('xar_dd_id'   => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0',
                                              'increment'   => true,
                                              'primary_key' => true),
                /* the property this dynamic data belongs to */
                    'xar_dd_propid'   => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
/* only needed if we go for freely extensible fields per item (not now)
                    'xar_dd_moduleid' => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
                    'xar_dd_itemtype' => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
*/
                /* the item id this dynamic data belongs to */
                    'xar_dd_itemid'   => array('type'        => 'integer',
                                              'null'        => false,
                                              'default'     => '0'),
                /* the value of this dynamic data */
                    'xar_dd_value'    => array('type'        => 'text', // or blob when storing binary data (but not for PostgreSQL - see bug 1324)
                                              'size'        => 'medium',
                                              'null'        => 'false')
              );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($dynamic_data,$datafields);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;
    xarDB::importTables(array('dynamic_data' => xarDB::$prefix . '_dynamic_data'));

    $query = xarDBCreateIndex($dynamic_data,
                           array('name'   => 'i_' . xarDB::$prefix . '_dyndata_propid',
                                 'fields' => array('xar_dd_propid')));
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    $query = xarDBCreateIndex($dynamic_data,
                           array('name'   => 'i_' . xarDB::$prefix . '_dyndata_itemid',
                                 'fields' => array('xar_dd_itemid')));
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    /**
     * Note : here we *could* start using the dynamicdata APIs, but since
     *        the module isn't activated yet, Xarigami doesn't like that either :-)
     */

    // we don't really need to create an object and properties for the dynamic data table

    // create some sample data for the sample object
    $dataentries = array(
        "$propid[22],1,'1'",
        "$propid[23],1,'Johnny'",
        "$propid[24],1,'32'",
        "$propid[25],1,'http://mikespub.net/xaraya/images/cuernos1.jpg'",

        "$propid[22],2,'2'",
        "$propid[23],2,'Nancy'",
        "$propid[24],2,'29'",
        "$propid[25],2,'http://mikespub.net/xaraya/images/agra1.jpg'",

        "$propid[22],3,'3'",
        "$propid[23],3,'Baby'",
        "$propid[24],3,'1'",
        "$propid[25],3,'http://mikespub.net/xaraya/images/sydney1.jpg'"
        );
    foreach ($dataentries as $dataentry) {
        $nextId = $dbconn->GenId($dynamic_data);
        $query = "INSERT INTO $dynamic_data
                         (xar_dd_id, xar_dd_propid, xar_dd_itemid, xar_dd_value)
                  VALUES (?,$dataentry)";
        $result = $dbconn->Execute($query,array($nextId));
        if (!isset($result)) return;
    }

    /**
      * Dynamic Relations table (= to keep track of relationships between objects)
      */
    $relationfields = array('xar_relation_id'    => array('type'        => 'integer',
                                                         'null'        => false,
                                                         'default'     => '0',
                                                         'increment'   => true,
                                                         'primary_key' => true),
// TODO:                /* more fields we need to add :) */
                            'xar_relation_todo'  => array('type'        => 'integer',
                                                         'null'        => false,
                                                         'default'     => '0')
                     );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($dynamic_relations,$relationfields);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;
    xarDB::importTables(array('dynamic_relations' => xarDB::$prefix . '_dynamic_relations'));


    // Add Dynamic Data Properties Definition Table
    if( !dynamicdata_createPropDefTable() ) return;


    /**
     * Set module variables
     */
    xarModSetVar('dynamicdata', 'SupportShortURLs', 1);

    /**
     * Register blocks
     */
    if (!xarMod::apiFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'dynamicdata',
                             'blockType'=> 'form'))) return;

    /**
     * Register hooks
     */
    // when a new module item is being specified
    if (!xarMod::registerHook('item', 'new', 'GUI',
                           'dynamicdata', 'admin', 'newhook')) {
        return false;
    }
    // when a module item is created (uses 'dd_*')
    if (!xarMod::registerHook('item', 'create', 'API',
                           'dynamicdata', 'admin', 'createhook')) {
        return false;
    }
    // when a module item is being modified (uses 'dd_*')
    if (!xarMod::registerHook('item', 'modify', 'GUI',
                           'dynamicdata', 'admin', 'modifyhook')) {
        return false;
    }
    // when a module item is updated (uses 'dd_*')
    if (!xarMod::registerHook('item', 'update', 'API',
                           'dynamicdata', 'admin', 'updatehook')) {
        return false;
    }
    // when a module item is deleted
    if (!xarMod::registerHook('item', 'delete', 'API',
                           'dynamicdata', 'admin', 'deletehook')) {
        return false;
    }
    // when a module configuration is being modified (uses 'dd_*')
    if (!xarMod::registerHook('module', 'modifyconfig', 'GUI',
                           'dynamicdata', 'admin', 'modifyconfighook')) {
        return false;
    }
    // when a module configuration is updated (uses 'dd_*')
    if (!xarMod::registerHook('module', 'updateconfig', 'API',
                           'dynamicdata', 'admin', 'updateconfighook')) {
        return false;
    }
    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    if (!xarMod::registerHook('module', 'remove', 'API',
                           'dynamicdata', 'admin', 'removehook')) {
        return false;
    }

//  Ideally, people should be able to use the dynamic fields in their
//  module templates as if they were 'normal' fields -> this means
//  adapting the get() function in the user API of the module, and/or
//  using some common data retrieval function (DD) in the future...

/*  display hook is now disabled by default - use the BL tags or APIs instead
    // when a module item is being displayed
    if (!xarMod::registerHook('item', 'display', 'GUI',
                           'dynamicdata', 'user', 'displayhook')) {
        return false;
    }
*/

    if (!xarMod::registerHook('item', 'search', 'GUI',
                           'dynamicdata', 'user', 'search')) {
        return false;
    }

    /**
     * Register BL tags
     */
// TODO: move this to some common place in Xarigami ?
    // Register BL user tags
    // output this property
    xarTplRegisterTag('dynamicdata', 'data-output',
                      array(),
                      'dynamicdata_userapi_handleOutputTag');
    // display this item
    xarTplRegisterTag('dynamicdata', 'data-display',
                      array(),
                      'dynamicdata_userapi_handleDisplayTag');
    // view a list of these items
    xarTplRegisterTag('dynamicdata', 'data-view',
                      array(),
                      'dynamicdata_userapi_handleViewTag');

    // Register BL admin tags
    // input field for this property
    xarTplRegisterTag('dynamicdata', 'data-input',
                      array(),
                      'dynamicdata_adminapi_handleInputTag');
    // input form for this item
    xarTplRegisterTag('dynamicdata', 'data-form',
                      array(),
                      'dynamicdata_adminapi_handleFormTag');
    // admin list for these items
    xarTplRegisterTag('dynamicdata', 'data-list',
                      array(),
                      'dynamicdata_adminapi_handleListTag');

    // Register BL item tags to get properties and values directly in the template
    // get properties for this item
    xarTplRegisterTag('dynamicdata', 'data-getitem',
                      array(),
                      'dynamicdata_userapi_handleGetItemTag');
    // get properties and item values for these items
    xarTplRegisterTag('dynamicdata', 'data-getitems',
                      array(),
                      'dynamicdata_userapi_handleGetItemsTag');

    // Register BL utility tags to avoid OO problems with the BL compiler
    // get label for this object or property
    xarTplRegisterTag('dynamicdata', 'data-label',
                      array(),
                      'dynamicdata_userapi_handleLabelTag');
    // get value or invoke method for this object or property
    xarTplRegisterTag('dynamicdata', 'data-object',
                      array(),
                      'dynamicdata_userapi_handleObjectTag');

    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * register(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('ViewDynamicData','All','dynamicdata','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditDynamicData','All','dynamicdata','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminDynamicData','All','dynamicdata','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewDynamicDataItems','All','dynamicdata','Item','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_READ');
    xarRegisterMask('EditDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_ADMIN');

    xarRegisterMask('ReadDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_READ');
    xarRegisterMask('EditDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_ADMIN');

    xarRegisterMask('ViewDynamicDataBlocks','All','dynamicdata','Block','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadDynamicDataBlock','All','dynamicdata','Block','All:All:All','ACCESS_READ');
   /*********************************************************************
    * Define instances for this module
    * Format is
    * setInstance(Module,Component,Query,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/

    $instances = array(
                       array('header' => 'external', // this keyword indicates an external "wizard"
                             'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
                             'limit'  => 0
                            )
                    );
    xarDefineInstance('dynamicdata','Item',$instances);

    $instances = array(
                       array('header' => 'external', // this keyword indicates an external "wizard"
                             'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
                             'limit'  => 0
                            )
                    );
    xarDefineInstance('dynamicdata','Field',$instances);

    xarMod::apiFunc('modules','admin','enablehooks',
                  array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));


    /* This init function brings our module to version 2.0.0, run the upgrades for the rest of the initialisation */

    return dynamicdata_upgrade('1.2.1');
}

/**
 * upgrade the dynamicdata module from an old version
 * This function can be called multiple times
 */
function dynamicdata_upgrade($oldVersion)
{


    // Upgrade dependent on old version number
    switch($oldVersion) {
    case '1.0':
        // Code to upgrade from version 1.0 goes here

        // Register BL item tags to get properties and values directly in the template
        // get properties for this item
        xarTplRegisterTag('dynamicdata', 'data-getitem',
                          array(),
                          'dynamicdata_userapi_handleGetItemTag');
        // get properties and item values for these items
        xarTplRegisterTag('dynamicdata', 'data-getitems',
                          array(),
                          'dynamicdata_userapi_handleGetItemsTag');

        // for the switch from blob to text of the xar_dd_value field, no upgrade is necessary for MySQL,
        // and no simple upgrade is possible for PostgreSQL
    case '1.1':
        // Fall through to next upgrade

    case '1.1.0':
        xarRemoveInstances('dynamicdata');
        $instances = array(
                           array('header' => 'external', // this keyword indicates an external "wizard"
                                 'query'  => xarModURL('dynamicdata', 'admin', 'privileges'),
                                 'limit'  => 0
                                )
                        );
        xarDefineInstance('dynamicdata','Field',$instances);


        // Fall through to next upgrade
    case '1.2.0':
        // Add Dynamic Data Properties Definition Table
        if( !dynamicdata_createPropDefTable() ) return;

        // Fall through to next upgrade
    case '1.2.1' :
            xarRegisterMask('SubmitDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_COMMENT');
            xarRegisterMask('SubmitDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_COMMENT');
    case '1.2.2' :
         xarRegisterMask('ModerateDynamicData','All','dynamicdata','All','All','ACCESS_MODERATE');
         xarRegisterMask('ModerateDynamicDataItem','All','dynamicdata','Item','All:All:All','ACCESS_MODERATE');
         xarRegisterMask('ModerateDynamicDataField','All','dynamicdata','Field','All:All:All','ACCESS_MODERATE');
         $sysobjects = 'a:4:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"4";i:3;s:1:"5";}';
         xarModSetVar('dynamicdata','systemobjects', $sysobjects);
         xarModSetVar('dynamicdata','itemsperpage', 20);
    case '1.2.3' :
        //upgrade to signify changes in all dynamic data configurations
        //upgrades in the installer upgrade checks and fixes
     case '1.4.0' :
        xarRegisterMask('DeleteDynamicData','All','dynamicdata','All','All','ACCESS_DELETE');
        xarModSetVar('dynamicdata','useritemsperpage', 0);
    case '1.4.1':

    case '2.0.0':
        // Code to upgrade from version 2.0.0 goes here
        break;
    }

    // Update successful
    return true;
}

/**
 * delete the dynamicdata module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 */
function dynamicdata_delete()
{

  //this module cannot be removed
  return false;

    /**
     * Drop tables
     */
    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();

    //Load Table Maintenance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_objects']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_properties']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_data']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_relations']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['dynamic_properties_def']);
    if (empty($query)) return; // throw back
    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    /**
     * Delete module variables
     */
    xarModDelVar('dynamicdata', 'SupportShortURLs');

    /**
     * Unregister blocks
     */
    if (!xarMod::apiFunc('blocks',
                       'admin',
                       'unregister_block_type',
                       array('modName'  => 'dynamicdata',
                             'blockType'=> 'form'))) return;

    /**
     * Unregister hooks
     */
    // Remove module hooks
    if (!xarMod::unregisterHook('item', 'new', 'GUI',
                             'dynamicdata', 'admin', 'newhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarMod::unregisterHook('item', 'create', 'API',
                             'dynamicdata', 'admin', 'createhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarMod::unregisterHook('item', 'modify', 'GUI',
                             'dynamicdata', 'admin', 'modifyhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarMod::unregisterHook('item', 'update', 'API',
                             'dynamicdata', 'admin', 'updatehook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarMod::unregisterHook('item', 'delete', 'API',
                             'dynamicdata', 'admin', 'deletehook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarMod::unregisterHook('module', 'modifyconfig', 'GUI',
                             'dynamicdata', 'admin', 'modifyconfighook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarMod::unregisterHook('module', 'updateconfig', 'API',
                             'dynamicdata', 'admin', 'updateconfighook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
    if (!xarMod::unregisterHook('module', 'remove', 'API',
                             'dynamicdata', 'admin', 'removehook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }

//  Ideally, people should be able to use the dynamic fields in their
//  module templates as if they were 'normal' fields -> this means
//  adapting the get() function in the user API of the module, and/or
//  using some common data retrieval function (DD) in the future...

/*  display hook is now disabled by default - use the BL tags or APIs instead
    if (!xarMod::unregisterHook('item', 'display', 'GUI',
                             'dynamicdata', 'user', 'displayhook')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }
*/

    if (!xarMod::unregisterHook('item', 'search', 'GUI',
                             'dynamicdata', 'user', 'search')) {
        xarSession::setVar('errormsg', xarML('Could not unregister hook'));
    }

    /**
     * Unregister BL tags
     */
// TODO: move this to some common place in Xarigami ?
    // Unregister BL tags
    xarTplUnregisterTag('data-input');
    xarTplUnregisterTag('data-output');
    xarTplUnregisterTag('data-form');

    xarTplUnregisterTag('data-display');
    xarTplUnregisterTag('data-list');
    xarTplUnregisterTag('data-view');

    xarTplUnregisterTag('data-getitem');
    xarTplUnregisterTag('data-getitems');

    xarTplUnregisterTag('data-label');
    xarTplUnregisterTag('data-object');

    // Remove Masks and Instances
    xarRemoveMasks('dynamicdata');
    xarRemoveInstances('dynamicdata');


    // Deletion successful
    return true;
}

function dynamicdata_createPropDefTable()
{
    /**
      * Dynamic Data Properties Definition Table
      */

    // Get existing DB info
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $dynamic_properties_def = $xartable['dynamic_properties_def'];

    //Load Table Maintenance API
    xarDBLoadTableMaintenanceAPI();


    $propdefs = array('xar_prop_id'   => array('type'        => 'integer',
                                               'null'        => false,
                                               'default'     => '0',
                                               'increment'   => true,
                                               'primary_key' => true),
                /* the name of this property */
                    'xar_prop_name'   => array('type'        => 'varchar',
                                               'size'        => 254,
                                               'default'     => NULL),
                /* the label of this property */
                    'xar_prop_label'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),
                /* this property's parent */
                    'xar_prop_parent'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),
                /* path to the file defining this property */
                    'xar_prop_filepath'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),
                /* name of the Class to be instantiated for this property */
                    'xar_prop_class'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),

                    'xar_prop_validation'   => array('type'        => 'text'),
                /* the source of this property */
                    'xar_prop_source'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),
                /* the semi-colon seperated list of file required to be present before this property is active */
                    'xar_prop_reqfiles'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),
                /* the semi-colon seperated list of modules required to be active before this property is active */
                    'xar_prop_reqmodules'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),
                /* the default args for this property -- serialized array */
                    'xar_prop_args'    => array('type'        => 'text',
                                              'size'        => 'medium',
                                              'null'        => 'false'),

                /*  */
                    'xar_prop_aliases'   => array('type'        => 'varchar',
                                              'size'        => 254,
                                              'default'     => NULL),
                /*  */
                    'xar_prop_format'   => array('type'        => 'integer',
                                              'default'     => '0')
              );

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarDBCreateTable($dynamic_properties_def,$propdefs);
        if (empty($query)) return false; // throw back
        $result = $dbconn->Execute($query);
        if (!isset($result)) return false;
        xarDB::importTables(array('dynamic_properties_def' => xarDB::$prefix . '_dynamic_properties_def'));
        $query = xarDBCreateIndex($dynamic_properties_def,
                           array('name'   => 'i_' . xarDB::$prefix . '_dynpropdef_mod',
                                 'fields' => array('xar_prop_reqmodules')));
        if (empty($query)) return; // throw back
        $result = $dbconn->Execute($query);
        if (!isset($result)) return;

        return true;
    }
?>
