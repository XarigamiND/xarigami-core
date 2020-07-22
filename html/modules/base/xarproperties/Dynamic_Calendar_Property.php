<?php
/**
 * Dynamic Calendar Property
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
 * Class for dynamic calendar property
 *
 * This property shows an input text field, and a clickable calendar next to it.
 * The calendar is a js popup in which users can choose a date
 * The date will get converted into a datetime or timestamp value, depending on the validation chosen.
 *
 * @package modules
 * @subpackage Base module
 * @author
 * @param
 */
sys::import('modules.dynamicdata.class.properties');

class Dynamic_Calendar_Property extends Dynamic_Property
{
    public $id         = 8;
    public $name       = 'calendar';
    public $desc       = 'Calendar';
    public $xv_dbformat = '';
    public $xv_dateformat = 'mediumdate';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'calendar';
        $this->filepath   = 'modules/base/xarproperties';
    }
    /**
     * See if the value put into the property is valid
     * @param mixed value
     */
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

        if (isset($this->default) && !empty($this->default) && (empty($value)|| $value == -1)) {
           if ((substr($this->default,0,6) =='time()') && ($this->xv_dbformat == 'timestamp')) {
            $value = time();
           }
        }

        // default time is unspecified
        if (empty($value)) {
              $this->value = -1;
        } elseif (is_numeric($value)) {
            $this->value = $value;
        } elseif (is_array($value) && !empty($value['year'])) {
            if (!isset($value['sec'])) {
                $value['sec'] = 0;
            }
            $this->value = mktime($value['hour'],$value['min'],$value['sec'],
                                  $value['mon'],$value['mday'],$value['year']);
        } elseif (is_string($value)) {
            // assume dates are stored in UTC format
            // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            // this returns -1 when we have an invalid date (e.g. on purpose)
            $this->value = strtotime($value);
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($this->value === false) $this->value = -1;
            if ($this->value >= 0) {
                // adjust for the user's timezone offset
                $this->value -= xarMLS_userOffset($this->value) * 3600;
            }
        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
        }
        // TODO: improve this
        // store values in a datetime field
        if ($this->validation == 'datetime') {
            $this->value = gmdate('Y-m-d H:i:s', $this->value);
        // store values in a date field
        } elseif ($this->validation == 'date') {
            $this->value = gmdate('Y-m-d', $this->value);
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    /**
     * Show an input form for the property
     * This shows a text input and a clickable js item next to it
     * The popup will show a js calendar, with or without a timeselector
     */
    function showInput(Array $data = array())
    {
        extract($data);
        $value = !isset($value) ? $this->value:$value;
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (isset($default) && !empty($default)) $this->xv_default = $default;
        // default time is unspecified
        if (empty($value)) {
            $value = -1;
        } elseif (!is_numeric($value) && is_string($value)) {
            // assume dates are stored in UTC format
            // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            // this returns -1 when we have an invalid date (e.g. on purpose)
            $value = strtotime($value);
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($value === false) $value = -1;
        }
        if (!isset($dateformat)) {
            $dateformat = '%Y-%m-%d %H:%M:%S';
            if ($this->validation == 'date') {
                $dateformat = '%Y-%m-%d';
            } else {
                $dateformat = '%Y-%m-%d %H:%M:%S';
            }
        }
        
        $localeData = xarMLSLoadLocaleData();
        for ($i = 1; $i < 8; $i++) {
            $dayNames[]      = $localeData['/dateSymbols/weekdays/'.$i.'/full'];
            $dayNamesShort[] = $localeData['/dateSymbols/weekdays/'.$i.'/short'];
            $dayNamesMin[] = $dayNamesShort[$i-1][0].$dayNamesShort[$i-1][1];
        }
        for ($i = 1; $i < 13; $i++) {
            $monthNames[] = $localeData['/dateSymbols/months/'.$i.'/full'];
        }
        $firstDay = isset($localeData['/dateSymbols/firstday']) ? $localeData['/dateSymbols/firstday'] : 0;
        $data['options'] =  serialize(array(
            'dayNames'      => $dayNames,
            'dayNamesShort' => $dayNamesShort,
            'dayNamesMin'   => $dayNamesMin,
            'firstDay'      => $firstDay,
            'monthNames'    => $monthNames,
            'closeText'     => xarML('Done'), 
            'currentText'   => xarML('Today'),
            'nextText'      => xarML('Next'),
            'prevText'      => xarML('Prev'),
            'timeOnlyTitle' => xarML('Choose Time'),
            'timeText'      => xarML('Time'),
            'hourText'      => xarML('Hour'),
            'minuteText'    => xarML('Minute'),
            'secondText'    => xarML('Second'), 
            'amText'        => $localeData['/dateSymbols/pm'],
            'pmText'        => $localeData['/dateSymbols/am']
        ));


           // $timeval = xarLocaleFormatDate($dateformat, $value);
        $data['baseuri']    = xarServerGetBaseURI();
        $data['dateformat'] = $dateformat;
        $data['jsID']       = str_replace(array('[', ']'), '_', $id);
        // $data['timeval']    = $timeval;
        $data['name']       = $name;
        $data['id']         = $id;
        $data['value']      = $value;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['template'] =  isset($template) ? $template : $this->template;
        return parent::showInput($data);
    }
    /**
     * Show the date formatted according to the validation type
     */
    function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is unspecified
        if (empty($value)) {
            $value = -1;
        } elseif (!is_numeric($value) && is_string($value)) {
            // assume dates are stored in UTC format
            // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            // this returns -1 when we have an invalid date (e.g. on purpose)
            $value = strtotime($value);
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($value === false) $value = -1;
        }
        if (!isset($dateformat)) {
            $dateformat = $this->xv_dateformat; //default medium date time
        }
        $dbformat = isset($dbformat)?$dbformat:$this->xv_dbformat;
        if ($dbformat == 'date' || $dbformat = 'datetime') {
            switch ($dateformat) {
                case 'short':
                    $dateformat = '%m/%e/%y';
                    break;
                case 'medium':
                    $dateformat = '%b %e, %Y';
                    break;
                case 'long':
                    $dateformat = '%B %e, %Y';
                    break;
                case 'shorttime':
                    $dateformat = '%I:%M %p %m/%e/%y';
                    break;
                case 'mediumtime':
                    $dateformat = '%I:%M:%S %p %b %e, %Y';
                    break;
                case 'longtime':
                    $dateformat = '%I:%M:%S %p %b %e, %Y';
                    break;
                case 'shortdate':
                    $dateformat = '%m/%e/%y %I:%M %p';
                    break;
                case 'mediumdate':
                    $dateformat = '%b %e, %Y %I:%M:%S %p';
                    break;
                case 'longdate':
                    $dateformat = '%B %e, %Y %I:%M:%S %p %Z';
                    break;
                case 'shorttoly':
                    $dateformat = '%I:%M %p';
                    break;
                case 'mediumtoly':
                    $dateformat = '%I:%M:%S %p';
                    break;
                case 'longtoly':
                    $dateformat = '%I:%M:%S %p %Z';
                    break;
                case 'full':
                case 'iso':
                    $dateformat = '%Y-%m-%dT%H:%M:%S%Z';
                    break;
                default:

            }
        }
        $data['dbformat'] = $dbformat;
        $data['dateformat'] = $dateformat;
        $data['value'] = $value;
        $data['template'] = isset($template)?$template:$this->template;
        return parent::showOutput($data);
    }
    function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvals = parent::getBaseValidationInfo();
            $timestamp = xarMLS_userTime();
            $datetime = xarLocaleFormatDate('%Y-%m-%d %H:%M:%S');
            $date = xarLocaleFormatDate('%Y-%m-%d');
            $dboptions = array(
                    array('id'=>'timestamp','name'=>xarML('timestamp: #(1)',$timestamp)),
                    array('id'=>'datetime', 'name'=>xarML('datetime: #(1)',$datetime)),
                    array('id'=>'date', 'name'=>xarML('date: #(1)',$date)),
                );

            $dateformats = array(
                    array('id'=>'short',      'name'=>'date-short: '.xarLocaleGetFormattedDate('short')),
                    array('id'=>'medium',     'name'=>'date-medium: '.xarLocaleGetFormattedDate('medium')),
                    array('id'=>'long',       'name'=>'date-long: '.xarLocaleGetFormattedDate('long')),
                    array('id'=>'shorttime',  'name'=>'timedate-short: '.xarLocaleGetFormattedDate('shorttime')),
                    array('id'=>'mediumtime',  'name'=>'timedate-medium: '.xarLocaleGetFormattedDate('mediumtime')),
                    array('id'=>'longtime',   'name'=>'timedate-long: '.xarLocaleGetFormattedDate('longtime')),
                    array('id'=>'shortdate',  'name'=>'datetime-short: '.xarLocaleGetFormattedDate('shortdate')),
                    array('id'=>'mediumdate', 'name'=>'datetime-medium: '.xarLocaleGetFormattedDate('mediumdate')),
                    array('id'=>'longdate',   'name'=>'datetime-long: '.xarLocaleGetFormattedDate('longdate')),
                    array('id'=>'shorttoly',  'name'=>'time-short: '.xarLocaleGetFormattedDate('shorttoly')),
                    array('id'=>'mediumtoly', 'name'=>'time-medium,: '.xarLocaleGetFormattedDate('mediumtoly')),
                    array('id'=>'longtoly',   'name'=>'time-long: '.xarLocaleGetFormattedDate('longtoly')),
                    array('id'=>'full',       'name'=>'full: '.xarLocaleGetFormattedDate('full')),
                    array('id'=>'iso',        'name'=>'iso: '.xarLocaleGetFormattedDate('iso')),
            );

            $validations = array('xv_dbformat' =>  array(  'label'=>xarML('Database format'),
                                                                'description'=>xarML('Database stored format of the date'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => array('options'=>$dboptions),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                     'xv_dateformat' => array( 'label' => xarML('Display format'),
                                                                'description'=>xarML('Output display of the date'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => array('options'=>$dateformats),
                                                                'ignore_empty' => 1,
                                                                'ctype'=>'display'
                                                                )
                                    );
            $validationarray= array_merge($validations,$parentvals);
        }
        return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @return array Base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 8,
                              'name'       => 'calendar',
                              'label'      => 'Calendar',
                              'format'     => '8',
                              'validation' => serialize($validations),
                              'source'         => '',
                              'dependancies'   => '',
                              'filepath'    => 'modules/base/xarproperties',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
     /**
      * Show a validation input form. The user can choose a valid validation for this property
      */
      /*
    function showValidation(Array $args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->validation = $validation;
        }
        if (empty($this->validation) || $this->validation == 'datetime' || $this->validation == 'date') {
            $data['dbformat'] = $this->validation;
            $data['other'] = '';
        } else {
            $data['dbformat'] = '';
            $data['other'] = $this->validation;
        }
        // Note : timestamp is not an option for ExtendedDate
        $data['class'] = get_class($this);

        // allow template override by child classes
        if (empty($template)) {
            $template = 'calendar';
        }
        return xarTplProperty('base', $template, 'validation', $data);
    }*/
    /**
     * Update the validation for this property
     * @return bool true on success
     */
     /*
    function updateValidation(Array $args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // do something with the validation and save it in $this->validation
        if (isset($validation)) {
            if (is_array($validation)) {
                if (!empty($validation['other'])) {
                    $this->validation = $validation['other'];

                } elseif (isset($validation['dbformat'])) {
                    $this->validation = $validation['dbformat'];

                } else {
                    $this->validation = '';
                }
            } else {
                $this->validation = $validation;
            }
        }

        // tell the calling function that everything is OK
        return true;
    }
*/
}

?>
