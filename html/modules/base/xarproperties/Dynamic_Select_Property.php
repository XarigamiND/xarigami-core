<?php
/**
 * Dynamic Select property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * @package modules
 * @subpackage Base module
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.dynamicdata.class.properties');

class Dynamic_Select_Property extends Dynamic_Property
{
    public $name        = 'dropdown';
    public $desc        = 'Dropdown List';
    public $xv_itemfunc;
    public $options;
    public $layout      = 'default';
    public $module      = 'base';
    public $xv_firstline   = null;
    public $xv_func        = null;
    public $xv_file        = null;
    public $xv_size        = 1; //number of rows to show
    public $xv_displayrows = 0; //number of rows to allow before displaying a textbox
    public $xv_optionlist  = null;
    public $xv_override    = false; // allow values other than those in the options

    function __construct($args)
    {
        parent::__construct($args);
        $this->template  = 'dropdown';
        $this->tplmodule = 'base';
        $this->filepath   = 'modules/base/xarproperties';

    }
    //<jojo> - we need a special case here
    // if no option is selected, we should treat it as 'empty' in this special case
    //while not strictly true from a usability pov it is what would be expected for 'allow empty'
     public function checkInput($name = '', $value = null)
    {
        //store the fieldname in case it is required by other methods/functions (e.g. file uploads)
        $name = empty($name) ? 'dd_'.$this->id : $name;
          $this->fieldname = $name;
        $namelabel = isset($this->label)?$this->label:$this->name;
        $this->invalid = '';
         if (!isset($value)) {
             if (isset($this->label) && !empty($this->label)) {
                $namelabel = $this->label;
            } else {
                $namelabel = $this->name;
            }
            list($found,$value) = $this->fetchValue($name);
            if (!$found && $this->xv_allowempty !=1) {
                $this->invalid = xarML("You must select an option for '#(1)'", $namelabel);
                $this->objectref->missingfields[] = $namelabel;//$this->name;
                return null;
            }
        }
          return $this->validateValue($value);
    }
    /**
     * Validate the value
     * @return bool true when value is valid, false when it is not
     */
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;
        $options = $this->getOptions();

            $found = false;
            foreach ($options as $option=>$info) {
                if ($info['id'] == $value) {
                    $value = $info['id'];
                    $this->value = $value;
                    $found = true;
                    break;
                } elseif ($option == $value) { //cater for alternative format

                    $this->value = $value;
                    $found = true;
                    break;
                }
            }

        if (!$found) $value = null;
        if ($this->xv_allowempty  && (empty($value) || is_null($value))) {
            $isvalid = true;
            return true;
        }
        // check if this option really exists
        $isvalid = $this->getOption(true);

        if ($isvalid) {
            return true;
        }

        // check if we allow values other than those in the options
        if ($this->xv_override) {
            return true;
        }
        if (!empty($this->xv_override_invalid)) {
            $this->invalid = xarML($this->xv_override_invalid);
        } else {
            $labelname = !empty($this->label)?$this->label:$this->name;
            $thevalue = $this->value;
            $thevalue = $this->showOutput(array($thevalue));
            $this->invalid = xarML("Selection not allowed: '#(1)' for '#(2)'", $thevalue, $labelname);
        }

        $this->value = null;
        return false;
    }

    /**
     * Show the input form for the property
     * @return mixed. This function calls the template function to show the input form
     */
    public function showInput(Array $data = array())
    {
        extract($data);

        $data['value'] = !isset($value) ? $this->value : $value;
        $data['override'] = !isset($override) ? $this->xv_override : $override;
        if (!isset($options)) {
            if(isset($data['validation']) && !empty($data['validation']) ) {
                //is this legacy?
                $check = false;
                if (!is_array($data['validation'])) {
                    try {
                        $check = @unserialize($data['validation']);
                    } catch (Exception $e) {
                        //do nothing
                    }
                }
                $serialized =  ($check===false) && ($data['validation'] != serialize(false)) ? false : true;
                if (!$serialized) {
                    $this->parseLegacyValidation(array('validation'=>$data['validation']));
                } else {

                 //get the info in config validation if it is available
                    $this->parseValidation($this->validation);
                    unset($validation);
                }
            }
            //template overrides
            if (isset($func))       $this->xv_func = $func;
            if (isset($file))       $this->xv_file = $file;
            if (isset($firstline))  $this->xv_firstline = $firstline;
           // Finally generate the options

            $data['options'] = $this->getOptions();

        } else {
            // If a firstline was defined add it in
            if (isset($firstline)) $this->xv_firstline = $firstline;
            if (!isset($data['options'])) $data['options'] = array();
            //first check the options are normalized
            if (is_array($options)) {
                $test = end($options);
                if (!is_array($test)) {
                    $normalizedoptions = array();
                    foreach ($options as $key => $value)
                    {
                        $normalizedoptions[] = array('id' => $key, 'name' => $value);
                    }
                    $data['options'] = $normalizedoptions;
                }
                //add the firstline -
                //jojo - this will already be added in if coming form filelist or image list and options set (usually);
                $data['options'] = array_merge($this->getFirstline(),$data['options']);
            }
        }

        //check what we have so far
        if (!is_array($data['options'])) {
            throw new Exception(xarML('Dropdown options do not have the correct form'));
        }
        //recheck normalization for options from other sources
        if (!is_array(end($data['options']))) {
            $normalizedoptions = array();
            foreach ($data['options'] as $key => $value)
                $normalizedoptions[] = array('id' => $key, 'name' => $value);
            $data['options'] = $normalizedoptions;
        }

        // check if we need to add the current value to the options
        if (!empty($data['value']) && $this->xv_override) {
          $found = false;
            foreach ($data['options'] as $option) {
                if ($option['id'] == $data['value']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data['options'][] = array('id' => $data['value'], 'name' => $data['value']);
            }
        }

        $data['size'] = isset($size) && !empty($size)? $size: $this->xv_size;//let tpl decide
        $data['tplmodule']  = !isset($tplmodule) ? $this->tplmodule : $tplmodule;
        $data['class']      = !isset($class) ?  $this->class : $class;
        // allow template override by child classes (or in BL tags/API calls)
        $data['template'] = (!isset($template) || empty($template)) ? $this->template: $template;

        return parent::showInput($data);
     }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (isset($value)) {
            $this->value = $value;
        }
        $data['value'] = $this->value;
        // get the option corresponding to this value
        $result = $this->getOption();
         // if we prep this the entities don't render correctly!
        // for now prep in the template and remove if necessary
        if (!isset($option)) { //option is passed in
            if (!empty($link)) {
                $data['option'] = array('id'=> $this->value, 'name'=> $result, 'link' => $link);
            } else {
                $data['option'] = array('id' => $this->value, 'name' => $result);
            }
        }
        // allow template override by child classes (or in BL tags/API calls)
        if (!isset($template) || empty($template)) {
            $data['template'] = 'dropdown';
        }
        return parent::showOutput($data);
    }

    /**
     * Retrieve the list of options on demand
     */
    function getOptions()
    {
        $firstline = $this->getFirstline();
        if (is_array($firstline)) $options = $firstline;
        if (isset($this->options) && count($this->options) > 0 ) {
            if (!empty($firstline) && is_array($firstline)) $this->options =array_merge($firstline,$this->options);
            return $this->options;
        } else {
          //  if (!isset($this->options)) $this->options = array();
        }

        if (!empty($this->xv_file)) $filepath = sys::code() . $this->xv_file;

        if (!empty($this->xv_func)) {
            // we have some specific function to retrieve the options here
            @eval('$items = ' . $this->xv_func .';');

            if (!isset($items) || !is_array($items)) $items = array();
            if (isset($items[0]) && is_array($items[0])) {
                foreach($items as $id => $name) {
                    $options[] = array('id' => $name['id'], 'name' => $name['name']);
                }
            } else {
                foreach ($items as $id => $name) {
                    $options[] = array('id' => $id, 'name' => $name);
                }
            }
                unset($items);
        } elseif (!empty($filepath) && file_exists($filepath)) {
            $parts = pathinfo($filepath);
            if ($parts['extension'] =='xml'){
                $data = implode("", file($filepath));
                $parser = xml_parser_create( 'UTF-8' );
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
                xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
                xml_parse_into_struct($parser, $data, $value, $index);
                xml_parser_free($parser);
                $limit = count($index['id']);
                while (count($index['id'])) {
                    $options[] = array('id' => $value[array_shift($index['id'])]['value'], 'name' => $value[array_shift($index['name'])]['value']);
                }
            } else {
                $fileLines = file($filepath);
                foreach ($fileLines as $option)
                {
                    // allow escaping \, for values that need a comma
                    if (preg_match('/(?<!\\\),/', $option)) {
                        // if the option contains a , we'll assume it's an id,name combination
                        list($id,$name) = preg_split('/(?<!\\\),/', $option);
                        $id = strtr($id,array('\,' => ','));
                        $name = strtr($name,array('\,' => ','));
                        array_push($options, array('id' => $id, 'name' => $name));
                    } else {
                        // otherwise we'll use the option for both id and name
                        $option = strtr($option,array('\,' => ','));
                        array_push($options, array('id' => $option, 'name' => $option));
                    }
                }
            }
        } elseif (!empty($this->xv_optionlist)) {
            $options = !isset($options)?array():$options;
            $lines = explode(';',$this->xv_optionlist);
            // remove the last (empty) element if empty!
            $end = end($lines);
            if (empty($end)) {
                array_pop($lines);
            }

            foreach ($lines as $option)
            {
                // allow escaping \, for values that need a comma
                if (preg_match('/(?<!\\\),/', $option)) {
                    // if the option contains a , we'll assume it's an id,name combination
                    list($id,$name) = preg_split('/(?<!\\\),/', $option);
                    $id = trim(strtr($id,array('\,' => ',')));
                    $name = trim(strtr($name,array('\,' => ',')));
                    array_push($options, array('id' => $id, 'name' => $name));
                } else {
                    // otherwise we'll use the option for both id and name
                    $option = trim(strtr($option,array('\,' => ',')));
                    array_push($options, array('id' => $option, 'name' => $option));
                }
            }
        }
        return $options;
    }

    /**
     * Retrieve or check an individual option on demand
     */
    function getOption($check = false)
    {
        if (!isset($this->value)) {
             if ($check) return true;
             return null;
        }

        $firstline = current($this->getFirstline());
        $firstlineid = isset($firstline['id']) && isset($firstline['name']) && !empty($firstline['name'])? $firstline['id']:null;
        if (!isset($this->xv_itemfunc) || empty($this->xv_itemfunc)) {
            // we're interested in one of the known options (= default behaviour)
            $options = $this->getOptions();
            foreach ($options as $option=>$info) {
                if ($info['id'] == $this->value)  {
                    if ($check) return true;
                    if ($info['id'] == $firstlineid) return '';
                    return $info['name'];
                }
            }
            //still no good - try old format
            foreach ($options as $option=>$info) {
                if ($option == $this->value) { //support old format
                    if ($check) return true;
                    if ($option == $firstlineid) return '';
                    return $info;
                }
            }
            if ($check) return false;
            return $this->value;
        }
        // most API functions throw exceptions for empty ids, so we skip those here
        if (empty($this->value)) {
             if ($check) return true;
             return $this->value;
        }
        // use $value as argument for your API function : array('whatever' => $value, ...)
        $value = $this->value;
        eval('$result = ' . $this->xv_itemfunc .';');
        if (isset($result)) {
            if ($check) return true;
            return $result;
        }
        if ($check) return false;
        return $this->value;
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
            $validations = array('xv_firstline' =>  array(  'label'=>xarML('First line display text'),
                                                                'description'=>xarML('First line displayed in select box to user.'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'display'
                                                          ),
                                    'xv_func'       =>  array(  'label'=>xarML('Function'),
                                                                'description'=>xarML('A function such as xarModAPIFunc(...) used to generate the options for this field as an id=> name list.'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'definition',
                                                           ),
                                    'xv_file'       =>  array(  'label'=>xarML('File'),
                                                                'description'=>xarML('A file, position relative to index.php, containing select options for this field, one per line as either name or id,name pairs.'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition',
                                                          ),
                                    'xv_optionlist' =>  array(  'label'=>xarML('Select options'),
                                                                'description'=>xarML("A list of name or id,name pair options for this field, separated by semicolons eg. '1,Red;2,Blue;3,Green' or 'Red;Blue;Green'"),
                                                                'propertyname'=>'textarea_small',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                    'xv_override'   =>  array(  'label'=>xarML('Override'),
                                                                'description'=>xarML('Accept values other than those in the options list for this field.'),
                                                                'propertyname'=>'checkbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=> 'definition'
                                                          ),
                                    'xv_size'   =>  array(  'label'=>xarML('Rows in selector'),
                                                                'description'=>xarML('Number of rows to display in selector'),
                                                                'propertyname'=>'integerbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype'=> 'display'
                                                          )

                                );
            $validationarray= array_merge($validations,$parentvals);
        }
        return $validationarray;
    }

    function getFirstline()
    {
        $firstline = $this->xv_firstline;
        if (empty($firstline)) return array();
        if (is_array($firstline)) {
            if (isset($firstline['name'])) {
                 $line = array('id' =>'' ,'name' => $firstline['name']);
            } else {
                $line = array('id' => '', 'name' => $firstline['id']);
            }
        } else {
            $firstline = explode(',',$firstline);
            if (isset($firstline[1])) {
                $line = array('id' => '', 'name' => $firstline[1]);
            } else {
                $line = array('id' => '', 'name' => $firstline[0]);
            }
        }
        return array($line);
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     * @return base information for this property
    **/
    function getBasePropertyInfo()
    {
        $args = array();

        $validation = $this->getBaseValidationInfo();
        $baseInfo = array(
                          'id'         => 6,
                          'name'       => 'dropdown',
                          'label'      => 'Dropdown list',
                          'format'     => '6',
                          'validation' => serialize($validation),
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

    function parseLegacyValidation($validation = '')
    {

        // if the validation field is an array, we'll assume that this is an array of id => name
        if (is_array($validation)) {
            if (!isset($this->options)) $this->options  = array();
            if (count($validation) == 1) {
                $options= current($validation);
                foreach($options as $id => $name) {
                    array_push($this->options, array('id' => $id, 'name' => $name));
                }
            } else {
                foreach($validation as $id => $name) {
                    array_push($this->options, array('id' => $id, 'name' => $name));
                }
            }
        // if the validation field starts with xarModAPIFunc, we'll assume that this is
        // a function call that returns an array of names, or an array of id => name
        } elseif (preg_match('/^xarModAPIFunc/i',$validation)) {
            // if the validation field contains two ;-separated xarModAPIFunc calls,
            // the second one is used to get/check the result for a single $value
            if (preg_match('/^(xarModAPIFunc.+)\s*;\s*(xarModAPIFunc.+)$/i',$validation,$matches)) {
                $this->xv_func = $matches[1];
                $this->xv_itemfunc = $matches[2];
            } else {
                $this->xv_func = $validation;
            }
            // or if it contains a ; or a , we'll assume that this is a list of name1;name2;name3 or id1,name1;id2,name2;id3,name3
        } elseif (strchr($validation,';') || strchr($validation,',')) {
            // allow escaping \; for values that need a semi-colon
            $options = preg_split('/(?<!\\\);/', $validation);
            foreach ($options as $option) {
                $option = strtr($option,array('\;' => ';'));
                // allow escaping \, for values that need a comma
                if (preg_match('/(?<!\\\),/', $option)) {
                    // if the option contains a , we'll assume it's an id,name combination
                    list($id,$name) = preg_split('/(?<!\\\),/', $option);
                    $id = strtr($id,array('\,' => ','));
                    $name = strtr($name,array('\,' => ','));
                    array_push($this->options, array('id' => $id, 'name' => $name));
                } else {

                    // otherwise we'll use the option for both id and name
                    $option = strtr($option,array('\,' => ','));
                    array_push($this->options, array('id' => $option, 'name' => $option));
                }
            }

        // or if it contains a data file path, load the options from the file.  File will contain one or more lines each containing a list specified as name1;name2;name3 or id1,name1;id2,name2;id3,name3
        } elseif (preg_match('/^{file:(.*)}/',$validation, $fileMatch)) {
            $filePath = $fileMatch[1];
            $this->xv_file = $filePath;
        // otherwise we'll leave it alone, for use in any subclasses (e.g. min:max in NumberList, or basedir for ImageList, or ...)
        } else {
        }
    }

}

?>
