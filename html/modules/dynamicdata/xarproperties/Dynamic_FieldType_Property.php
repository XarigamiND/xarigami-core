<?php
/**
 * Dynamic Select Property
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
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Class to handle field type property
 *
 * @package dynamicdata
 */
class Dynamic_FieldType_Property extends Dynamic_Select_Property
{
    public $id         = 22;
    public $name       = 'fieldtype';
    public $desc       = 'Field Type';
    public $reqmodules  = 'dynamicdata';

    function __construct($args)
    {
        parent::__construct($args);

        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->xv_firstline = isset($firstline)?$firstline: xarML('Please select');
        if( !isset($args['skipInit']) || ($args['skipInit'] != true) )
        {
            $this->options = $this->getOptions();
        }

    }
    public function getOptions()
    {
        $options = array();
        $proptypes = Dynamic_Property_Master::getPropertyTypes();
        if (!isset($proptypes)) {
            $proptypes = array();
        }
        foreach ($proptypes as $propid => $proptype) {
            $options[] = array('id' => $propid, 'name' => $proptype['label']);
        }
        if ($options) {
            uasort($options,array($this,'propertysort'));
        }
        $this->options = $options;
        $options = parent::getOptions();
        return $options;
    }
    /**
     * Get the base information for this property.
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args = array();
        //we don't really want options do we here?
        $validation = $this->getBaseValidationInfo();
        $baseInfo = array(
                              'id'          => 22,
                              'name'        => 'fieldtype',
                              'label'       => 'Field type',
                              'format'      => '22',
                              'validation'  => serialize($validation),
                              'source'      => '',
                              'dependancies'=> '',
                              'filepath'    => 'modules/dynamicdata/xarproperties',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'     => '',
                              'args'        => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

    function propertysort($a, $b)
    {
       return strnatcasecmp($a['name'], $b['name']);
    }

}

?>