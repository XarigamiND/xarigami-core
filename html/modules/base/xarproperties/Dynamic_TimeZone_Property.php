<?php
/**
 * Dynamic TimeZone Property
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
sys::import('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * handle the timezone property
 */
class Dynamic_TimeZone_Property extends Dynamic_Select_Property
{
    public $id         = 32;
    public $name       = 'timezone';
    public $desc       = 'Time Zone';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'timezone';
    }

    public function validateValue($value = null)
    {
         if (!parent::validateValue($value)) return false;

        if (empty($value)) {
            // no timezone selected
            return true;

        } elseif (is_numeric($value)) {
            // keep old numeric format
            return true;

        } elseif (is_string($value)) {
            // check what kind of string we have here
            $out = @unserialize($value);
            if ($out !== false) {
                // we have a serialized value
                if (empty($out['timezone'])) {
                    $this->value = '';
                    return true;
                }
                $timezone = $out['timezone'];
            } else {
                // we have a text value
                $timezone = $value;
            }

        } elseif (is_array($value)) {
            if (empty($value['timezone'])) {
                $this->value = '';
                return true;
            }
            $timezone = $value['timezone'];
        }

        // check if the timezone exists
        $info = xarMod::apiFunc('base','user','timezones',
                              array('timezone' => $timezone));
        if (empty($info)) {
            $this->invalid = xarML('timezone');
            $this->value = null;
            return false;
        }
        list($hours,$minutes) = explode(':',$info[0]);
        // tz offset is in hours
        $offset = (float) $hours + (float) $minutes / 60;
        // save a serialized array with timezone and offset
        $value = array('timezone' => $timezone,
                       'offset'   => $offset);
        $this->value = serialize($value);
        return true;
    }

      /**
     * Show the chosen timezone as a text
     */
    public function showOutput(Array $data = array())
    {
        extract($data);
       if (!isset($value)) $value = isset($this->value)?$this->value:'UTC';
       if (!is_array($value) && substr($value, 0,2) == 'a:') {
            $temp = unserialize($value);
            $value = isset($temp['timezone'])? $temp['timezone']:'';
        }
        $zone = new DateTimeZone($value);
        $datetime = new DateTime('now',$zone);
        $offset = $zone->getOffset($datetime)/3600;
        $data['offset'] = '';
         if (isset($offset)) {
            $hours = intval($offset);
            if ($hours != $offset) {
                $minutes = abs($offset - $hours) * 60;
            } else {
                $minutes = 0;
            }
            if ($hours > 0) {
                $data['offset'] = sprintf("+%d:%02d",$hours,$minutes);
            } else {
                $data['offset'] = sprintf("%d:%02d",$hours,$minutes);
            }
        }
        $data['timezone'] = $value; //backward compatibility for templates
        $data['value'] = $value;
        $data['template'] = isset($template)?$template:$this->template;
        return parent::showOutput($data);
    }

    function getOptions()
    {
       if (isset($this->options) && count($this->options) > 0) {
            return $this->options;
        }
        $zones = DateTimeZone::listIdentifiers();
        $options = array();
        foreach ($zones as $name) {
            $zone = new DateTimeZone($name);
            $datetime = new DateTime('now',$zone);
            $options[] = array('id' => $name, 'name' => $name, 'offset' => $zone->getOffset($datetime));
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
        $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                           'id'         => 32,
                           'name'       => 'timezone',
                           'label'      => 'Time zone',
                           'format'     => '32',
                           'validation' => serialize($validations),
                           'source'     => '',
                           'filepath'    => 'modules/base/xarproperties',
                           'dependancies' => '',
                           'requiresmodule' => 'base',
                           'aliases' => '',
                           'args'         => '',
                           );
        return $baseInfo;
     }
}
?>
