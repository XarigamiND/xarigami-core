<?php
/**
 * Imagelist property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * @author mikespub <mikespub@xaraya.com>
*/

sys::import('modules.base.xarproperties.Dynamic_FileList_Property');

/**
 * Handle the imagelist property
 * @package dynamicdata
 */
class Dynamic_ImageList_Property extends Dynamic_FileList_Property
{
    public $id         = 35;
    public $name       = 'imagelist';
    public $desc       = 'Image List';

    public $imagetext  = '';
    public $imagealt   = '';
    public $xv_display = true; //display image on input
    public $xv_output_width         = '';
    public $xv_output_height        = '';
    public $xv_output_units         = 'px';
    public  $xv_display_width = '100';
    function __construct($args)
    {
        parent::__construct($args);
        $this->template  = 'imagelist';
        $this->imagetext = xarML('No image');
        $this->imagealt = xarML('Image');

        if (empty($this->xv_file_ext)) $this->setExtensions('gif,jpg,jpeg,png,bmp');

        if (empty($this->xv_basedir)) $this->xv_basedir = sys::varpath();
        if (empty($this->xv_baseurl)) $this->xv_baseurl = $this->xv_basedir;
        // Default selection
       // if (!isset($this->xv_firstline)) $this->xv_firstline = xarML('Select image');
    }

    /**
     * Provision for srcpath so we can display on input but only if image is web browsable
     */
    public function showInput(Array $data = array())
    {
        extract($data);
        if(!empty($basedir)) $this->xv_basedir = $basedir; //pass in basedir
        if (!isset($value)) {
            $value = $this->value;
        }
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $paths =  parent::getBaseDirInfo();
        $basedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $basepath = isset($paths['basepath']) ? $paths['basepath'] : '';
        $filepath = str_replace('//','/',$basedir.'/'.$data['value']);
        $data['srcpath'] = '';
        if (!empty($data['value']) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$data['value']) &&
            file_exists( $filepath) &&
            is_file($filepath)) {
            $srcpath=  $filepath;
            $data['srcpath'] = $srcpath;
        }

        $data['value'] = basename($data['value']);
        $this->value =  $data['value'];
        $data['width'] = isset($width)?$width: $this->xv_display_width;
        if (!isset($validtion)) $validation = ''; //legacy
         if (!isset($options)) $optinos = '';
        if (empty ($options) && empty($validation))
        {
            $data['options']= $this->getoptions();
        }
         $data['basedir'] = empty($basedir)?$basepath:$basedir;
        return parent::showInput($data);
    }
    function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        $paths =  parent::getBaseDirInfo();
        $basedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $baseurl  = isset($paths['basepath']) ? $paths['basepath'] : $basedir;

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

        $filetype = $this->xv_file_ext;

        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
           $srcpath=str_replace('//','/',$basedir.'/'.$value);
        } else {
           $srcpath='';
        }
        if (empty($outputsize) && file_exists($basedir.'/'.$value) && is_file($basedir.'/'.$value)) {
            $data['size'] = getimagesize($basedir.'/'.$value);
        } else {
            $data['size'] = '';
        }

        $data['value']=$value;
        $data['basedir']=$basedir;
        $data['baseurl'] = $baseurl;
        $data['filetype']=$filetype;
        $data['srcpath']=$srcpath;

        if (empty($data['imagetext'])) $data['imagetext'] = $this->imagetext;
        if (empty($data['imagealt'])) $data['imagealt'] = $this->imagealt;
        $template = (!isset($template) || empty($template)) ? 'imagelist' : $template;
        $data['template'] = $template;
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
            $parentvalidations = parent::getBaseValidationInfo();
            $validations = array('xv_display' =>  array(  'label'=>xarML('Image on input?'),
                                                                'description'=>xarML('Display the image on input selection?'),
                                                                'propertyname'=>'checkbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'
                                                          ),
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
     function getBasePropertyInfo()
     {
         $args = array();
         $validation = $this->getBaseValidationInfo();

         $baseInfo = array(
                              'id'         => 35,
                              'name'       => 'imagelist',
                              'label'      => 'Image list',
                              'format'     => '35',
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
}
?>