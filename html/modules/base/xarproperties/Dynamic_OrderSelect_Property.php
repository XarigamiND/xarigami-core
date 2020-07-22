<?php
/**
 * OrderSelect Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * handle the orderselect property
 * @author Dracos <dracos@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_OrderSelect_Property extends Dynamic_Select_Property
{
    public $id   = 50;
    public $name = 'orderselect';
    public $desc = 'Order Select';

    public $xv_order        = null;
    public $xv_size        = null;

    public $order = null;

    function __construct($args)
    {
        parent::__construct($args);
        $this->template  = 'orderselect';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = 'dd_'.$this->id ;

        list($found, $value) = $this->fetchValue($name . '_hidden');
        if (!$found) return false;

        return $this->validateValue($value);

    }

    public function validateValue($value = null)
    {
       if (!isset($value)) {
            $value = $this->value;
        }

        $tmp = array();
        if (!isset($options)) $options = $this->getOptions();

        if (empty($value) || (!is_array($value) && (strstr($value, ';') === false))) {
            foreach ($options as $k => $v) {
                $tmp[] = $v['id'];
            }
         } elseif (!is_array($value)) {
            $tmp = explode(';', $value);
        }
        $validlist = array();

        foreach ($options as $option) {
            array_push($validlist, $option['id']);
        }

        if(count(array_diff($validlist, $tmp)) != 0) {
            $this->invalid = xarML('value');
            $this->value = null;
            $ret =  false;
        } else {
            $this->value = implode(';', $tmp);
            $ret =  true;
        }
        return $ret;
    }

     public function showInput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        $order = $value;
        if (is_array($value)) {
            //we want it imploded
            $value = implode(';',$value);
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->getOptions();
        }
        $tmpopts = array();
        if (empty($value) || !is_array($value)) {

            if  (strstr($value, ';') === false) {
                $tmp = array();
                foreach ($options as $k => $v) {
                    $tmp[] = $v['id'];
                }
                $value = implode(';', $tmp);
           } elseif (!is_array($value)) {
                $tmpval = explode(';', $value);
                $tmpopts = array();
                foreach($tmpval as $v) {
                    foreach($options as $k) {
                        if($k['id'] == $v) {
                            $tmpopts[] = $k;
                            continue;
                        }
                    }
                }
                $options = $tmpopts;
            }
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['value']  = $value;
        $data['name']   = $name;
        $data['id']     = $id;
        $data['options']= $options;
        $data['size']=  !isset($size)?$this->xv_size:$size;
        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        $data['template'] = !isset($template) ?$this->template:$template;
         return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options)) {
            $options = $this->getOptions();
        }
         if (empty($value) || (!is_array($value) && (strstr($value, ';') === false))) {
            $tmpval = array();
            foreach ($options as $k => $v) {
                $tmpval[] = $v['id'];
            }
            $value = implode(';', $tmpval);
        } elseif (!is_array($value)) {
            $tmpval = explode(';', $value);
            $tmpopts = array();
            foreach($tmpval as $v) {
                foreach($options as $k) {
                    if($k['id'] == $v) {
                        $tmpopts[] = $k;
                        continue;
                    }
                }
            }
            $options = $tmpopts;
        }

        $data['value']= $tmpval;
        $data['options']= $options;
        $data['template'] = isset($template)?$template:$this->template;
        return parent::showOutput($data);
    }


    /**
     * Get the base information for this property.
     *
     *
     * @return array Base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args = array();
        $validation = $this->getBaseValidationInfo();

         $baseInfo = array(
                            'id'         => 50,
                            'name'       => 'orderselect',
                            'label'      => 'Order select',
                            'format'     => '50',
                            'validation' => serialize($validation),
                            'source'     => '',
                            'filepath'    => 'modules/base/xarproperties',
                            'dependancies' => '',
                            'requiresmodule' => 'base',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }
}
?>