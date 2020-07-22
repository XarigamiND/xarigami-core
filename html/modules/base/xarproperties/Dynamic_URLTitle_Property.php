<?php
/**
 * Dynamic URL Title Property
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
sys::import('modules.base.xarproperties.Dynamic_URL_Property');

/**
 * handle the URL + Title property
 *
 * @package dynamicdata
 *
 */
class Dynamic_URLTitle_Property extends Dynamic_URL_Property
{
    public $id         = 41;
    public $name       = 'urltitle';
    public $desc       = 'URL + Title';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'urltitle';
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_array($value)) {
                if (isset($value['title'])) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }
                if (isset($value['link'])) {
                    $link = $value['link'];
                } else {
                    $link = '';
                }
                // Make sure $value['title'] is set and has a length > 0
                if (strlen(trim($title))) {
                    $title = $value['title'];
                } else {
                    $title = '';
                }
                //check the link
                if (!parent::validateValue($link)) return false;
                $value = array('link' => $link, 'title' => $title);
                $this->value = serialize($value);
            } else {
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    function showInput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) {
            $value = $this->value;
        }
        //get the link and title info
        if (!empty($value)) {
            if (is_array($value)) {
                if (isset($value['link'])) {
                    $link = $value['link'];
                }
                if (isset($value['title'])) {
                    $title = $value['title'];
                }
            } elseif (is_string($value) && substr($value,0,2) == 'a:') { //serialized array
                $newval = unserialize($value);
                if (isset($newval['link'])) {
                    $link = $newval['link'];
                }
                if (isset($newval['title'])) {
                    $title = $newval['title'];
                }
            }
        }
        if (empty($link)) {
            $link = 'http://';
        }
        if (empty($title)) {
            $title = '';
        }

        $data['title']    = xarVarPrepForDisplay($title);
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['link']     = xarVarPrepForDisplay($link);

        $data['template'] = isset($template)?$template:$this->template;
        return parent::showInput($data);
    }

    function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $returndata= '';
        }
        if (is_array($value)) {
            if (isset($value['link'])) {
                $link = $value['link'];
            }
            if (isset($value['title'])) {
                $title = $value['title'];
            }
        } elseif (is_string($value) && substr($value,0,2) == 'a:') {
            $newval = unserialize($value);
            if (isset($newval['link'])) {
                $link = $newval['link'];
            }
            if (isset($newval['title'])) {
                $title = $newval['title'];
            }
        }

        if (!empty($title)) $title = xarVarPrepForDisplay($title);
        if (!empty($link)) $link = xarVarPrepForDisplay($link);



        $data['value']   = $this->value;
        $data['link']    = (!empty($link) && $link != 'http://') ? $link : '';
        $data['title']   = (!empty($title)) ? $title : '';

        $template="";
        return parent::showOutput($data);
    }

    /**
     * Get the base information for this property.
     *
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
        $validation = parent::getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 41,
                            'name'       => 'urltitle',
                            'label'      => 'URL + title',
                            'format'     => '41',
                            'validation' => serialize($validation),
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