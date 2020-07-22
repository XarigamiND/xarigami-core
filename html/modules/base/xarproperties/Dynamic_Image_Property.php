<?php
/**
 * Image Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');
sys::import('modules.base.xarproperties.Dynamic_FileUpload_Property');
/**
 * handle the image property
 *
 * @package dynamicdata
 */
class Dynamic_Image_Property extends Dynamic_TextBox_Property
{
    public $id         = 12;
    public $name       = 'image';
    public $desc       = 'Image';

    public $xv_image_source = 'url';
    public $xv_basedir      = '';
    public $xv_basepath      = ''; //for uploads
    public $xv_display          = false;
    public $xv_file_ext     = 'gif,jpg,jpeg,png,bmp'; //allowed file extensions
    public $upload          = false;
    public $imagetext  = '';
    public $imagealt   = '';
    public $xv_longname = false;
    public $xv_max_file_size = 1000000;
    public  $xv_display_width = '100';
    public $xv_host    = ''; //URL only
    //image options
    public $xv_scale                = NULL; //100%
    public $xv_resize_width         = NULL;
    public $xv_resize_height        = NULL;
    public $xv_output_width         = '';
    public $xv_output_height        = '';
    public $xv_output_units         = 'px';

    function __construct($args)
    {
        parent::__construct($args);
        $this->template  = 'image';
        $this->imagetext = xarML('No image');
        $this->imagealt = xarML('Image');

        if ($this->xv_image_source == 'upload') $this->upload = true;
    }

    function validateValue($value = null)
    {


       //  if (!isset($value)) $value = $this->value;
        // make sure we check the right image_source when dealing with several image properties
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }
        //don't validate the value for upload as it may not yet be populated
        $sourcename = $name . '_source';
        if (!xarVarFetch($sourcename, 'str:1:100', $image_source, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!empty($image_source)) $this->xv_image_source = $image_source;
        if ($this->xv_image_source == 'url') {
           if (!parent::validateValue($value)) return false;
            $prop = Dynamic_Property_Master::getProperty(array('type' => 'url'));
             $prop->xv_host = isset($this->xv_host) ?$this->xv_host :'';
            $prop->validateValue($value);
            $this->value = $prop->value;
        } elseif ($this->xv_image_source == 'upload') {

            $prop = Dynamic_Property_Master::getProperty(array('type' => 'fileupload'));
            $paths =  $this->getBaseDirInfo();

            $basedir = isset($paths['basedir']) ? $paths['basedir'] : '';
            $basepath = isset($paths['basepath']) ? $paths['basepath'] : '';
            $prop->xv_basedir = $basedir;
            $prop->xv_scale= $this->xv_scale;
            $prop->xv_allowempty= $this->xv_allowempty;
            $prop->xv_resize_height = $this->xv_resize_height;
            $prop->xv_resize_width = $this->xv_resize_width;
            $prop->setExtensions($this->xv_file_ext);
            $prop->fieldname = $this->fieldname;
            $prop->validateValue($value);

            //remove the directory path, we only want to work with the file name
            $this->value = basename($prop->value);


        }elseif ($this->xv_image_source == 'local') {
            $prop = Dynamic_Property_Master::getProperty(array('type'=>'filelist'));
            $prop->xv_longname = $this->xv_longname;
            $prop->xv_basedir = $this->xv_basedir;
            $prop->xv_file_ext = $this->xv_file_ext;
            $prop->validateValue($value);
            $this->value = $prop->value;
        }
         $this->invalid = isset($prop->invalid)?$prop->invalid:'';



         if (!empty($this->invalid)) return false;
        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        $this->xv_basedir = isset($basedir)?$basedir: $this->xv_basedir;
        $data['image_source'] = isset($image_source) ? $image_source : $this->xv_image_source;
        if (isset($host) && !empty($host)) $this->xv_host = $host;

        $paths =  $this->getBaseDirInfo();

        $basedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $basepath = isset($paths['basepath']) ? $paths['basepath'] : '';
        $data['longname'] = isset($longname) ? $longname : $this->xv_longname;
        $data['extensions'] = isset($extensions) ? $extensions : $this->xv_file_ext;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['fileName']   = isset($data['fileName'])?$data['fileName']:$data['value'];
        $data['class']   = isset($data['class'])?$data['class']:$this->xv_classname;
        $data['display'] = isset($display)? $display : $this->xv_display;
        $data['maxsize'] = isset($maxsize)? $maxsize : $this->xv_max_file_size;
        $data['srcpath']  = ''; //for file display on input
        $data['width'] = isset($width)?$width: $this->xv_display_width;
        //for display on input
         $filepath = str_replace('//','/',$basedir.'/'.$data['value']);
        if (!empty($data['value']) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$data['value']) &&
            file_exists( $filepath) &&
            is_file($filepath)) {
            $srcpath=  $filepath;
            $data['srcpath'] = $srcpath;
        }
        if (($this->xv_image_source == 'local') || ($this->xv_image_source == 'upload')) {
            if  ($this->xv_image_source == 'upload') {
                $this->upload = true;
                $data['basedir'] = $this->xv_basedir;
                $data['fileName']  = basename( $data['fileName'] );
            }
            $data['value'] = basename($data['value']);
            $this->value =  $data['value'];
        }
        //we need a basedir for browsering which could be outside the webdir
        //the image cannot be display tho (or should we allow it file:// ...... for some cases)
       $data['basedir'] = empty($basedir)?$basepath:$basedir;
        return parent::showInput($data);
    }
    /**
     * Show the image in a template
     * @param array @args
     */
    public function showOutput(Array $data = array())
    {
        extract($data);
        if(!empty($image_source)) $this->xv_image_source = $image_source;
        if(!empty($basedir)) $this->xv_basedir = $basedir;
        if (!isset($data['value']) ||  empty($data['value'])) $data['value'] = $this->value;
        $paths = $this->getBaseDirInfo();
        $basedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $basepath = isset($paths['basepath']) ? $paths['basepath'] : '';

        $output_width = isset($output_width)?$output_width: $this->xv_output_width;
        $output_height = isset($output_height)?$output_height: $this->xv_output_height;
        $output_units = isset($output_units)?$output_units: $this->xv_output_units;
        $outputsize = '';
        if (!isset($output_units)) $output_units = $this->xv_output_units;
        if (isset($output_width) && !empty($output_width)) {
            $outputsize = 'width:'.$output_width.$output_units.';';
        } elseif (isset($output_height) && !empty($output_height)) {
             $outputsize = 'height:'.$output_height.$output_units.';';
        }

        $data['outputsize'] = $outputsize;
        $data['srcpath']  = ''; //for file display
        if (!empty($data['value'])) {
            // pass through basedir for uploaded file
             if (($this->xv_image_source == 'local') || ($this->xv_image_source == 'upload')) {
                //$data['value'] = $basedir . $data['value'];
                //for display on input
                $filepath = str_replace('//','/',$basedir.'/'.$data['value']);
                if (preg_match('/^[a-zA-Z0-9_\/.-]+/',$data['value']) &&  file_exists($filepath) && is_file($filepath)) {
                   $srcpath = $filepath;
                   $data['srcpath'] = $srcpath;
                }
            }
        }

        if (empty($data['imagetext'])) $data['imagetext'] = $this->imagetext;
        if (empty($data['imagealt'])) $data['imagealt'] = $this->imagealt;

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
            $local = xarML('Local');
            $fileupload = xarML('File upload');
            $uploadonly = xarML('[File upload only]');
            //parent is textbox so we need to supply upload attributes as well

            $allowduppropargs = array('options' =>array(
                                                    array('id'=>0,  'name' =>xarML('Not allowed')),
                                                    array('id'=>1,  'name' =>xarML('Create a new version')),
                                                    array('id'=>2,  'name' =>xarML('Overwrite existing file'))
                                                      )
                                                      );
            $imagesourceargs = array('options' =>array(array('id'=>'local', 'name'=>$local),
                                                        array('id'=>'url', 'name'=>'URL'),
                                                        array('id'=>'upload', 'name' =>$fileupload)),
                                                        'required'=>1);
            $validations = array('xv_image_source' =>  array('label'=>xarML('Image source'),
                                                                'description'=>xarML('Image source either a local file listing to choose from, URL, or file upload'),
                                                                'propertyname'=>'radio',
                                                                'propargs' => $imagesourceargs,
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
                                    'xv_file_ext'       =>  array('label'=>xarML('File extensions'),
                                                                'description'=>xarML('A list of allowable file extensions separated by commas'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'definition'),
                                    'xv_basedir'       =>  array(  'label'=>xarML('Base directory'),
                                                                'description'=>xarML('A file, position relative to index.php, containing select options for this field.'),
                                                                'propertyname'=>'textbox',
                                                                'propargs'=>array('size'=>64),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition',
                                                                'configinfo'=>xarML('[Local or Upload Source only.]')
                                                                ),
                                    'xv_longname'   =>  array(  'label'=>xarML('Display file extension'),
                                                                'description'=>xarML('Display file name plus file extension in the drop down selection list.'),
                                                                'propertyname'=>'checkbox',
                                                                 'configinfo'    => xarML('[Local filebrowser only]'),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=> 'definition'),
                                    'xv_allow_duplicates' => array(  'label'=>xarML('Allow duplicates?'),
                                                                'description'=>xarML('Overwrite, create a new version or disallow duplicate filenames uploaded.'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => $allowduppropargs,
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'validation'),
                                    'xv_display' =>  array(  'label'=>xarML('Image on input?'),
                                                                'description'=>xarML('Display the image on input selection?'),
                                                                'propertyname'=>'checkbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'display'),
                                    'xv_max_file_size'   =>  array('label'=>xarML('Maximum file size'),
                                                                'description'=>xarML('Maximum file size for uploaded file'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>15),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('bytes'),
                                                                'ctype' =>'validation'),
                                'xv_scale'   =>  array('label'=>xarML('Image scale'),
                                                                'description'=>xarML('Scale to this size on upload'),
                                                                'propertyname'=>'floatbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => '%'.' '.$uploadonly,
                                                                'ctype' =>'definition'),
                                    'xv_resize_width'   =>  array('label'=>xarML('Resize width'),
                                                                'description'=>xarML('Resize to this width on upload'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('pixels').' '.$uploadonly,
                                                                'ctype' =>'definition'),
                                    'xv_resize_height'   =>  array('label'=>xarML('Resize height'),
                                                                'description'=>xarML('Resize to this height on upload'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('pixels').' '.$uploadonly,
                                                                'ctype' =>'definition'),
                                    'xv_output_width'   =>  array('label'=>xarML('Output display width'),
                                                                'description'=>xarML('Resize to this width on display'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('[Image upload only]'),
                                                                'ctype' =>'display'),
                                    'xv_output_height'   =>  array('label'=>xarML('Output display height'),
                                                                'description'=>xarML('Resize to this height on display'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('[Image upload only]'),
                                                                'ctype' =>'display'),
                                    'xv_output_units'   =>  array('label'=>xarML('Output display units'),
                                                                'description'=>xarML('Resize to this height on display'),
                                                                'propertyname'=>'radio',
                                                                 'propargs' => array('options'=>array(  array('id' =>'px',  'name'=>xarML('px')),
                                                                                                        array('id' =>'%',   'name'=>xarML('%')),
                                                                                                        array('id' =>'em',  'name'=> xarML('em')),
                                                                                                    )
                                                                            ),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('[Image upload only]'),
                                                                'ctype' =>'display'),
                                    'xv_host' =>  array(  'label'=>xarML('Scheme and host'),
                                                                'description'=>xarML('Scheme and host'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition',
                                                                'configinfo'=>xarML('[URL Source only. Optional. Baseurl when different from specified or site baseurl.]')
                                                          ),



                                    );

            $validationarray = array_merge($parentvals,$validations);
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
         $validation = $this->getBaseValidationInfo();
         $args = array();
         $baseInfo = array(
                              'id'         => 12,
                              'name'       => 'image',
                              'label'      => 'Image',
                              'format'     => '12',
                              'validation' => serialize($validation),
                              'source'         => '',
                              'filepath'    => 'modules/base/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
     /**
     * Set the list/regex of allowed file extensions, depending on the syntax used (cfr. image, webpage, ...)
     */
    public function setExtensions($file_ext = null)
    {
        if (isset($file_ext)) {
            $this->xv_file_ext = $file_ext;
        }
        $this->file_ext_list = null;
        $this->file_ext_regex = null;
        if (!empty($this->xv_file_ext)) {
            // example: array('gif', 'jpg', 'jpeg', ...)
            if (is_array($this->xv_file_ext)) {
                $this->file_ext_list = $this->xv_file_ext;

            // example: gif,jpg,jpeg,png,bmp,txt,htm,html
            } elseif (strpos($this->xv_file_ext, ',') !== false) {
                $this->file_ext_list = explode(',', $this->xv_file_ext);

            // example: gif|jpe?g|png|bmp|txt|html?
            } else {
                $this->file_ext_regex = $this->xv_file_ext;
            }
        }
    }
    public function getBaseDirInfo()
    {

        if (isset($this->xv_basedir))  {
           $prop_path = $this->xv_basedir;//often the bit above webroot but may be absolute
            // If the base dir supplied contains {var} then expand that
            // We discard anything that comes before '{var}' and anything up to the next '/' after it.
            // We expect {var} to be used like this: {var}/custom_path
            if (preg_match('/{var}/', $prop_path)) {
              $prop_path = preg_replace('#{var}[^/]*/#', sys::varpath() . '/', $prop_path);
              $this->xv_basedir = $prop_path ;

            }
        } else {
             // No base directory supplied, so default to '{var}/uploads', with no basepath.
            $this->xv_basepath = '';
            $this->xv_basedir = 'var/uploads';
        }

        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/default/images
        if (!empty($this->xv_basedir) && preg_match('/\{theme\}/',$this->xv_basedir)) {
            $curtheme = xarTplGetThemeDir();
            $this->xv_basedir = preg_replace('/\{theme\}/',$curtheme,$this->xv_basedir);
        }

        $uname = xarUserGetVar('uname');
        $uid = xarUserGetVar('uid');
        $udir = $uname . '_' . $uid;

        if (!empty($this->xv_basedir) && preg_match('/\{user\}/',$this->xv_basedir)) {
            $this->xv_basedir = preg_replace('/\{user\}/',$udir,$this->xv_basedir);
        }

        // This one for uploads-hooked operation only.
        if (!empty($this->xv_importdir) && preg_match('/\{user\}/',$this->xv_importdir)) {
            $this->xv_importdir = preg_replace('/\{user\}/',$udir,$this->xv_importdir);
        }

        $paths = sys::getBaseDirs($this->xv_basedir) ;

        return $paths;

    }

}

?>