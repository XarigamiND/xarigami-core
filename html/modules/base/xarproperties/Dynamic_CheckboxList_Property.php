<?php
/**
 * Checkbox List Property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

sys::import('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * Class to handle check box list property
 * @package dynamicdata
 */
class Dynamic_CheckboxList_Property extends Dynamic_Select_Property
{
    public $id         = 1115;
    public $name       = 'checkboxlist';
    public $desc       = 'Checkbox List';
    public $xv_displaycolumns = 3;
    public $xv_displaydelimiter = ',';
    public $template = 'checkboxlist';
    public $xv_display_layout = 'default';
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        //backward compatibility with checkboxmask property now an alias of checkboxlist
        $this->template = isset($args['template']) && !empty($args['template'])?$args['template'] :'checkboxlist';
    }
  public function validateValue($value = null)
    {
        if (!isset($value)) {
            $this->value = 0;

        } elseif (is_array($value) ) {
            $this->value = implode ( ',', $value);
        } else {
            $this->value = $value;
        }

        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);

         if (!isset($value))
        {
            $data['value'] = $this->value;
        } else {
            $data['value'] = $value;
        }

        if (empty($data['value']) ) {
            $data['value'] = array();
        } elseif ( !is_array($data['value']) && is_string($data['value']) ) {
            $data['value'] = explode( ',', $data['value'] );
        } else {
            $data['value'] = $this->getValue();
        }

        if (isset($options)) $this->xv_optionlist = $options;
        $data['layout']     = isset($layout) && !empty($layout)?$layout: $this->xv_display_layout;
        $data['columns']    = isset($displaycolumns) && !empty($diplaycolumns) ? $displaycolumns : $this->xv_displaycolumns;//let tpl decide
        $this->xv_displaycolumns = $data['columns'];
        $template = (isset($template) && empty($template)) ? $template: 'checkboxlist';
        $data['template'] = $template;

        return parent::showInput($data);
    }
    /**
     * Show the output for this property.
     *
     * The output is a joined string, shown in the template
     * @return mixed template with string "value1,value2"
     */
    function showOutput(Array $data = array())
    {
         extract($data);

         $data = array();
        if (!isset($value))
        {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = explode(',',$value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        if (!isset($options)) {
            $options = $this->getOptions();
        }

        $data['value']= $value;
        $data['options']= $options;
        $data['displaydelimiter']= isset($displaydelimiter)?$displaydelimiter:$this->xv_displaydelimiter;
        // allow template override by child classes (or in BL tags/API calls)
        $template = isset($template)&& !empty($template)?$template:$this->template;
        $data['template'] = $template;
        return xarTplProperty('base', $template, 'showoutput', $data);
    }


 /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvalidations = parent::getBaseValidationInfo();

            $validations= array('xv_displaycolumns'    =>  array('label'=>xarML('Columns'),
                                                                    'description'=>xarML('Number of columns for the checkbox list display'),
                                                                    'propertyname'=>'textbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'display'
                                                                  ),

                                    'xv_displaydelimiter'    =>  array('label'=>xarML('Delimiter'),
                                                                    'description'=>xarML('Character to use for separation of options in display'),
                                                                    'propertyname'=>'textbox',
                                                                    'ignore_empty'  =>1,
                                                                    'ctype'=>'display'
                                                                     )
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
    public function getBasePropertyInfo()
    {

        $args = array();
        $validation = $this->getBaseValidationInfo();
        $validation = serialize($validation);
        $args['template'] = 'checkboxmask';
        $aliases[] = array(
                            'id'         => 1114,
                            'name'       => 'checkboxmask',
                            'label'      => 'Checkbox mask',
                            'format'     => '1114',
                            'validation' => $validation,
                            'filepath'    => 'modules/base/xarproperties',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => 'base',
                            'args' => serialize( $args ),

                            // ...
                           );
        $args = array();
        $baseInfo = array(
                        'id'         => 1115,
                        'name'       => 'checkboxlist',
                        'label'      => 'Checkbox List',
                        'format'     => '1115',
                        'validation' => $validation,
                        'source'         => '',
                        'dependancies'   => '',
                        'requiresmodule' => 'base',
                       'filepath'    => 'modules/base/xarproperties',
                        'aliases'        => $aliases,
                       'args'           => serialize($args),
                        // ...
                       );
        return $baseInfo;
    }
}
?>