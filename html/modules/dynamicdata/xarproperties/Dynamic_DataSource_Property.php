<?php
/**
 * Dynamic Data source Property
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
 * Class for data source property
 *
 * @package dynamicdata
 */
class Dynamic_DataSource_Property extends Dynamic_Select_Property
{
    public $id         = 23;
    public $name       = 'datasource';
    public $desc       = 'Data Source';
    public $reqmodules  = 'dynamicdata';

    function __construct($args)
    {

        parent::__construct($args);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        if (!isset($this->options) || count($this->options) == 0) {
            $datastoreMaster = new Dynamic_DataStore_Master($args);
            $sources = $datastoreMaster->getDataSources($args);
            if (!isset($sources)) {
                $sources = array();
            }
            foreach ($sources as $source) {
                $this->options[] = array('id' => $source, 'name' => $source);
            }
        }
        // allow values other than those in the options
        $this->override = true;
    }

    // default methods from Dynamic_Select_Property
    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 23,
                              'name'       => 'datasource',
                              'label'      => 'Data source',
                              'format'     => '23',
                              'validation' => serialize(array()),
                              'source'         => '',
                              'dependancies'   => '',
                              'filepath'    => 'modules/dynamicdata/xarproperties',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
