<?php
/**
 * Date Format Property
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
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Class for the date format property
 *
 * @package dynamicdata
 */
class Dynamic_DateFormat_Property extends Dynamic_Select_Property
{
    public $id         = 33;
    public $name       = 'dateformat';
    public $desc       = 'Date Format';

    function __construct($args)
    {
        parent::__construct($args);
    }
   /**
     * Get Options
     *
     * Get a list of date formats
     */
    function getOptions()
    {
        $options = $this->getFirstline();
        if (isset($this->options) && count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }

        $options = array(array('id' => '%m/%d/%Y %H:%M:%S', 'name' => xarML('12/31/2004 24:00:00')),
                               array('id' => '%d/%m/%Y %H:%M:%S', 'name' => xarML('31/12/2004 24:00:00')),
                               array('id' => '%Y/%m/%d %H:%M:%S', 'name' => xarML('2004/12/31 24:00:00')),
                               array('id' => '%d %m %Y %H:%M',    'name' => xarML('31 12 2004 24:00')),
                               array('id' => '%b %d %H:%M:%S',    'name' => xarML('12 31 24:00:00')),
                              );

        return $options;
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
                              'id'         => 33,
                              'name'       => 'dateformat',
                              'label'      => 'Date format',
                              'format'     => '33',
                              'validation' => serialize($validations),
                              'filepath'    => 'modules/base/xarproperties',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}
?>
