<?php
/**
 * Dynamic Item Type property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
 /**
     * Possible formats
     *
     *   module
     *       show the list of itemtypes for that module via getitemtypes()
     *       E.g. "articles" = the list of publication types in articles
     *
     *   module.itemtype
     *       show the list of items for that module+itemtype via getitemlinks()
     *       E.g. "articles.1" = the list of articles in publication type 1 News Articles
     *
     *   module.itemtype:xarMod::apiFunc(...)
     *       show some list of "item types" for that module via xarMod::apiFunc(...)
     *       and use itemtype to retrieve individual items via getitemlinks()
     *       E.g. "articles.1:xarMod::apiFunc('articles','user','dropdownlist',array('ptid' => 1, 'where' => ...))"
     *       = some filtered list of articles in publication type 1 News Articles
     *
     *   An additional third parameter is supported, with a value of 0 or 1 (defaut 0)
     *   This parameter allows multiple publication types to be selected if set (1).
     *
     *   TODO: support 2nd API call to retrieve the item in case getitemlinks() isn't supported
     */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.Dynamic_Combo_Property');


/**
 * Handle the item type property
 *
 * @package dynamicdata
 */
class Dynamic_ItemType_Property extends Dynamic_Combo_Property
{
    public $id         = 20;
    public $name       = 'itemtype';
    public $desc       = 'Item Type';
    public $reqmodules  = 'dynamicdata';
    public $xv_module   = ''; // get itemtypes for this module with getitemtypes()
    public $xv_itemtype = 0; // get items for this module+itemtype with getitemlinks()
    public $xv_firstline = '';


    function __construct($args)
    {
        parent::__construct($args);
         $this->filepath   = 'modules/dynamicdata/xarproperties';
         //parent sets first line, let's initialise it empty here
         $this->xv_firstline = '';

    }

    function showInput(Array $data= array())
    {
        if (!empty($data['module'])) $this->xv_module = $data['module'];
        if (!empty($data['itemtype'])) $this->xv_itemtype = $data['itemtype'];


        if (empty($args['template'])) {
            $args['template'] = 'itemtype';
        }

        return parent::showInput($data);
    }



    /**
     * Retrieve the list of itemtype options or a single option.
     */
    function getOptions()
    {
       $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }

        if (empty($this->xv_module)) return array();

        if (is_numeric($this->xv_module)) {
            // we should have a regid here, if we don't get the module name
            $this->xv_module = xarMod::getName($this->xv_module);
        }
        if (empty($this->xv_itemtype)) {
            // we're interested in the module itemtypes (= default behaviour)
            try {
                $itemtypes = xarMod::apiFunc($this->xv_module,'user','getitemtypes');
                if (!empty($itemtypes)) {
                    foreach ($itemtypes as $typeid => $typeinfo) {
                        if (isset($typeid) && isset($typeinfo['label'])) {
                            $options[] = array('id' => $typeid, 'name' => $typeinfo['label']);
                        }
                    }
                }
            } catch (Exception $e) {}

        } elseif (empty($this->xv_func)) {
            // we're interested in the items for module+itemtype
            try {
                $itemlinks = xarMod::apiFunc($this->xv_module,'user','getitemlinks',
                                           array('itemtype' => $this->itemtype,
                                                 'itemids'  => null));
                if (!empty($itemlinks)) {
                    foreach ($itemlinks as $itemid => $linkinfo) {
                        if (isset($itemid) && isset($linkinfo['label'])) {
                            $options[] = array('id' => $itemid, 'name' => $linkinfo['label']);
                        }
                    }
                }
            } catch (Exception $e) {}

        } else {
            // we have some specific function to retrieve the items here
            try {
                eval('$items = ' . $this->func .';');
                if (isset($items) && count($items) > 0) {
                    foreach ($items as $id => $name) {
                        // skip empty items from e.g. dropdownlist() API
                        if (empty($id) && empty($name)) continue;
                        $options[] = array('id' => $id, 'name' => $name);
                    }
                }
            } catch (Exception $e) {}
        }

        return $options;
    }

    function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvals = parent::getBaseValidationInfo();


            $validations = array('xv_module'   =>  array(  'label'=>xarML('Module'),
                                                                'description'=>xarML('Module to check for itemtypes'),
                                                                'propertyname'=>'module',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=> 'definition',
                                                                 'propargs' => array('xv_firstline'=>xarML('Select (if applicable)')
                                                                                    ),
                                                          ),
                                    'xv_itemtype'   =>  array(  'label'=>xarML('Itemtype'),
                                                                'description'=>xarML('Itemtype'),
                                                                'propertyname'=>'integerbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=> 'definition'
                                                          )

                                );
            $validationarray= array_merge($validations,$parentvals);
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
        $validations = $this->getBaseValidationInfo();
        $baseInfo = array(
            'id'         => 20,
            'name'       => 'itemtype',
            'label'      => 'Item type',
            'format'     => '20',
            'validation' =>  serialize($validations),
            'source'      => '',
            'filepath'    => 'modules/dynamicdata/xarproperties',
            'dependancies'   => '',
            'requiresmodule' => 'dynamicdata',
            'aliases'        => '',
            'args'           => serialize($args),
        );
        return $baseInfo;
     }

}
?>