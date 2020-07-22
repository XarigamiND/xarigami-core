<?php
/**
 * Language List Property
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
 * @author mikespub <mikespub@xaraya.com>
*/
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * handle the language list property
 * @package dynamicdata
 */
class Dynamic_LanguageList_Property extends Dynamic_Select_Property
{
    public $id         = 36;
    public $name       = 'language';
    public $desc       = 'Language List';

    function __construct($args)
    {
        parent::__construct($args);
    }

    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }

        $list = xarMLSListSiteLocales();
        asort($list);
        foreach ($list as $locale) {
            $locale_data = xarMLSLoadLocaleData($locale);
            $name = $locale_data['/language/display'] . " (" . $locale_data['/country/display'] . ")";
            $options[] = array('id'   => $locale,
                               'name' => $name,
                                );
        }
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
                            'id'         => 36,
                            'name'       => 'language',
                            'label'      => 'Language list',
                            'format'     => '36',
                            'validation' => serialize($validations),
                            'source'     => '',
                            'dependancies' => '',
                            'filepath'    => 'modules/base/xarproperties',
                            'requiresmodule' => 'base',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>