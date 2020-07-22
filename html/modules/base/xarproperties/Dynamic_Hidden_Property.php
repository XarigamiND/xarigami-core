<?php
/**
 * Hidden property
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
 * Class to handle hidden properties
 * @author mikespub <mikespub@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_Hidden_Property extends Dynamic_Property
{
    public $id         = 18;
    public $name       = 'hidden';
    public $desc       = 'Hidden';
    public $reqmodules = 'base';

 function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'hidden';
        $this->filepath   = 'modules/base/xarproperties';
    }

    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 18,
                              'name'       => 'hidden',
                              'label'      => 'Hidden',
                              'format'     => '18',
                             'validation' =>  serialize(array()),
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