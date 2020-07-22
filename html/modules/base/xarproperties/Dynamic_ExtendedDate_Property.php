<?php
/**
 * Extended Date property
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
 * @package modules
 * @subpackage Base module
 * @author Roger Keays <roger.keays@ninthave.net>
 */

sys::import ('modules.base.xarproperties.Dynamic_Calendar_Property');

/**
 * The extended date property converts the value provided by the javascript
 * calendar into a universal YYYY-MM-DD format for storage in most databases
 * supporting the 'date' type.
 *
 * The problem with the normal Calendar property is that it converts
 * everything into a UNIX timestamp, and for most C librarys this does not
 * include dates before 1970. (see Xaraya bugs 2013 and 1428)
 */
class Dynamic_ExtendedDate_Property extends Dynamic_Calendar_Property
{
    public $id         = 47;
    public $name       = 'extendeddate';
    public $desc       = 'Date extended';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'extendeddate';
    }

    /**
     * We allow two validations: date, and datetime (corresponding to the
     * database's date and datetime data types.
     *
     * We also don't make any modifications for the timezone (too hard).
     */
    function validateValue($value = null)
    {
        if (empty($dbformat)) $dbformat = 'datetime';
        // if (!parent::validateValue($value)) return false;
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $this->value = $value;
            return true;

        } elseif (is_array($value)) {

            if (!empty($value['year']) && !empty($value['mon']) && !empty($value['day'])) {
                if (is_numeric($value['year']) && is_numeric($value['mon']) && is_numeric($value['day']) &&
                    $value['mon'] > 0 && $value['mon'] < 13 && $value['day'] > 0 && $value['day'] < 32) {
                    $this->value = sprintf('%04d-%02d-%02d',$value['year'],$value['mon'],$value['day']);

                    if ($this->xv_dbformat== 'datetime') {
                        if (isset($value['hour']) && isset($value['min']) && isset($value['sec']) &&
                            is_numeric($value['hour']) && is_numeric($value['min']) && is_numeric($value['sec']) &&
                            $value['hour'] > -1 && $value['hour'] < 24 && $value['min'] > -1 && $value['min'] < 61 && $value['sec'] > -1 && $value['sec'] < 61) {
                            $this->value .= ' ' . sprintf('%02d:%02d:%02d',$value['hour'],$value['min'],$value['sec']);
                        } else {
                            $this->invalid = xarML('date format');
                            $this->value = null;
                            return false;
                        }
                    }
                } else {
                    $this->invalid = xarML('date format');
                    $this->value = null;
                    return false;
                }
            } else {
                $this->value = '';
            }
            return true;

        /* sample value: 2004-06-18 18:47:33 */
        } elseif (is_string($value) &&

            /* check it matches the correct regexp */
            ($this->xv_dbformat == 'date' &&
            preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $value)) ||

            ($this->xv_dbformat == 'datetime' &&
            preg_match('/\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}/', $value))
            ) {

            /* TODO: use xaradodb to format the date */
            $this->value = $value;
            return true;

        } else {
            $this->invalid = xarML('date format');
            $this->value = null;
            return false;
        }
    } /* validateValue */

    /**
     * Show the input according to the requested dateformat.
     */
    function showInput(Array $data = array())
    {
        extract($data);

        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        $data['year'] =  '';
        $data['mon']  = '01';
        $data['day']  = '01';
        $data['hour'] = '00';
        $data['min']  = '00';
        $data['sec']  = '00';
        // default time is unspecified
        if (!empty($value)) {

            if ($this->xv_dbformat == 'date' &&
                preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $value, $matches)) {
                $data['year'] = $matches[1];
                $data['mon']  = $matches[2];
                $data['day']  = $matches[3];

            } elseif (($this->xv_dbformat == 'datetime') &&
                    (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})/', $value, $matches))) {
                    $data['year'] = $matches[1];
                    $data['mon']  = $matches[2];
                    $data['day']  = $matches[3];
                    $data['hour'] = $matches[4];
                    $data['min']  = $matches[5];
                    $data['sec']  = $matches[6];
            }
        }
        $data['year'] = isset($data['year'])?$data['year']:'';
        $data['format']   = $this->xv_dbformat;
        $dateformat = isset($dateformat)?$dateformat: $this->xv_dateformat;

        if (!isset($dateformat)) {
            if ($this->xv_dbformat == 'date') {
                $dateformat = '%Y-%m-%d';
            } else {
                $dateformat = '%Y-%m-%d %H:%i:%S';
            }
        }
        $data['dateformat'] = $dateformat;
        $data['name']       = $name;
        $data['id']         = $id;
        $data['value']      = $value;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['template'] = isset($template)?$template:$this->template;
       return xarTplProperty('base', 'extendeddate', 'showinput', $data);
    }

    /**
     * Show the output according to the requested dateformat.
     */
    function showOutput(Array $args = array())
    {
        extract($args);

        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }

        $data['year'] = '';
        $data['mon']  = '';
        $data['day']  = '';
        $data['hour'] = '';
        $data['min']  = '';
        $data['sec']  = '';

        // default time is unspecified
        if (empty($value)) {
            $value = '';

        } elseif ($this->xv_dbformat == 'date' &&
            preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $value, $matches)) {
            $data['year'] = $matches[1];
            $data['mon']  = $matches[2];
            $data['day']  = $matches[3];

        } elseif ($this->xv_dbformat  == 'datetime' &&
            preg_match('/(\d{4})-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})/', $value, $matches)) {
            $data['year'] = $matches[1];
            $data['mon']  = $matches[2];
            $data['day']  = $matches[3];
            $data['hour'] = $matches[4];
            $data['min']  = $matches[5];
            $data['sec']  = $matches[6];
        }
        $dbformat = isset($dbformat)?$dbformat:$this->xv_dbformat;
        $dateformat = isset($dateformat)?$dateformat:$this->xv_dateformat;
        if ($dbformat = 'datetime') {
            switch ($dateformat) {
                case 'short':
                    $dateformat = 'd/m/y';
                    break;
                case 'medium':
                    $dateformat = 'd M, Y';
                    break;
                case 'long':
                    $dateformat = 'd F, Y';
                    break;
                case 'shorttime':
                    $dateformat = 'h:i A d/m/y';
                    break;
                case 'mediumtime':
                    $dateformat = 'h:i:s A M d, Y';
                    break;
                case 'longtime':
                    $dateformat = 'h:i:s A M d, Y';
                    break;
                case 'shortdate':
                    $dateformat = 'd/m/y h:i A';
                    break;
                case 'mediumdate':
                    $dateformat = 'd M, Y h:i:s A';
                    break;
                case 'longdate':
                    $dateformat = 'd F, Y h:i:s A P';
                    break;
                case 'shorttoly':
                    $dateformat = 'h:i A';
                    break;
                case 'mediumtoly':
                    $dateformat = 'h:i:s A';
                    break;
                case 'longtoly':
                    $dateformat = 'h:i:s A P';
                    break;
                case 'full':
                case 'iso':
                    $dateformat = 'Y-m-d h:i:s P';
                    break;
                default:

            }
        } else { //it's date only but we might have a datetime format set
            switch ($dateformat) {
                case 'shorttime':
                case 'shorttoly':
                case 'shortdate':
                case 'short':
                    $dateformat = 'd/m/y';
                    break;
                case 'mediumtime':
                case 'mediumtoly':
                case 'mediumdate':
                case 'medium':
                    $dateformat = 'd M, Y';
                    break;
                case 'longtime':
                case 'longtoly':
                case 'longdate':
                case 'long':
                    $dateformat = 'd F, Y';
                    break;
                case 'full':
                case 'iso':
                    $dateformat = 'Y-m-d';
                    break;
                default:

            }

        }
        $data['dbformat'] = $dbformat;

        $dbdate = $dbformat == 'datetime'?'Y-m-d H:i:s':'Y-m-d';
        $newdate = date_create_from_format($dbdate,$value);
        $data['parsedvalue']= @date_format($newdate,$dateformat);
        $data['dateformat'] = $dateformat;
        $data['value']      = $value;
        $data['template'] = isset($template)?$template:$this->template;
         return parent::showOutput($data);
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
   function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            //we are overriding parent validations
            $timestamp = xarMLS_userTime();
            $datetime = xarLocaleFormatDate('%Y-%m-%d %H:%M:%S');
            $date = xarLocaleFormatDate('%Y-%m-%d');
            $dboptions = array(
                    array('id'=>'datetime', 'name'=>xarML('datetime: #(1)',$datetime)),
                    array('id'=>'date', 'name'=>xarML('date: #(1)',$date)),
                );

            $dateformats = array(
                    array('id'=>'short',      'name'=>'date-short: '.xarLocaleFormatDate('%m/%e/%y')),
                    array('id'=>'medium',     'name'=>'date-medium: '.xarLocaleFormatDate('%b %e, %Y')),
                    array('id'=>'long',       'name'=>'date-long: '.xarLocaleFormatDate('%B %e, %Y')),
                    array('id'=>'shorttime',  'name'=>'timedate-short: '.xarLocaleFormatDate('%I:%M %p %m/%e/%y')),
                    array('id'=>'mediumtime',  'name'=>'timedate-medium: '.xarLocaleFormatDate('%I:%M:%S %p %b %e, %Y')),
                    array('id'=>'longtime',   'name'=>'timedate-long: '.xarLocaleFormatDate('%I:%M:%S %p %b %e, %Y %Z')),
                    array('id'=>'shortdate',  'name'=>'datetime-short: '.xarLocaleFormatDate('%m/%e/%y %I:%M %p')),
                    array('id'=>'mediumdate', 'name'=>'datetime-medium: '.xarLocaleFormatDate('%b %e, %Y %I:%M:%S %p')),
                    array('id'=>'longdate',   'name'=>'datetime-long: '.xarLocaleFormatDate('%B %e, %Y %I:%M:%S %p %Z')),
                    array('id'=>'shorttoly',  'name'=>'time-short: '.xarLocaleFormatDate('%I:%M %p')),
                    array('id'=>'mediumtoly', 'name'=>'time-medium,: '.xarLocaleFormatDate('%I:%M:%S %p')),
                    array('id'=>'longtoly',   'name'=>'time-long: '.xarLocaleFormatDate('%I:%M:%S %p %Z')),
                    array('id'=>'full',       'name'=>'full: '.xarLocaleFormatDate('%Y-%m-%dT%H:%M:%S%Z')),
                    array('id'=>'iso',        'name'=>'iso: '.xarLocaleFormatDate('%Y-%m-%dT%H:%M:%S%Z')),
            );

            $validationarray = array('xv_dbformat' =>  array(  'label'=>xarML('Database format'),
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
        }
        return $validationarray;
    }

     function getBasePropertyInfo()
     {
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 47,
                              'name'       => 'extendeddate',
                              'label'      => 'Date extended',
                              'format'     => '47',
                              'validation' => serialize($validations),
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