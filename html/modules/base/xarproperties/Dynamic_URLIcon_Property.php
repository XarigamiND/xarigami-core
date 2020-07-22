<?php
/**
 * Dynamic URL Icon Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Include the base class
 *
 */
sys::import('modules.base.xarproperties.Dynamic_URL_Property');

/**
 * Handle the URLIcon property
 *
 * @package dynamicdata
 */
class Dynamic_URLIcon_Property extends Dynamic_URL_Property
{
    public $id         = 27;
    public $name       = 'urlicon';
    public $desc       = 'URL Icon';
    public $xv_icon_url = ''; //favicon will not display if we initialize with non-empty
    public $xv_usefavicon = false;
    public $xv_titletext = 'IM Icon';
    function __construct($args)
    {
        parent::__construct($args);
        $this->template = 'urlicon';
    }

  function showOutput(Array $data = array())
    {
        extract($data);
        if (empty($value)) $value = $this->value;
       if (!isset($link))  $link = '';

        if (!isset($usefavicon)) $usefavicon = $this->xv_usefavicon;
        if (!empty($value) && $value != 'http://' && empty($link)) {
            $link = xarVarPrepForDisplay($value);
        }
        if (empty($icon) || $icon==trim('http://')) {
            $icon = $this->xv_icon_url;

            if ($usefavicon == TRUE) {
                if (empty($icon) || $icon==trim('http://')) {
                    // We don't have a validated icon to display, use favicon
                    $icon = xarMod::apiFunc('base','user','getfavicon',
                                                  array('url' => $value));
                }
            }
            //still empty - fall back
            if (empty($icon) || $icon==trim('http://'))
            {
                // use the default system icon
                $icon = xarTplGetImage('icons/www-url.png','base');
            }
        }

        $data['template'] = isset($template)?$template:$this->template;
        $data['link'] = $link;
        $data['icon'] = $icon;
        $data['value']= $value;
         $data['title']= isset($title)?$title:$this->xv_titletext;
        return parent::showOutput($data);
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
            $validations = array('xv_icon_url' =>  array(  'label'=>xarML('Icon URL'),
                                                                'description'=>xarML('URL to icon for display'),
                                                                'propertyname'=>'url',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                  'xv_titletext' =>  array(  'label'=>xarML('Title and Alt text'),
                                                                'description'=>xarML('Alt text used for title and alt attributes in icon link'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                    'xv_usefavicon' =>   array(  'label'=>xarML('Use favicons'),
                                                                'description'=>xarML('The site favicon will be displayed if possible.'),
                                                                'propertyname'=>'checkbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition',
                                                                'configinfo'=>xarML('Can slow down page rendering while site favicon is being sourced')
                                                          ),
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
        $validations = $this->getBaseValidationInfo();
        $baseInfo = array(
                          'id'         => 27,
                          'name'       => 'urlicon',
                          'label'      => 'URL icon',
                          'format'     => '27',
                          'validation' => serialize($validations),
                          'source'     => '',
                          'dependancies' => '',
                          'filepath'    => 'modules/base/xarproperties',
                          'requiresmodule' => 'base',
                          'aliases' => '',
                          'args'         => '',
                          // ...
                         );
        return $baseInfo;
    }
}

?>