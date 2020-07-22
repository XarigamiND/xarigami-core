<?php
/**
 * Dynamic GoogleMap property
 *
 * @package modules
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2010-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
 /* Static google map property
  * Basic static map property - first draft
  * TODO: options and validations, and non-static js version
  * @author Jo Dalle Nogare
  */
class Dynamic_GoogleMap_Property extends Dynamic_Property
{
    public $id          = 51;
    public $name        = 'googlemap';
    public $desc        = 'Google map';
    public $layout      = 'default';
    public $tplmodule   = 'base';
    public $module      = 'base';

    public $xv_mapwidth     = 300;
    public $xv_mapheight    = 250;
    public $xv_zoom         = 11;
    public $xv_latitude     = 0;
    public $xv_longitude    = 0;
    public $xv_locations    = '';
    public $xv_staticmap    = TRUE;
    public $xv_center       = '';
    public $xv_maptype      = 'roadmap';
    public $xv_mapformat    = 'PNG';
    public $xv_mappath      = '';
    public $xv_sensor       = FALSE;
    public $xv_fstyle       = '';
    public $xv_visible      = '';
    public $xv_inputlist    = array();
    public $xv_title        = 'Google map';
    public $xv_defaultmarker= '';
    public $urlparams       = '';


    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template     = 'googlemap';
        if(!isset($this->xv_inputlist)) $this->xv_inputlist = array();
        $this->filepath   = 'modules/bases/xarproperties';
    }
     public function checkInput($name = '', $value = null)
    {
        $name = !empty($name) ? $name : $this->name;
        if (!xarVarFetch($name,'isset',$mapinfo, NULL,XARVAR_NOT_REQUIRED)) return;
        $fieldlist = $this->xv_inputlist;
        $name = $this->name;
        $id = $this->id;

        if (isset($mapinfo) && is_array($mapinfo)) {
             $validations = $this->getBaseValidationInfo();
             $args = array();
            foreach($fieldlist as $fieldname) {
               //ensure we pass options
                if (isset($validations[$fieldname]['propargs']) && is_array($validations[$fieldname]['propargs'])) {
                    foreach ($validations[$fieldname]['propargs'] as $k=>$v) {
                        $args[$k] = $v;
                    }
                }
                $args['type'] =  $validations[$fieldname]['propertyname'];
                $args['name'] = $name."['".$fieldname."']";
                $args['value']= $mapinfo[$fieldname];
                $args['label']= $validations[$fieldname]['label'];
                $propinfo = Dynamic_Property_Master::getProperty($args);
                $isvalid = $propinfo->checkInput($args['name'], $args['value']);
                if ($isvalid === FALSE) {

                    $invalidmsg = $propinfo->invalid;
                    $this->invalid[$fieldname] = $invalidmsg;
                }
                $this->$fieldname = $mapinfo[$fieldname];

            }
            //take lat and long before center - no allow both to be provided
            /*if (isset($mapinfo['xv_latitude']) && !empty($mapinfo['xv_latitude'])
                && isset($mapinfo['xv_longitude']) && !empty($mapinfo['xv_longitude'])) {
                $this->xv_center =$mapinfo['xv_latitude'].','.$mapinfo['xv_longitude'];
                $mapinfo['xv_center'] ='';
            }*/
        }

         $this->value = serialize($mapinfo); //return the value in any case
        if (is_array($this->invalid) && count($this->invalid)>0) {
            return false;
        }
       return true;

    }

    public function showInput(Array $data = array())
    {
        extract($data);
        $this->parseValidation($this->validation);

        try {
            $newvalue = @unserialize($this->value);
        } catch (Exception $e) {
            $newvalue = array();
        }
        $value = isset($value)?$value:$newvalue;

        if (is_array($value)) {
            foreach($this->xv_inputlist as $fieldname) {

                $passedvalue = substr($fieldname,3);
                 $value[$fieldname] = isset($value[$fieldname]) && !is_null($value[$fieldname])?$value[$fieldname]: $this->$fieldname;

                $this->$fieldname = isset($value[$passedvalue])?$value[$passedvalue]:$value[$fieldname];
            }
        }
        if (is_array($this->invalid) && count($this->invalid) > 0) {
            $invalid = TRUE;
        } else {
            $invalid = FALSE;
        }
        $data['inputlist']  = isset($data['inputlist']) && is_array($data['inputlist']) ? $data['inputlist'] : $this->xv_inputlist;
        $data['mapwidth']   = isset($data['mapwidth']) ? $data['mapwidth'] : $this->xv_mapwidth;
        $data['mapformat']   = isset($data['mapformat']) ? $data['mapformat'] : $this->xv_mapformat;
        $data['center']   = isset($data['center']) ? $data['center'] : $this->xv_center;
        $data['maptype']   = isset($data['maptype']) ? $data['maptype'] : $this->xv_maptype;
        $data['mapheight']  = isset($data['mapheight']) ? $data['mapheight'] : $this->xv_mapheight;
        $data['zoomlevel']  = isset($data['zoomlevel']) ? $data['zoom'] : $this->xv_zoom;
        $data['latitude']   = isset($data['latitude']) ? $data['latitude'] : $this->xv_latitude;
        $data['longitude']  = isset($data['longitude']) ? $data['longitude'] : $this->xv_longitude;
        $data['locations']  = isset($data['locations']) ? $data['locations'] : $this->xv_locations;
        $data['mappath']  = isset($data['mappath']) ? $data['mappath'] : $this->xv_mappath;
        $data['staticmap']  = isset($data['staticmap']) ? $data['staticmap'] : $this->xv_staticmap;
        $data['visible']  = isset($data['visible']) ? $data['visible'] : $this->xv_visible;
        $data['fstyle']  = isset($data['fstyle']) ? $data['fstyle'] : $this->xv_fstyle;
        $data['title'] = isset( $data['title'] )? $data['title'] :$this->xv_title;
        $data['defaultmarker'] = isset( $data['defaultmarker'] )? $data['defaultmarker'] :$this->xv_defaultmarker;
        $props = array();
        $prop = array();
        $args= array();
        $validations = $this->getBaseValidationInfo();
        foreach ( $data['inputlist'] as $key=>$value){

            $prop['fieldname']= $this->name.'['.$value.']';
            $prop['fieldid'] = $this->id.'_'.$value;
            $prop['fieldlabel']= $validations[$value]['label'];
            $prop['description']= $validations[$value]['description'];
            $prop['fieldvalue'] = $this->$value;

            $prop['configinfo'] = isset($validations[$value]['configinfo']) ? $validations[$value]['configinfo']: '';
            $prop['invalid'] = isset($this->invalid[$value]) ?$this->invalid[$value]:'';
            $propargs = isset($validations[$value]['propargs'])?$validations[$value]['propargs']:array();
            //pass normal arguments here for processing as data in the property input - like tag data
            $args= array('type'=>$validations[$value]['propertyname'],'name'=>$prop['fieldname'], 'id'=> $prop['fieldid'], 'value'=>$prop['fieldvalue'], 'invalid'=>$prop['invalid']);
            foreach($propargs as $argname=>$argvalue) {
                $args[$argname] = $argvalue;
            }
            $prop['inputtag'] = xarModAPIFunc('dynamicdata','admin','showinput',$args);

            $props[] = $prop;
        }
        $data['props']= $props;
        if (empty($template)) $template = $this->template;
        $data['template'] = $template;

         return xarTplProperty('base', $template, 'showinput', $data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        $this->parseValidation($this->validation);
        $value = isset($value)?$value: $this->value;
        if (isset($value) && !is_array($value)) {
            try {
                $value = @unserialize($value);
            } catch (Exception $e) {
                $value = array();
            }
        } else {
            $value = array();
        }
        if (is_array($value)) {
            foreach($this->xv_inputlist as $fieldname) {
               $passedvalue = substr($fieldname,3);
               $value[$fieldname] = isset($value[$fieldname])?$value[$fieldname]: $this->$fieldname;
               $newvalue = isset($value[$passedvalue])?$value[$passedvalue]:$value[$fieldname];
            $this->$fieldname = $newvalue;
            }
        }
        if (is_array($this->invalid) && count($this->invalid) > 0) {
            $invalid = TRUE;
        } else {
            $invalid = FALSE;
        }
        $data['defaultmarker']   = isset($data['defaultmarker']) ? $data['defaultmarker'] : $this->xv_defaultmarker;
        $data['mapwidth']   = isset($data['mapwidth']) ? $data['mapwidth'] : $this->xv_mapwidth;
        $data['mapformat']   = isset($data['mapformat']) ? $data['mapformat'] : $this->xv_mapformat;
        $data['maptype']   = isset($data['maptype']) ? $data['maptype'] : $this->xv_maptype;
        $data['mapheight']  = isset($data['mapheight']) ? $data['mapheight'] : $this->xv_mapheight;
        $data['zoomlevel']  = isset($data['zoomlevel']) ? $data['zoom'] : $this->xv_zoom;
        $data['latitude']   = isset($data['latitude']) ? $data['latitude'] : $this->xv_latitude;
        $data['longitude']  = isset($data['longitude']) ? $data['longitude'] : $this->xv_longitude;
        $data['locations']  = isset($data['locations']) ? $data['locations'] : $this->xv_locations;
        $data['mappath']  = isset($data['mappath']) ? $data['mappath'] : $this->xv_mappath;
        $data['visible']  = isset($data['visible']) ? $data['visible'] : $this->xv_visible;
        $data['fstyle']  = isset($data['fstyle']) ? $data['fstyle'] : $this->xv_fstyle;

        $data['staticmap']  = isset($data['staticmap']) ? $data['staticmap'] : $this->xv_staticmap;
        $data['center']   = isset($data['center']) ? $data['center'] : $this->xv_center;
        $data['sensor']   = isset($data['sensor']) ? 'true' : 'false';
        $data['title'] = isset( $data['title'] )? $data['title'] :$this->xv_title;
        if (is_array($this->invalid) && count($this->invalid) > 0) {
            $invalid = TRUE;
        } else {
            $invalid = FALSE;
        }

        //build the url
        $parameters = array();
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $parameters['center']= 'center='.$data['latitude'].','.$data['longitude'];
        } elseif (!empty($data['center'])) {
             $parameters['center']= 'center='.urlencode($data['center']);
        }
        $iconurl='';
        $parameters['markers'] ='markers=';
        if (!empty($data['defaultmarker'])) {
            $iconurl = urlencode(xarServerGetBaseURL().xarTplGetThemeDir().'/images/'.$data['defaultmarker']);
            $parameters['markers'] .='icon:'.$iconurl.'|';
        }
         $data['iconurl'] = $iconurl;
        if (!empty($data['locations'])) {
            $parameters['markers'] .= urlencode($data['locations']);
            $parameters['center'] = '';
        } else {
            //let's show it as a pointer anyway
            if (!empty($data['latitude']) && !empty($data['longitude'])) {
                $parameters['markers'] .= $data['latitude'].','.$data['longitude'];
                 $parameters['center'] = '';
            } elseif (!empty($data['center'])) {
                $parameters['markers'] .= urlencode($data['center']);
                 $parameters['center'] = '';
            }
        }
        if (!empty($data['mapwidth']) && !empty($data['mapheight'])) {
            $parameters['size'] = 'size='.$data['mapwidth'].'x'.$data['mapheight'];
        }
        if (!empty($data['zoomlevel'])) {
            $parameters['zoom'] = 'zoom='.$data['zoomlevel'];
        }
        if (!empty($data['maptype'])) {
            $parameters['maptype'] = 'maptype='.$data['maptype'];
        }
        if (!empty($data['mapformat'])) {
            $parameters['format'] = 'format='.$data['mapformat'];
        }
        if (!empty($data['mappath'])) {
            $parameters['path'] = 'path='.urlencode($data['mappath']);
        }
        if (!empty($data['visible'])) {
            $parameters['visible'] = 'visible='.urlencode($data['visible']);
        }
       if (!empty($data['fstyle'])) {
            $parameters['style'] = 'style='.urlencode($data['fstyle']);
        }

        $parameters['sensor'] = 'sensor='.$data['sensor'];
        $data['parameters'] = $parameters;
        if ((!empty($parameters['center']) || !empty($parameters['markers']))  && ($invalid==FALSE)) {
            $data['urlparams'] = implode('&amp;',$parameters);
        } else {
            $data['urlparams'] = '';
        }
        $this->urlparams = $data['urlparams'];
        if (empty($template)) $template = $this->template;
        $data['template'] = $template;

        return parent::showOutput($data);

    }

    function getValue($data = array())
    {
        return  $this->urlparams;

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

            $mapargs = array( array('id'=>'roadmap',  'name' =>xarML('roadmap')),
                             array('id'=>'satellite', 'name' =>xarML('satellite')),
                             array('id'=>'hybrid',  'name' =>xarML('hybrid')),
                             array('id'=>'terrain',  'name' =>xarML('terrain'))
                           );
            $this->mapoptions = $mapargs;
            $formatargs = array( array('id'=>'PNG',  'name' =>'PNG'),
                             array('id'=>'JPEG', 'name' =>'JPEG'),
                             array('id'=>'GIF',  'name' =>'GIF'),
                             );
            $inputargs = array( array('id'=>'xv_latitude',  'name' =>'latitude'),
                                array('id'=>'xv_longitude', 'name' =>'longitude'),
                                array('id'=>'xv_center',  'name' =>'center'),
                                 array('id'=>'xv_maptype',  'name' =>'maptype'),
                                  array('id'=>'xv_zoom',  'name' =>'zoom'),
                                  array('id'=>'xv_mapwidth',  'name' =>'mapwidth'),
                                   array('id'=>'xv_title',  'name' =>'title'),
                                  array('id'=>'xv_mapheight',  'name' =>'mapheight'),
                             );
            $markers = '<a href="http://code.google.com/intl/en/apis/maps/documentation/staticmaps/#Markers">'.xarML('Marker help').'</a>';
             $features = '<a href="http://code.google.com/intl/en/apis/maps/documentation/staticmaps/#StyledMaps">'.xarML('Feature style help').'</a>';
            $validations= array(        'xv_mapwidth'    =>  array('label'=>xarML('Map width'),
                                                          'description'=>xarML('Width of the map'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'display',
                                                          'configinfo'    => xarML('px')
                                                          ),
                                        'xv_mapheight'    =>  array('label'=>xarML('Map height'),
                                                          'description'=>xarML('Height of the map'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'display',
                                                           'configinfo'    => xarML('px')
                                                           ),
                                        'xv_zoom'          =>  array('label'=>xarML('Zoom level'),
                                                          'description'=>xarML('Zoom level for this map between 0 (the whole world, and 21+)'),
                                                          'propertyname'=>'integerbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'display',
                                                          ),
                                        'xv_defaultmarker' => array('label'=>xarML('Default marker icon'),
                                                           'description'=> xarML('Default marker icon - google icon is used when none specified'),
                                                           'propertyname'=>'imagelist',
                                                           'propargs'=> array('xv_basedir'=>'{theme}/images/','xv_longname'=>1,'firstline'=>xarML('Default')),
                                                           'ignore_empty' => 1,
                                                           'ctype'=>'display',
                                                           ),
                                        'xv_latitude'       =>  array('label'=>xarML('Latitude'),
                                                          'description'=>xarML('Latitude value for center'),
                                                          'propertyname'=>'floatbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition'
                                                          ),
                                        'xv_longitude'       =>  array('label'=>xarML('Longitude'),
                                                          'description'=>xarML('Longitude value for center'),
                                                          'propertyname'=>'floatbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition'
                                                          ),
                                        'xv_locations'   =>  array('label'=>xarML('Location marker list'),
                                                           'description'=>xarML('List of location markers separated by pipe character in format: markerstyle|markerLocation1|markerLocation2'),
                                                           'propertyname'=>'textarea_small',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'display',
                                                           'configinfo'    => xarML('[see #(1)]',$markers)
                                                          ),
                                        'xv_staticmap'     => array('label' => xarML('Static map?'),
                                                            'description' => xarML('Display a static map.'),
                                                            'propertyname' => 'checkbox',
                                                            'ignore_empty'  => 1,
                                                            'ctype' => 'definition',
                                                            'configinfo'    => xarML('Unchecked, a dynamic map is displayed which relies on javascript.')
                                                        ),
                                        'xv_center'   =>  array('label'=>xarML('Map center'),
                                                           'description'=>xarML('Defines the center of the map as a string address if latitude and longitude not given.'),
                                                           'propertyname'=>'textbox',
                                                           'propargs' => array('class'=>'textxxlong','size'=>60),
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition',
                                                           'configinfo'    => xarML('[Required if latitude and longitude not provided.]')
                                                          ),
                                        'xv_maptype'   =>  array('label'=>xarML('Map type'),
                                                           'description'=>xarML('Defines the type of map to display'),
                                                           'propertyname'=>'dropdown',
                                                           'propargs' => array('options'=>$mapargs),
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition'
                                                          ),
                                        'xv_mapformat'   =>  array('label'=>xarML('Map format'),
                                                           'description'=>xarML('Defines the output format'),
                                                           'propertyname'=>'dropdown',
                                                           'propargs' => array('options'=>$formatargs),
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition'
                                                          ),
                                        'xv_mappath'   =>  array('label'=>xarML('Map path'),
                                                           'description'=>xarML('Defines the type of map to display in format pathStyles|pathLocation1|pathLocation2|'),
                                                           'propertyname'=>'textarea_small',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'display'
                                                          ),
                                        'xv_visible'   =>  array('label'=>xarML('Always visible'),
                                                           'description'=>xarML('Optional location that always remains visible on the map.'),
                                                           'propertyname'=>'textarea_small',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'display'
                                                          ),
                                        'xv_fstyle'   =>  array('label'=>xarML('Custom feature style'),
                                                           'description'=>xarML('A custom feature style.'),
                                                           'propertyname'=>'textarea_small',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'display',
                                                            'configinfo'    => xarML('[see #(1)]',$features)
                                                          ),
                                        'xv_sensor'   =>  array('label'=>xarML('Sensor use'),
                                                           'description'=>xarML('Using a sensor?'),
                                                           'propertyname'=>'checkbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition'
                                                          ),
                                        'xv_title'   =>  array('label'=>xarML('Title'),
                                                           'description'=>xarML('Title for centerpoint'),
                                                           'propertyname'=>'textbox',
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition'
                                                          ),
                                        'xv_inputlist'   =>  array('label'=>xarML('Available inputs'),
                                                           'description'=>xarML('A list of available user inputs'),
                                                           'propertyname'=>'checkboxlist',
                                                           'propargs' => array('options'=>$inputargs),
                                                           'ignore_empty'  =>1,
                                                           'ctype'=>'definition'
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
     * @return base information for this property
    **/
    function getBasePropertyInfo()
    {
        $args = array();

        $validation = $this->getBaseValidationInfo();

        $baseInfo = array(
            'id'            => 51,
            'name'          => 'googlemap',
            'label'         => 'Google map',
            'format'        => '51',
            'validation'    =>  serialize($validation),
            'source'        => '',
            'dependancies'  => '',
            'filepath'    => 'modules/base/xarproperties',
            'requiresmodule'=> 'base',
            'aliases'       => '',
            'args'          => serialize($args),
        );
        return $baseInfo;
    }

}
?>
