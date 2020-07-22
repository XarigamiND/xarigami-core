<?php
/**
 * File upload property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Base module
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 *
 * @todo Handle URL decoding of files for storage, and encoding in links.
 *       This is important as URL encoding should *not* appear in a stored filename.
 *
 * Dynamic File Upload Property
 */

sys::import('modules.dynamicdata.class.properties');
/**
 * Class to handle file upload properties
 */
class Dynamic_FileUpload_Property extends Dynamic_Property
{
    public $id         = 9;
    public $name       = 'fileupload';
    public $desc       = 'File Upload';
    public $file_mode = 0777;
    // Standard properties.
    public $xv_max_file_size    = 1000000; //bytes
    public $xv_basepath         = null;
    public $xv_basedir          = '';
    public $xv_file_ext         = 'gif,jpg,jpeg,png,bmp,pdf,doc,txt';
    public $xv_file_mode        = 0777;
    public $xv_display          = FALSE;
    public $xv_size             = 30; //upload input
    public $xv_display_width    = 160; //width of a displayed image on input
    // 'uploads' module properties.
    public $UploadsModule_isHooked = false;
    public $xv_multiple         = true;
    public $xv_importdir        = NULL;
    public $xv_style = 'icon'; //uploads
    public $methods  =  array('trusted'  => false,
                               'external' => false,
                               'upload'   => false,
                               'stored'   => false,
                              );
    public $xv_methods = array(); //holds GUI config in checkbox list format
    public $xv_allow_duplicates     = 2;    //overwrite old files
    public $xv_use_temp_file        = false;
    public $xv_obfuscate_filename   = false;
    public $remove_leftover_values  = true; //used in 2x
    //image options
    public $xv_scale                = NULL; //100%
    public $xv_resize_width         = NULL;
    public $xv_resize_height        = NULL;
    public $xv_output_width         = '';
    public $xv_classname            = '';
    public $xv_output_height        = '';
    public $xv_output_units         = 'px';
    public $phpmaxerror = '';
    // This is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag.
    public $upload                  = true;

    // Constructor method.
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'fileupload';
        $this->filepath = 'modules/base/xarproperties';

        if (empty($this->id)) $this->id = $this->name;

      // Determine if the uploads module is hooked to the calling module.
        // If so, we will use the uploads modules functionality.

       if ($this->UploadsModule_isHooked == TRUE || xarCoreCache::getCached('Hooks.uploads', 'ishooked') ) {
            $this->UploadsModule_isHooked = TRUE;
            $this->xv_max_file_size = xarModGetVar('uploads', 'file.maxsize');
            $this->xv_basepath = getcwd();
            $this->xv_file_ext = '';
            $basedir    = xarModGetVar('uploads', 'path.uploads-directory');
            $importdir  = xarModGetVar('uploads', 'path.imports-directory');
            if (!isset($this->xv_basedir) || empty($this->xv_basedir)) $this->xv_basedir =  $basedir;
            if (!isset($this->xv_importdir)) $this->xv_importdir = $importdir ;
            //format for new uploads and checkbox lists for validations etc
            if (!isset($this->xv_methods) || empty($this->xv_methods))
            {
                 $defaultmethods = array(
                    'trusted'  => xarModGetVar('uploads', 'dd.fileupload.trusted')  ? TRUE : FALSE,
                    'external' => xarModGetVar('uploads', 'dd.fileupload.external') ? TRUE : FALSE,
                    'upload'   => xarModGetVar('uploads', 'dd.fileupload.upload')   ? TRUE : FALSE,
                    'stored'   => xarModGetVar('uploads', 'dd.fileupload.stored')   ? TRUE : FALSE
                );
                foreach($defaultmethods as $k => $v) {
                    if ($v === TRUE) {
                         $this->xv_methods[] =  $k;
                    }
                }
            }
            //old style format for input displays
            foreach($this->xv_methods as $k) {
                if (in_array($k,$this->xv_methods)) {
                    $this->methods[$k] =  TRUE;
                } else {
                    $this->methods[$k] =  FALSE;
                }
            }

        }
        //else {
            // FIXME: this doesn't take into account the itemtype or non-main module objects. [FIXED]
            // 2008-11-11 judgej@xaraya.com Get the list of hooks from the module and itemtype that this
            // dd object is a part of. Default to the current module if we are not a part of a DD object.
            if (isset($this->_moduleid) && isset($this->_itemtype)) {
                $list = xarModGetHookList(xarMod::getName($this->_moduleid), 'item', 'transform', $this->_itemtype);
            } else {
                // The old method as fallback.
                $list = xarMod::getHookList(xarMod::getName(), 'item', 'transform');
            }
            foreach ($list as $hook) {
                if ($hook['module'] == 'uploads') {
                    $this->UploadsModule_isHooked = TRUE;
                   xarCoreCache::setCached('Hooks.uploads', 'ishooked', TRUE);
                    break;
                }
            }
       // }

        $this->xv_basedir = isset($basedir) ? $basedir :$this->xv_basedir; //may be absolute or above the webroot
        $this->xv_basepath = isset($basepath) ? $basepath :$this->xv_basepath; //absolute path

        $this->xv_file_ext = isset($this->xv_file_ext)?trim($this->xv_file_ext):'';
        $this->xv_max_file_size = isset($max_file_size)?$max_file_size:trim($this->xv_max_file_size);
    }
   function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // Store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }

        return $this->validateValue($value);

    }
    function validateValue($value = null)
    {
        // The variable corresponding to the file upload field is no longer set in PHP 4.2.1+
        // but we're using a hidden field to keep track of any previously uploaded file here
        //cannot check for upload here with parent validation as it will not be able to find the value
        //handle it separately elsewhere
        //if (!parent::validateValue($value)) return false;

        if (!isset($value)) $value = $this->value;
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_' . $this->id;
        }


        $display = $this->xv_display;
        // Retrieve new value for preview + new/modify combinations.
        if (xarCoreCache::isCached('DynamicData.FileUpload', $name)) {
            $this->value = xarCoreCache::getCached('DynamicData.FileUpload', $name);
            return true;
        }
        // Get the filename from the value, which could contain some path information
        $isfile = is_file($value); //could just be a subdir eg {user} directory if empty
        $fileName = basename($value);

        // If the uploads module is hooked in, use it's functionality instead.
        // Move this to another method, to keep the non-hooked method cleaner.
        if ($this->UploadsModule_isHooked == TRUE) {
            return $this->_validateValueUploadsHooked($name);
        }

        // The form item for uploading new files.
        $upname = $name.'_upload';
        $filetype = $this->xv_file_ext;
        if (isset($_FILES[$upname])) {
            $file =& $_FILES[$upname];
        } else {
            $file = array();
        }
        if (isset($this->validation)) {
            $this->parseValidation($this->validation);
        }
        $paths = $this->getBaseDirInfo();
        $newbasedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $newbasepath= isset($paths['basepath']) ? $paths['basepath'] : '';



        if ( (isset($file['size']) && ($file['size'] > $this->xv_max_file_size)) || (isset($file['error']) &&  $file['error']==2)) { //fallback - should be ok
            $this->invalid = xarML('File exceeds maximum allowed file size');
            return false;
        }
        if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && ($file['size'] > 0 )) {

            // if the uploads module is hooked (to be verified and set by the calling module)
            if (!empty($_FILES[$upname]['name'])) {
                $fileName = xarVarPrepForOS(basename(strval($file['name'])));

                $filetype = $this->xv_file_ext;
                $this->setExtensions($filetype);

                if (!$this->validateExtension($_FILES[$upname]['name'])) {
                    $this->invalid = xarML('Invalid file type - must be one of:  #(1)',$filetype);
                    return false;
                } else {
                     $resized = FALSE;
                     $resizedimage = '';
                     $resizedname = '';
                    //first check if we want resizing of uploaded file
                    if (!is_null($this->xv_scale) || !is_null($this->xv_resize_height) || !is_null($this->xv_resize_width)) {
                        //some resizing wanted
                        if (!class_exists('xarImage')) {
                           sys::import('modules.base.xarclass.xarImage');
                        }
                        $sizedimage = new xarImage();
                        $sizedimage->getImage($file['tmp_name']);
                        $filesuffix = '';
                        if ($sizedimage->image) {
                            if (isset($this->xv_scale) && !empty($this->xv_scale)) {
                                $sizedimage->scaleImage($this->xv_scale);
                                $filesuffix = '_scale_'.$this->xv_scale;
                            }
                            if (isset($this->xv_resize_width) && !empty($this->xv_resize_width)) {
                                $sizedimage->resizeByWidth($this->xv_resize_width);
                                 $filesuffix = '_width_'.$this->xv_resize_width;
                            }else if (isset($this->xv_resize_height) && !empty($this->xv_resize_height)) {
                                $sizedimage->resizeByHeight($this->xv_resize_height);
                                  $filesuffix = '_height_'.$this->xv_resize_height;
                            }

                            $fileext = $this->getExtension(basename($fileName));
                            $newname =  rtrim(basename($fileName,$fileext),'.');
                            $fileName = $newname.$filesuffix.'.'.$fileext;
                            $resizedimage = $sizedimage->image;

                        } else {
                            $this->invalid = xarML('Image not supported for resize. Must be one of: #(1)',$filetype);
                            return false;
                        }

                    }

                    $destination = str_replace('//', '/',  $newbasepath  . '/' .  $newbasedir  . '/'. $fileName);
                    $dironly = dirname($destination);
                    // Make sure the directory exists, create it if not.
                    if (!file_exists(dirname($destination))) {
                        // If the basePath does not exist, then stop.
                        // We will only create the basedir under the basePath.
                        if (!is_dir($newbasepath)) {
                            $this->invalid = xarML('Configuration error: no valid base path: '.$newbasepath);
                            return false;
                        }
                        // Allow for recursion (the recursion flag is available from PHP5 only)
                        // Loop through each level in the basedir, creating a folder.
                        $basedir_parts = explode('/', trim($newbasedir, '/'));
                        $destination_walk =$newbasepath;
                        foreach($basedir_parts as $basedir_part) {
                            $destination_walk .= '/' . $basedir_part;

                            // Directory may already exist.
                            if (is_dir($destination_walk)) continue;

                            // A file may stand in our way.
                            if (file_exists($destination_walk)) {
                                $this->invalid = xarML('file exists in place of directory');
                                $this->value = null;
                                return false;
                            }

                            // We may need to change umask or set the permissions explicitly, so that files
                            // can be managed outside of the web processes.
                            if (!mkdir($destination_walk, $this->xv_file_mode)) {
                                $this->invalid = xarML('failed to create directory for file ');
                                $this->value = null;
                                return false;
                            }
                            @chmod($destination_walk, $this->xv_file_mode);
                        }
                    }

                    //validations for existence
                    //jojo - tidy up and move out of here for clarity to new method
                    if (file_exists($destination)) {

                        if ($this->xv_allow_duplicates == 0) { //not allowed
                            $this->invalid = xarML('The file already exists, please add a file with another name');
                            $this->value = null;
                            return false;
                        }elseif ($this->xv_allow_duplicates == 1) { //new versions
                            //ok we have to change the name
                            //we need the extension
                            $exttype = $this->getExtension($fileName);
                            $filePart = rtrim(basename($fileName,$exttype),'.');
                            $version = 0;
                            while(file_exists($destination)){
                                $version++;
                                $tempName = $filePart.'_'.$version.'.'.$exttype;
                                $destination = str_replace('//', '/',  $dironly  . '/'. $tempName);
                                 $fileName = $tempName;
                            }

                        }
                    } //otherwise if 2 = overwrite

                    try {
                        if (!empty($resizedimage)) {
                             $sizedimage->saveImage($destination);
                        } else {
                            move_uploaded_file($file['tmp_name'], $destination);
                        }
                    } catch (Exception $e) {

                       if (!is_writeable(dirname($destination))) {
                            $this->invalid = xarML('The destination directory is not writeable, or you do not have write permission to it');
                            $this->value = null;
                            return false;
                        } else {
                            $this->invalid = xarML('File upload failed');
                            $this->value = null;
                            return false;
                        }
                    }
                }

                $this->value = $fileName;
                // save new value for preview + new/modify combinations
                xarCoreCache::setCached('DynamicData.FileUpload', $name, $fileName);
            } else {
                // TODO: assign random name + figure out mime type to add the right extension ?
                $this->invalid = xarML('file name for upload');
                $this->value = $fileName;
//                return false;
            }
        } elseif (xarCoreCache::isCached('DynamicData.FileUpload', $name)) {
            // Retrieve new value for preview + new/modify combinations.
            $this->value = xarCoreCache::getCached('DynamicData.FileUpload', $name);
        } elseif (!empty($value) && ($isfile ==TRUE) && !is_numeric($value) && !stristr($value, ';')) {

            if (!$this->validateExtension($fileName)) {
                $this->invalid = xarML('Invalid file type');
                $this->value = null;
                return false;
            } elseif (!file_exists($newbasepath . '/' . $newbasedir . '/'. $fileName) || !is_file($newbasepath . '/' . $newbasedir . '/'. $fileName)) {
                $this->invalid = xarML('File not found');
                $this->value = null;
                //return false;
            }
            $this->value = $value;
        } else {
            $this->value = '';
        }

        if ($this->xv_allowempty != 1 && $value =='' && $this->value == '') { //the already uploaded and the about to be uploaded with preview
            $thename = !empty($this->label)?$this->label:$this->name;
            $this->invalid = xarML('Error: #(1) cannot be empty, please add a file', $thename);
            //$this->value = null;
            return false;
        }

        // Make sure the value has the basedir prepended, if available.
        $this->value = $fileName;
        return true;
    }

    // Validate functionality for when uploads are hooked.
    function _validateValueUploadsHooked($name)
    {
        $this->parseValidation($this->validation);
        $paths = $this->getBaseDirInfo();
        $newbasedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $newbasepath= isset($paths['basepath']) ? $paths['basepath'] : '';

        // set override for the upload/import paths if necessary
        if (!empty($this->$newbasedir) || !empty($this->xv_importdir)) {
            $override = array();
            if (!empty($newbasedir)) {
                $override['upload']= array('path' => $newbasedir);
            } elseif (!empty($newbasepath)) {
                  $override['upload'] = array('path' => $newbasepath);
            }
            if (!empty($this->xv_importdir)) $override['import'] = array('path' => $this->xv_importdir);
        } else {
            $override = null;
        }
        $display = isset($display)?$display: FALSE;
        $extensions = isset($this->xv_file_ext) ?  $this->xv_file_ext : '';
        $width= $this->xv_display_width;

        try {
            $return = xarMod::apiFunc('uploads', 'admin', 'validatevalue',
                        array(
                            'id' => $name, // not $this->id
                            'value' => isset($value) ? $value : $this->value, //jojo - default was null
                            // pass the module id, item type and item id (if available) for associations
                            'moduleid' => $this->_moduleid,
                            'itemtype' => $this->_itemtype,
                            'itemid'   => !empty($this->_itemid) ? $this->_itemid : null,
                            'multiple' => $this->xv_multiple,
                            'format' => 'fileupload',
                            'methods' => $this->methods,
                            'extensions'=> $extensions,
                            'display'  => $display ?$display:$this->xv_display,
                            'override' => !isset($override)?null:$override,
                            'maxsize' => $this->xv_max_file_size,
                            'width'   => isset($width)?$width: $this->xv_display_width,
                            'scale' => $this->xv_scale,
                            'resize_height' => $this->xv_resize_height,
                            'resize_width' => $this->xv_resize_width,
                            'allow_duplicates'=> isset($allow_duplicates)? $allow_duplicates : $this->xv_allow_duplicates,
                        )
                    );
         } catch (Exception $e) {
             $this->invalid  =  $e->getMessage();
            $this->value = null;
            return false;
        }
        if (!isset($return) || !is_array($return) || count($return) < 2) {
            $this->value = null;
            return false;
        }
        if ($this->xv_allowempty != 1 && (!isset($return[1]) || empty($return[1]))) {
            $thename = !empty($this->label)?$this->label:$this->name;
            $this->invalid = xarML('#(1) cannot be empty', $thename);
            $this->value = null;
           return false;
        }
        if (empty($return[0])) {
            $this->value = null;
            $this->invalid = xarML('value');
            return false;
        } else {
            if (empty($return[1])) {
                $this->value = '';
            } else {
                $this->value = $return[1];
            }

            // save new value for preview + new/modify combinations
            xarCoreCache::setCached('DynamicData.FileUpload', $name, $this->value);
            return true;
        }
    }

    public function showInput(Array $data = array())
    {
        extract($data);

        if (empty($name)) $name = 'dd_' . $this->id;
        if (empty($id)) $id = $name;
        if (!isset($value)) $value = $this->value;

        $upname = $name . '_upload';
        $this->upname = $upname;

        //ensure we are working with expanded vars in file names and normalised paths
        //make sure we pass in any basedir for processing
        $this->xv_basedir = isset($basedir)?$basedir: $this->xv_basedir;

        $paths = $this->getBaseDirInfo();
        $newbasedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $newbasepath = isset($paths['basepath']) ? $paths['basepath'] : '';

        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarCoreCache::setCached('Hooks.dynamicdata', 'withupload', 1);

        //some display settings
        $data['display'] = isset($display)  ? $display: $this->xv_display;

        $data['width']      = isset($width) && !empty($width) ? $width : $this->xv_output_width;
        $data['size']     = isset($size) && !empty($size) ? $size : $this->xv_size;

        //fix max file size for display on input of info
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        $maxsize = isset($maxsize) ?$maxsize : $this->xv_max_file_size; //bytes
        $maxmb = $maxsize/(1024*1024); //bytes to Mb
        if ($maxmb >$upload_mb) {
            $this->xv_max_file_size = $upload_mb * 1024 * 1024; //in bytes
            $maxsize = $this->xv_max_file_size;
            $this->phpmaxerror = xarML('Admin notice: The max upload size set in the configuration of #(1) Mb is greater than your php maximum allowed of #(2) Mb',$maxmb, $upload_mb);
        }
        $data['maxsize'] = (int)$maxsize;
        $data['phpmaxerror'] = $this->phpmaxerror;
        if ( $maxsize  >= (1024 * 1024)) {
            $data['filemax'] = number_format(($maxsize /(1024*1024))) . ' '.xarML('Mb');
        } elseif ($maxsize>0) {
             $data['filemax'] = number_format($maxsize/1 ). ' '.xarMl('Bytes');
        } else {
             $data['filemax'] =  '0 '.xarMl('Bytes');
        }
        $extensions = isset($extensions)? $extensions:  $this->xv_file_ext;

        //check for uploads hooks
        if ( xarCoreCache::getCached('Hooks.uploads', 'ishooked') ) {
            $this->parseValidation($this->validation);

            if (!empty($this->xv_file_ext)) {
                $extensions = $this->xv_file_ext;
            } else {
                $extensions = '';
            }
            // User must have hooked the uploads module after uploading files directly.
            // CHECKME: remove any left over values - or migrate entries to uploads table ?
            if (!empty($value) && !is_numeric($value) && !stristr($value, ';')) $value = '';

            // set override for the upload/import paths if necessary
            if (!empty($newbasedir) || !empty($this->xv_importdir)) {
                $override = array();
                if (!empty($newbasedir)) {
                    $override['upload'] = array('path' => $newbasedir);
                }
                if (!empty($this->xv_importdir)) {
                    $override['import'] = array('path' => $this->xv_importdir);
                }
            } else {
                $override = null;
            }
            $args =   array('id'         => $name, // not $this->id
                           'value'      => $value,
                           'fileName'   => $value,//backward compat
                           'multiple'   => $this->xv_multiple,
                           'format'     => 'fileupload',
                           'methods'    => $this->methods,
                           'override'   => $override,
                           'size'       => $data['size'] ,
                           'maxsize'    => $data['maxsize'] ,
                           'display'    => $data['display'],
                           'width'      => $data['width'],
                           'invalid'    => $this->invalid,
                           'phpmaxerror' => $data['phpmaxerror'],
                           'extensions' => $extensions,
                           'filemax'    => $data['filemax']);

            return xarMod::apiFunc('uploads','admin','showinput',$args);
        }

        // user must have unhooked the uploads module
        // remove any left over values
        // Only non-hooked functionality below.

        // Remove any left over values.
        if (!empty($value) && (is_numeric($value) || stristr($value, ';'))) $value = '';


        $data['value'] = $value;
        $data['extensions'] = $extensions;


        $data['basedir'] = $newbasedir;
        $data['basepath'] = $newbasepath;
        $fileName = basename($value);

        //check we don't have something like username in the filename string
        $fileName = str_replace( $data['basedir'] ,'',$fileName);
        $data['fileName']= $fileName;

        //some info in case we want to display a file on input eg if it's an image
        // The directory the file is (or should be) in.

        $dir= $data['basepath']. $data['basedir'];
        $file_path = $dir.  $fileName;

         //we don't know what type of file this is esp if uploads not hooked
        //rudimentary check for common image types png,jpg,jpeg,gif,tiff,bmp
        $data['src'] = '';
        $pathinfo['extension']='';
       if (( $data['display'] == 1)  && !empty($data['value']) && !empty($data['basedir'])) { //if base dir is not empty it is web accessible below web root
             $mimefail = false;
            $imgarray = array('png','jpg','jpeg','gif','tiff','bmp','JPEG','JPG');
            if (xarMod::isAvailable('mime')) {
                try {
                    $filetype = xarMod::apiFunc('mime','user','analyze_file',array('fileName'=>$file_path));
                     if (substr($filetype,0,5) == 'image') {
                        $data['src'] = $data['basedir'] .xarVarPrepForDisplay($fileName);
                     }
                } catch (Exception $e) {
                    $mimefail = true;
                }
            }

           if (!xarMod::isAvailable('mime') || $mimefail) {
                $pathinfo = pathinfo($fileName);
                $fileext = isset($pathinfo['extension']) ?$pathinfo['extension']: '';
                if (in_array($fileext,$imgarray)) {
                    $data['src']= $data['basedir'] .xarVarPrepForDisplay($fileName);
                }
            }
            //now what about {var} expansion
            if (preg_match('/{var}/', $data['src'])) {
                    $data['src'] = preg_replace('#{var}[^/]*/#', sys::varpath() . '/', $data['src']);
            }
            //now what about {theme} expansion
            if (preg_match('/{theme}/', $data['src'])) {
                    $data['src'] = preg_replace('#{theme}[^/]*/#', xarTplGetThemeDir() . '/', $data['src']);
            }
            $uname = xarUserGetVar('uname');
            $uid = xarUserGetVar('uid');
            $udir = $uname . '_' . $uid;
            //now what about {user} expansion
            if (preg_match('/{user}/', $data['src'])) {
                    $data['src'] = preg_replace('#{user}[^/]*/#',$udir, $data['src']);
            }
            $data['src'] = str_replace(sys::web(),'',rtrim($data['src']));
            if (!is_file( $data['src'])) {
               $data['src'] ='';
               $display=false; //no file to see
            }
        }

        $data['basePath'] =  $data['basepath']; //backward compatibility - set for deprecation
        $data['name']       = $name;
        $data['value']      = $value;
        $data['upname']     = $upname;
        $data['extensions'] = $extensions;
        $data['fileName']   = isset($data['fileName'])?$data['fileName']:$value;
       $data['filePath'] =  $file_path ;

        return parent::showInput($data);
    }

    function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) {
            $value = $this->value;
        }
        //ensure we have property formed paths
        $paths = $this->getBaseDirInfo();
        $class= isset($class)? $class:$this->xv_classname;
        $output_width = isset($output_width)?$output_width: $this->xv_output_width;
        $output_height = isset($output_height)?$output_height: $this->xv_output_height;
        $output_units = isset($output_units)?$output_units: $this->xv_output_units;
        $newbasedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $newbasepath = isset($paths['basepath']) ? $paths['basepath'] : '';
         //check for uploads hooks
        if ($this->UploadsModule_isHooked == TRUE || xarCoreCache::getCached('Hooks.uploads', 'ishooked') || $this->UploadsModule_isHooked) {
            $this->parseValidation($this->validation);
            $out =  xarMod::apiFunc('uploads', 'user', 'showoutput',
                array(
                    'value' => $value,
                    'format' => 'fileupload',
                    'multiple' => $this->xv_multiple,
                     'style' => !isset($style)? $this->xv_style:$style,
                     'class' => $class,
                     'output_height' => $output_height,
                     'output_width' => $output_width,
                     'output_units' => $output_units
                     )
            );
            //don't return empty arrays
            if (!empty($out)) return $out;
        }

             // Non upload-hooked code below.
        // Note: you can't access files directly in the document root here
        if (!empty($value)) {
            if (is_numeric($value) || stristr($value, ';')) {
                // User must have unhooked the uploads module.
                // Remove any left over values.
                // i.e. the value stored when hooked is incomptible with the value
                // stored when not hooked.
                return '';
            }

             $outputsize = '';
            if (!isset($output_units)) $output_units = $this->xv_output_units;
            if (isset($output_width) && !empty($output_width)) {
                $outputsize = 'width:'.$output_width.$output_units.';';
            } elseif (isset($output_height) && !empty($output_height)) {
                 $outputsize = 'height:'.$output_height.$output_units.';';
            }

            $data['outputsize'] = $outputsize;
            // The 'value' contains on the filename, so we
            // need to append it to the basePath and baseDir to get the physical file.
            // FIXME: the template treats the presence of the basedir as the all-go to
            // provide a link to the file. The file may *not* be in a web-accessible
            // directory, so the assumption cannot be made.
            $mime = '';
            $file_path = str_replace('//','/',$newbasepath . $newbasedir. $value);
            if (!file_exists($file_path) || !is_file($file_path)) {
                // The file is no longer there.
                $value = NULL;
            } else {
               if (extension_loaded('fileinfo')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                   $mime = finfo_file($finfo, $file_path);
                } else {
                    $mime = '';
                }
            }
            // The directory the file is (or should be) in.
            $data['dir'] = $newbasepath . $newbasedir;
            $data['basedir'] = $newbasedir;

            $data['fileName'] = isset($fileName)?$fileName : $value;
            $data['value'] = $value;
            $isimage = false;
            $mimeimage = '';
            if (xarModIsAvailable('mime')) { //use it but don't return any errors if it excepts
                try  {
                   $mimetype =  xarModAPIFunc('mime','user','extension_to_mime',array('fileName' =>  $data['fileName']));
                   $mimeimage = xarModAPIFunc('mime','user','get_mime_image',array('mimeType' => $mimetype));
                } catch (Exception $e) {
                    //do nothing - we just go without the image
                }
            }
            $data['mimeimage'] = $mimeimage;
            //file is under webroot
            $data['fileuri'] = str_replace('//','/',$newbasedir. $value);
            if ( is_file($data['fileuri']) && !empty($mime) && xarModIsAvailable('mime') && substr($mime,0,5) == 'image')
            {
               $isimage = true;
            }
            $data['isimage'] = $isimage;
            $data['template'] = isset($template)?$template:$this->template;
            return parent::showOutput($data);
        } else {
            return '';
        }

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
    public function getExtension($filename)
    {
        $filename = rtrim($filename,'.');
        $pos = strrchr($filename, '.');
        if ($pos !== false) {
             $extension = ltrim($pos,'.');
        } else {
            $extension = '';
        }
        return $extension;
    }
    public function validateExtension($filename = '')
    {
/*
        $filetype = $this->xv_file_ext;
        $filename = xarVarPrepForOS(basename(strval($filename)));
        return (!empty($filetype) && preg_match("/\.$filetype$/",$filename));
*/
        $pos = strrpos($filename, '.');
        if ($pos !== false) {
            $extension = substr($filename, $pos + 1);
        } else {
            // in case we already got the extension from $dir->getExtension()
            $extension = $filename;
        }

        if (!empty($this->file_ext_list) &&
            !in_array($extension, $this->file_ext_list)) {
            return false;
        }
        if (!empty($this->file_ext_regex) &&
            !preg_match('/^' . $this->file_ext_regex . '$/', $extension)) {
            return false;
        }
        return true;
    }

    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvalidation = parent::getBaseValidationInfo();
            $allowduppropargs = array('options' =>array(
                                                    array('id'=>0,  'name' =>xarML('Not allowed')),
                                                    array('id'=>1,  'name' =>xarML('Create a new version')),
                                                    array('id'=>2,  'name' =>xarML('Overwrite existing file'))
                                                      )
                                                      );
            $validations = array('xv_file_ext'       =>  array('label'=>xarML('File extensions'),
                                                                'description'=>xarML('A list of allowable file extensions separated by commas'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'validation'),
                                    'xv_basedir'       =>  array(  'label'=>xarML('Base directory'),
                                                                'description'=>xarML('A directory path to where files can be uploaded (no ending slash).'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>0,
                                                                'ctype'=>'definition'),
                                    'xv_allow_duplicates' => array(  'label'=>xarML('Allow duplicates?'),
                                                                'description'=>xarML('Overwrite, create a new version or disallow duplicate filenames uploaded.'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => $allowduppropargs,
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'validation'),
                                    'xv_max_file_size'   =>  array('label'=>xarML('Maximum file size'),
                                                                'description'=>xarML('Maximum file size for uploaded file'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>15),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('bytes'),
                                                                'ctype' =>'validation'),
                                    'xv_display' =>  array(  'label'=>xarML('Display image on input?'),
                                                                'description'=>xarML('Display the image on input selection?'),
                                                                'propertyname'=>'checkbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'display'),
                                    'xv_display_width' =>  array(  'label'=>xarML('Input image display width'),
                                                                'description'=>xarML('Width of image displayed on input in pixels'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('max'=>4),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'display',
                                                                'configinfo'    => xarML('pixels'),
                                                                ),
                                     'xv_scale'   =>  array('label'=>xarML('Image scale'),
                                                                'description'=>xarML('Scale to this size on upload'),
                                                                'propertyname'=>'floatbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('% [Image upload only]'),
                                                                'ctype' =>'definition'),
                                    'xv_resize_width'   =>  array('label'=>xarML('Resize width'),
                                                                'description'=>xarML('Resize to this width on upload'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('pixels [Image upload only]'),
                                                                'ctype' =>'definition'),
                                    'xv_resize_height'   =>  array('label'=>xarML('Resize height'),
                                                                'description'=>xarML('Resize to this height on upload'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('pixels [Image upload only]'),
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



                                    );


            //jojo - why is this not Hooks.uploads not being picked up from cache? Investigate - leave loaded for now and add a note for users
            if (isset($this->_moduleid) && isset($this->_itemtype)) {
                    $list = xarModGetHookList(xarMod::getName($this->_moduleid), 'item', 'transform', $this->_itemtype);
            } else {
                // The old method as fallback.
                $list = xarMod::getHookList(xarMod::getName(), 'item', 'transform');
            }
            foreach ($list as $hook) {
                if ($hook['module'] == 'uploads') {
                    $this->UploadsModule_isHooked = TRUE;
                   xarCoreCache::setCached('Hooks.uploads', 'ishooked', TRUE);
                    break;
                }
            }
             if (xarCoreCache::getCached('Hooks.uploads', 'ishooked') ||  $this->UploadsModule_isHooked == 1) {
              $hooktext = xarML('Uploads module is HOOKED');
                $methodargs = array( array('id'=>'trusted',  'name' =>'trusted'),
                                 array('id'=>'external', 'name' =>'external'),
                                 array('id'=>'upload',  'name' =>'upload'),
                                 array('id'=>'stored',  'name' =>'stored')
                               );
                $validations2 = array( 'xv_methods' =>  array(  'label'=>xarML('Allowed methods'),
                                                                'description'=>xarML('Allowed methods for file selection when the Uploads module is hooked'),
                                                                'propertyname'=>'checkboxlist',
                                                                'propargs' => array('options'=>$methodargs),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => $hooktext,
                                                                'ctype'=>'definition'),

                                        'xv_style'       =>  array('label'=>xarML('Output type'),
                                                                'description'=>xarML('Mime download, image display (if available), or simple array'),
                                                                'propertyname'=>'radio',
                                                                 'propargs' => array('options'=>array(  array('id' =>'',        'name'=>xarML('Data')),
                                                                                                        array('id' =>'icon',     'name'=>xarML('Download')),
                                                                                                        array('id' =>'transform', 'name'=> xarML('Transform (image if available)')),
                                                                                                    )
                                                                            ),
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'display'),
                                                                );

                $validations = array_merge($validations,$validations2);
            }
            $validationarray = array_merge($parentvalidation,$validations);
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
        $validations = $this->getBaseValidationInfo();

        $baseInfo = array(
            'id'         => 9,
            'name'       => 'fileupload',
            'label'      => 'File upload',
            'format'     => '9',
            'validation' => serialize($validations),
            'source'         => '',
            'filepath'    => 'modules/base/xarproperties',
            'dependancies'   => '',
            'requiresmodule' => 'base',
            'aliases'        => '',
            'args'           => serialize($args),
        );

        return $baseInfo;
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