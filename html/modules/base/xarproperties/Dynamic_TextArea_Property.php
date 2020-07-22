<?php
/**
 * Dynamic Textarea Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2004-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Property to show a text area
 *
 * @package modules
 * @subpackage Base module
 * @author mikespub <mikespub@xaraya.com>
 */
class Dynamic_TextArea_Property extends Dynamic_Property
{
    public $name       = 'textarea';
    public $desc       = 'Small Text Area';
    public $xv_classname = ''; // via GUI in property
    public $class = ''; // passed in from template
    public $defaultclass = ''; // some default class
    public $xv_rows = 2;
    public $xv_cols = 35;
    public $xv_maxlength = null;

    function __construct($args)
    {
        parent::__construct($args);

        $this->tplmodule = 'base';
        $this->template = 'textarea';
        $this->filepath   = 'modules/base/xarproperties';
        // check validation for allowed rows/cols (or values)
        if (!empty($args['rows'])) {
            $this->xv_rows =$args['rows'];
        }

    }

    function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return;
        if (!isset($value)) {
            $value = $this->value;
        }
        //backward compatibility
        if (!empty($value)) {
            if (isset($this->xv_other['maxlength']) && strlen(utf8_decode($value)) > $this->xv_other['maxlength']) {
                $this->invalid = xarML('#(1) text: must not be more than #(2) characters long', $this->name, $this->xv_other['maxlength']);
                $this->value = $value;
                return false;
            }
        }

        $this->value = $value;
        return true;
    }

    /**
     * Show the input for the textarea
     */
    function showInput(Array $data = array())
    {
        extract($data);

        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['value']    = isset($value) ? $value : $this->value;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['rows']     = !empty($rows) ? $rows : $this->xv_rows;
        $data['cols']     = !empty($cols) ? $cols : $this->xv_cols;
        //we allow GUI to override a template class
        $data['class']    = !empty($this->xv_classname) ? $this->xv_classname : (!empty($class) ?$class : NULL);
        $data['other'] = (!empty($this->xv_other)) ? $this->xv_other : '';

        $data['template'] = (isset($template) && !empty($template)) ? $template : 'textarea';
        return parent::showInput($data);

    }
    /*
     * jojo - mostly redundant but to be sure we prep the data for HTML display being a text area.
     */
    function showOutput(Array $data = array())
    {
         extract($data);

         if (isset($value)) {
            $data['value'] = xarVarPrepHTMLDisplay($value);
         } else {
            $data['value'] = xarVarPrepHTMLDisplay($this->value);
         }
        $data['template'] = (isset($template) && !empty($template)) ? $template : 'textarea';

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
           $parentvals = parent::getBaseValidationInfo();
            $validations = array('xv_rows'    =>  array('label'=>xarML('Rows'),
                                                            'description'=>xarML('Rows in text area'),
                                                            'propertyname'=>'integerbox',
                                                            'ignore_empty'  =>1,
                                                            'ctype'=>'display'
                                                          ),
                                    'xv_cols'    =>  array('label'=>xarML('Columns'),
                                                          'description'=>xarML('Columns in text area'),
                                                           'propertyname'=>'integerbox',
                                                            'ignore_empty'  =>1,
                                                            'ctype' =>'display'
                                                           ),

                                    'xv_min_length'    =>  array('label'=>xarML('Minimum length'),
                                                          'description'=>xarML('Minimum required length of this field'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                           'ctype'=>'validation'
                                                          ),
                                    'xv_max_length'    =>  array('label'=>xarML('Maximum length'),
                                                          'description'=>xarML('Maximum required length of this field'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                           'ctype'=>'validation'

                                                           ),
                                );
             $validationarray= array_merge($validations,$parentvals);

        }
        return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     */
    function getBasePropertyInfo()
    {
        $args = array();
        $validation = $this->getBaseValidationInfo();
        $validation = serialize($validation);
        $args['rows'] = 8;
        $aliases[] = array(
                            'id'         => 4,
                            'name'       => 'textarea_medium',
                            'label'      => 'Text area - medium',
                            'format'     => '4',
                            'validation' => $validation,
                            'source'     => '',
                            'dependancies' => '',
                            'filepath'    => 'modules/base/xarproperties',
                            'requiresmodule' => 'base',
                            'args' => serialize( $args ),

                            // ...
                           );

        $args['rows'] = 20;
        $aliases[] = array(
                              'id'         => 5,
                              'name'       => 'textarea_large',
                              'label'      => 'Text area - large',
                              'format'     => '5',
                              'validation' => $validation,
                            'source'     => '',
                            'dependancies' => '',
                            'filepath'    => 'modules/base/xarproperties',
                            'requiresmodule' => 'base',
                            'args' => serialize( $args ),
                            // ...
                           );

        $args['rows'] = 2;
        $baseInfo = array(
                            'id'         => 3,
                            'name'       => 'textarea_small',
                            'label'      => 'Text area - small',
                            'format'     => '3',
                            'validation' => $validation,
                            'source'     => '',
                            'dependancies' => '',
                            'filepath'    => 'modules/base/xarproperties',
                            'requiresmodule' => 'base',
                            'aliases' => $aliases,
                            'args' => serialize( $args ),
                            // ...
                           );
        return $baseInfo;
    }
}

?>