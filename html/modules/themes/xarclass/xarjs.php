<?php
/**
 * Xarigami CSS class library
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 * @author Jason Judge
 * @author Jo Dalle Nogare
 */
/**
 * Register function
 *
 * Register javascript in the queue for later rendering
 * @author Jo Dalle Nogare
 * @author Jason Judge
 * @access public
 * @param  array    $args array of optional parameters<br/>
 *         array    $definition array of field definitions containing type, position etc, optional
 *         string   $args[type] type of js to include, either src or code, optional, default src<br/>
 *         string   $args[code] string containing js code to include if $type is code<br/>
 *         mixed    $args[filename] array containing filename or list of filenames in a comma delimited list
 *                  name of file(s) to include, required if $type is src, or<br/>
 *                  file(s) to get contents from if $type is code and $code isn't supplied<br/>
 *         string   $args[module] name of module to look for file(s) in, optional, default current module<br/>
 *         string   $args[position] position to place the js, eg render in head or body, optional, default head<br/>
 *         string   $args[index] optional index in queue, any duplicate would be overridden eg duplication instances of same calendar on a page<br/>
 *         integer  $args['weight'] optional parameter to specify weight, the lower the position the earlier the js is loaded, defaults to WEIGHT
 *         string   $args['libname'] name of the js library (in scripts/[libname])
 *         string   $args['libfile'] name of the library/framework file if different to the libname, can be a URL
 *         string   $args['plugin']  name of the plugin (points to scripts[$libname]/[$plugin]) optional,
 *         string   $args['pluginfile'] name of the plugin filename(s) if different to $plugin, as name or comma separated list
 *         string   $args['style']  name of the plugin style file or files to load (comma separated) as override of original
 *         string   $args['comment']  name comment to add in front of js
 * @return boolean TRUE on success
 * @throws none
 */
/**
 * Base Js Class
**/
class xarJs extends xarObject
{
    const XARSCRIPTDIR = 'scripts';

    private static $queue;
    public $weight          = 10; //default weight for lib plugins, library is default at 9 to ensure it loads first
    public $definition      = array();
    public $type            = 'src';
    public $code            = '';
    public $filename        = '';
    public $module          = '';
    public $position        = 'head';
    public $index           = '';
    public $libname         = '';
    public $libfile         = '';
    public $plugin          = '';
    public $pluginfile      = '';
    public $style           = '';
    public $codedir;
    public $themedir;
    public $jsaggregate = FALSE; //switch to turn on aggregationg
    public $jscachedir     = './var/cache/styles';
    public $basepath;
    public $aggregate       = TRUE;     //aggregate==FALSE - exclude from aggregation
    public $compress        = FALSE;   //to use gzip or other compression as available
    public $comment        = '';     //comment to add in template

    /**
     * @TODO - <jojo> finish constructor, replace $args arrays everywhere
     */
    public function __construct($args = array())
    {
        extract($args);
        $this->codedir      = sys::code();
        $this->themedir     = xarTplGetThemeDir();
        $basepath           = xarServerGetBaseURL();
        $this->basepath     = $basepath;
        $this->jsaggregate = xarModVars::get('themes','jsaggregate');
        $this->jscachedir  = xarModVars::get('themes','csscachedir'); //same as for styles
        $this->compress     = xarModVars::get('themes','compress');
        $this->aggregate    = isset($aggregate) && !is_null($aggregate)?$aggregate : $this->aggregate;
        $this->comment      = isset($comment) ?$comment : $this->comment;
    }

    public function registerJs($args)
    {
        if (isset($args['definition']) && is_array($args['definition'])) {
           // merge definition into arguments
            foreach ($args['definition'] as $defkey => $defval) {
                $args[$defkey] = $defval;
            }
        }
        extract($args);

        $code = !isset($code) ? $this->code: $code;
        $weight = !isset($weight) || empty($weight) ? $this->weight: $weight;
        $filename =  isset($filename) ? $filename :'';
        $libname = isset($libname)? trim($libname) :'';
        $style    = isset($style)? $style : '';
        $aggregate = isset($aggregate) && !is_null($aggregate)? $aggregate:$this->aggregate;
        $comment = isset($comment) ? $comment: $this->comment;
        $libfile = isset($libfile)? $libfile : '';
        $ishttp = ''; //initialize
        $ishttps = ''; //initialize
        $options = isset($options)? $options : '';

        if (isset($libfile) && !empty($libfile)) {
           $ishttp = substr($libfile,0,7);
           $ishttps = substr($libfile,0,8);
        }
        if (isset($filename) && !empty($filename)) {
           $ishttp = substr($filename,0,7);
           $ishttps = substr($filename,0,8);
        }
        $isexternal = ($ishttp == 'http://') || ($ishttps == 'https://')? TRUE:FALSE; //can also be https

        //process any specific module located javascript if there is any, module could be a parameter even for library files
        if (empty($libname)) {

            if (!empty($code)) {
                // If the 'code' attribute has been passed in, then some inline code
                // has been supplied - we don't need to read anything from a file then.
                $index = !empty($index) ? $index : md5($code);

                if (!empty($options)) {
                    $jsoptions = $this->_addJsVars($options);
                    $varcomment = xarML('Variables ');
                    $this->addJs($position, $type, "\n// ".$varcomment."\n".$jsoptions."\n".$code, $index, $weight, $aggregate);
                } else {
                    $this->addJs($position, $type, $code, $index, $weight, $aggregate);
                }

            } elseif (!empty($filename)) {
                // Return the code to call up the javascript file.
                // Only the file version is supported for now.
                $params = array('module'    =>$module,
                                'type'      =>$type,
                                'filename'  =>$filename,
                                'index'     =>$index,
                                'weight'    =>$weight,
                                'isexternal' => $isexternal,
                                'aggregate' => $aggregate,
                                'comment'   => $comment,
                                'position'  =>$position,
                                'options'   => $options
                                );

                $this->_queueModuleJs($params);

             }
        } elseif (isset($libname) && !empty($libname)) {
            if (!isset($plugin)) $plugin = '';
            if (!isset($pluginfile)) $pluginfile = '';
            //process javascriptlibrary and plugins
            //add the plugins - and load the framework if it's not already loaded
            if (!empty($plugin)) {
                  $params = array(
                                'libname'     => $libname,
                                'libfile'     => $libfile,
                                'plugin'      => $plugin,
                                'pluginfile'  => $pluginfile,
                                'style'       => $style,
                                'type'        => $type,
                                'module'      => $provider,
                                'index'       => $index,
                                'weight'      => $weight,
                                'position'    => $position,
                                'aggregate' => $aggregate,
                                 'comment'   => $comment,
                                'isexternal'    => $isexternal
                                );

                $this->_queueLibraryJs($params);

             } else { //process the javascript framework file

                if ($isexternal == TRUE) {
                     $filename = $libfile;
                } elseif (!empty($libfile) && isset($libname) && !empty($libname)) { //there is an alternative framework script
                    $filename = $libname.'/'.$libfile;
                } else {
                    $filename = $libname.'/'.$libname;
                }
                $params = array(
                                'libfile'   => $libfile,
                                'libname'   => $libname,
                                'filename'  => $filename,
                                'index'     => $index,
                                'type'      => $type,
                                'weight'    => $weight,
                                'position'  => $position,
                                'style'     => $style,
                                'module'    => $module,
                                'aggregate' => $aggregate,
                                 'comment'   => $comment,
                                'isexternal'    => $isexternal
                                );

                 $this->_queueLibraryJs($params);

           }
        }
        return TRUE;
    }

    /**
     * Render function
     *
     * Handle render javascript form field tags
     *
     * @access public
     * @param array   $args array of optional parameters<br/>
     *        array   $args['definition'] form field definition or the type, position, ...
     *        string  $args[position] position to fetch the js, optional<br/>
     *        string  $args[index] Unique index, optional<br/>
     *        string  $args[type] type to render, optional
     * @return string templated output of js to render
     * @throws none
     */
    public function renderJs($args)
    {
        extract($args);

        if (isset($definition) && is_array($definition)) {
            extract($definition);
        }
        $args['opencomment']                = "<!-- ";
        $args['closecomment']               = " -->\n";
        $javascript = $this->getJs($args);

        if (empty($javascript)) return;
        $args['javascript'] = $javascript;
        return xarTplModule('themes', 'javascript', 'render', $args);
    }

    /**
     * Get JS queued for output, optionally by position and index
     *
     * @access public
     * @param array   $args array of optional parameters<br/>
     *        string  $args[position] position to get JS for, optional<br/>
     *        string  $args[index] index to get JS for, optional
     *        string  $args[drop] == TRUE drop the given position and index
     * @return mixed array of queued js, FALSE if none found
     * @throws none
    */
    public function getJs($args)
    {
        extract($args);

        if (!isset($position) || empty($position)) return self::$queue;
        if (!isset(self::$queue[$position]) || empty(self::$queue[$position])) {return;}
        $javascript = array();
        if (isset($drop) && ($drop ===TRUE)) {
            unset(self::$queue[$position][$index]);
        }
        $jslist = self::$queue[$position];

        self::$queue[$position] = $this->_orderJsWeight( $jslist );
        if (empty($index)) {
            if ($this->jsaggregate == TRUE) {
                $javascript = $this->aggregateJs(self::$queue[$position]);
            } else {
                $javascript =  self::$queue[$position];
            }
        } elseif (!empty($position) && !empty($index) && isset(self::$queue[$position][$index])) {
            if ($this->jsaggregate == TRUE && isset(self::$queue[$position][$index]['aggregate']) && (self::$queue[$position][$index]['aggregate'] == TRUE)) {
                  $javascript = $this->aggregateJs(self::$queue[$position][$index]);
            } else {
                $javascript = self::$queue[$position][$index];
            }
        }

        return $javascript;
    }

/**
 * Add javascript to queue
 *
 * Add JavaScript code or links to the queue for template output
 *
 * @access public
 * @param string  $position position to place js, (head or body), required
 * @param string  $type     type of data to queue, (src or code), required
 * @param string  $data     data to queue (filepath, or raw Javascript code fragment), required
 * @param string  $index    index to use, optional (unique key and/or ordering)
 * @return boolean TRUE on success
 *
 */
    public function addJs($position, $type, $data, $index = '', $weight, $aggregate = null, $comment='')
    {
        if (empty($position) || empty($type) || empty($data)) {return;}

        // keep track of javascript when we're caching
        xarCache::addJavaScript($position, $type, $data, $index, $weight,$aggregate,$comment);
        $aggregate = isset($aggregate) && !is_null($aggregate)?$aggregate:$this->aggregate;
        if (!isset(self::$queue[$position])) self::$queue[$position] = array();

        $newitem = array('type' => $type, 'data' => $data, 'weight'=> $weight, 'aggregate'=>$aggregate, 'comment'=> $comment);
        if (empty($index)) {
            self::$queue[$position][] = $newitem;
        } else {
            self::$queue[$position][$index] =  $newitem;
        }
        return;
    }

/**
 * Base JavaScript management functions
 * Find the path for a JavaScript file.
 *
 * @author Jason Judge
 * @access private
 * @param  array   $args array of optional parameters<br/>
 *         string  $args[filename] name of file to find<br/>
 *         string  $args[module] name of module to look for filename in,  or, optional
 *         integer $args[modid] regid of module to look for filename <br/>
 *         integer $args[moduleid] regid of module to look for filename in (deprecated)<br/>
 * @return string  the virtual pathname for the JS file; an empty value if not found
 * @throws none
 */
    private function _findfile($args)
    {
        extract($args);

        // File must be supplied and may include a path.
        if (empty($filename) || $filename != strval($filename)) {
            return;
        }

         // Bug 5910: If the path has GET parameters, then move them aside for now.
        if (strpos($filename, '?') > 0) {
            list($filename, $params) = explode('?', $filename, 2);
            $params = '?' . $params;
        } else {
            $params = '';
        }

       // Use the current module if none supplied.
        if (empty($module) && empty($modid)) {
            list($module) = xarRequest::getInfo();
        }

        // Get the module ID from the module name.
        if (empty($modid) && !empty($module)) {
            $modid = xarMod::getRegId($module);
        }

        // Get details for the module if we have a valid module id.
        if (!empty($modid)) {
            $modInfo = xarMod::getInfo($modid);
            // Get module directory if we have a valid module.
            if (!empty($modInfo)) {
                $modOsDir = $modInfo['osdirectory'];
            }
        }

        // Initialise the search path.
        $searchPath = array();

        // The search path for the JavaScript file.
        if (isset($modOsDir)) {
            $searchPath[] = $this->themedir . '/modules/' . $modOsDir . '/includes/' . $filename;
           // $searchPath[] = $this->themedir . '/modules/' . $modOsDir . '/xarincludes/' . $filename; //do we really need this?
            $searchPath[] = $this->codedir.'modules/' . $modOsDir . '/xartemplates/includes/' . $filename;
        }
        $searchPath[] = $this->themedir . '/'.self::XARSCRIPTDIR.'/' . $filename;
        $searchPath[] = self::XARSCRIPTDIR.'/' . $filename; //jojo - added for integrated js library

        foreach($searchPath as $filePath) {
            //jojo - this seems to result in error handler call without the try
            //not sure why - thought it just returned FALSE if the file doesn't exist
            //perhaps on windows it also has some issues esp with symlinks
            try {
                    if (file_exists($filePath)) break;
                } catch (Exception $e) {
                    //do nothing
                }
            $filePath = '';
        }

        if (empty($filePath)) {
            return;
        }

        return $filePath . $params;
    }
    /**
      * Handle queuing of specific JS Library plugin code
      */
    private function _queueLibraryJs($args)
    {
        extract($args);
        if (isset($plugin) && !empty($plugin) && isset($libname)) {
            $plugin =trim($plugin);
            $libname = trim($libname);
             //do any plugin styles
            if (isset($style) && !empty($style)) {
                if(is_string($style)) {
                    $stylefiles = explode(',', $style);
                }
                foreach ($stylefiles as $stylesheet) {
                    //themes_userapi_register
                    $file = $libname . '/plugins/' . $plugin . '/' . preg_replace('/\.css$/', '', $stylesheet);
                    $provider = (isset($module) && !empty($module)) ? $module :'';
                    $styleload = xarModAPIFunc('themes','user','register', array(
                        'scope' => 'script',
                        'module' => $provider,
                        'file' => $file,
                        'comment' => $comment
                    ));
                }
            }
         } elseif (isset($libname) && isset($style) && !empty($style)) {
         //do any library styles - did this get missed totally??? I thought it was covered somewhere
             if(is_string($style)) {
                    $stylefiles = explode(',', $style);
                }
                foreach ($stylefiles as $stylesheet) {
                    //themes_userapi_register
                    $file = $libname . '/' . preg_replace('/\.css$/', '', $stylesheet);
                    $provider = (isset($module) && !empty($module)) ? $module :'';
                    $styleload = xarModAPIFunc('themes','user','register', array(
                        'scope' => 'script',
                        'module' => $provider,
                        'file' => $file,
                        'comment' => $comment
                    ));
                }
         }
        //Try loading the framework if there is one specified in case it's not already loaded
        //there is an alternative libfile to the library name

        if (FALSE == $isexternal) {//there is an alternative local framework script
            if (isset($libfile) && !empty($libfile)) {
                $libfile = trim($libfile);
                $libfilename = $libname.'/'.$libfile;
            } else {
                $libfilename = $libname.'/'.$libname;
            }
            if (substr($libfilename,-3) != '.js') {
                $libfilename = $libfilename.'.js'; //make sure there is a js extension
            }
        }else {
            $libfilename = $libfile; //use the given javascript file uri
        }
        $args['filename'] = trim($libfilename);

        if ($isexternal == TRUE) {
            $filePath = $args['filename']; // should be full path
            $fullfilePath = $filePath;
        } else {
            //find and process the local file
            $filePath  = $this->_findfile($args);
            $fullfilePath = xarServer::getBaseURL() . $filePath;
        }
        if (!empty($filePath)) {
            //check the load order
            $newweight = $weight;
            //check the main library file is not already loaded in head or body
            $checkpos = ($position == 'head') ? 'body': 'head';
            $checkarray = array('position'=>$checkpos, 'index'=>$filePath);
            $isloaded = $this->getJs($checkarray);
            //jojo - tricky - if it is the actual framework, and it's loaded in body - but we require it in head
            //we really need to drop the body one and load in head
            if (empty($isloaded) &&  ($position != 'head')) { // it is not loaded yet - let's load it;
                if (isset($pluginfile) && !empty($pluginfile)) { //we're loading plugin too - make sure the libfile loads first
                    //arbitrary but we set it to ensure library files are always less than the default 10
                    //if you need a specific weight set it, the plugin will always be greater than default 10
                    //TODO - work out a more robust way to do this without hard coding.
                    if ($newweight >= $this->weight) $newweight = $this->weight - 1;
                }
                $result = $this->addJs($position,$type, $fullfilePath, $filePath, $newweight, $aggregate, $comment);
            } elseif ($position == 'head') {
                //we have to load this and make sure it's not loaded in the body
                $result = $this->addJs($position,$type, $fullfilePath, $filePath, $newweight, $aggregate, $comment);
                //drop any of the same in the body
                $result = $this->getJs(array('position'=>'body','index'=>$filePath, 'drop'=> TRUE));
            }
        }

        //if there is some other plugin file names load them along with the library filename
        if (isset($plugin) && !empty($plugin)) {
            if (isset($pluginfile) && !empty($pluginfile) && is_string($pluginfile))
            {
               $pluginlist = explode(',',$pluginfile);
               foreach ($pluginlist as $pluginname)
               {
                    $pluginname= trim($pluginname);
                    $filename = $libname.'/plugins/'.$plugin.'/'.$pluginname;
                    if ((FALSE == $isexternal)  && (substr($filename,-3) != '.js'))
                    {
                        $filename = $filename.'.js'; //make sure there is a js extension
                    }
                    $args['filename'] = $filename;
                    $thisfilePath  = $this->_findfile($args);
                    if (!empty($filePath))
                    {
                        $thisindex = isset($index) && !empty($index) ? $index : $thisfilePath;
                        //we check here to see if already loaded in head or body
                        //but we also do not want it reloaded - it will auto overwrite but we do not want it out of sequence
                        $checkpos = ($position == 'head') ? 'body': 'head';
                        $checkarray = array('position'=>$checkpos, 'index'=>$thisindex);
                        $isloaded = $this->getJs($checkarray);
                        //now the very thing we rely on for no allowing reloading is preventing property ordering - ie the 'index'
                        if (empty($isloaded))
                        {
                            $fullfilePath = xarServerGetBaseURL() . $thisfilePath;
                            //plugins are always loaded with weight> default currently 10
                           $this->addJs($position, $type, $fullfilePath, $thisindex, $weight, $aggregate, $comment);
                        }
                    }
                }

            } else {
                $filename = $libname.'/plugins/'.$plugin.'/'.$plugin;
                $args['filename'] = $filename;
                $filePath  = $this->_findfile($args);
                if (!empty($filePath)) {
                   $index = isset($index) && !empty($index) ? $index : $filePath;
                    $checkpos = ($position == 'head') ? 'body': 'head';
                    $checkarray = array('position'=>$checkpos, 'index'=>$index);
                    $isloaded = $this->getJs($checkarray);
                    if (empty($isloaded))
                    {
                        $fullfilePath = xarServerGetBaseURL() . $filePath;
                        //$this->addJs($position, 'src', $fullfilePath, $index, $weight+ $this->weight, $aggregate, $comment);
                        $this->addJs($position, 'src', $fullfilePath, $index, $weight, $aggregate, $comment,$loadorder);
                    }
                }
            }
        }

        return;
    }

    /**
      *  Handle queuing of page and module javascript
      */
     private function _queueModuleJs($args)
    {
        extract($args);

        // Filename can be an array of files to include, or a
        // comma-separated list. This allows a bunch of files
        // to be included from a source module in one go.
        if (!is_array($filename) && !empty($filename)) {
            $files = explode(',', $args['filename']);
        }
        foreach ($files as $file) {
            $varcomment = '';
            $file = trim($file);
            if ((FALSE === $isexternal) && (substr($file,-3) != '.js')) {
                $file = $file.'.js'; //make sure there is a js extension
            }
            if (FALSE === $isexternal) {//there is an alternative local framework script
                $filePath = $this->_findfile(array('filename' => trim($file), 'module' => $module));
            }else {
               $filePath = $file; //use the given javascript file uri
            }
            // A failure to find a file is recorded, but does not stop subsequent files.
            if (!empty($filePath)) {
                 $index = isset($index) && !empty($index) ? $index : $filePath;
                if ($isexternal === FALSE) {
                    $filePath = xarServer::getBaseUrl() . $filePath;
                }
                if (isset($options) && !empty($options)) {
                    $jsoptions = $this->_addJsVars($options);
                     //make sure it loads one less place than the normal file
                     $varcomment = xarML('Variables for #(1)', $filename);
                     $this->addJs($position, 'code', $jsoptions,  md5($options), $weight-1, $aggregate, $varcomment);
                }
                $checkpos = ($position == 'head') ? 'body': 'head';
                $checkarray = array('position'=>$checkpos, 'index'=>$index);
                $isloaded = $this->getJs($checkarray);;
                if (empty($isloaded)) {
                    $this->addJs($position, $type, $filePath, $index, $weight, $aggregate, $comment);
                }
            }
        }

        return;
    }

    function _addJsVars($options)
    {
        $jscode = '';
        $jsoptions = '';
        if (!empty($options) && substr($options,0,2) == 'a:') {
            $options = unserialize($options);
        }
        if (is_array($options)) {
            foreach ($options as $name => $value) {
                $value = json_encode($value);
                $jscode .= "var $name = $value;\n";
            }
        }
        return $jscode;
    }


    function aggregateJs($jsfiles)
    {

        if (empty($jsfiles)) return;
        $contents = '';
        $cachefileuri = '';
        $cachekey = md5(serialize($jsfiles));
        //check to see if the cache key exists and thus the existing cache file $uri
        if (xarCoreCache::isCached('Themes.js',$cachekey)) {
            $cachefileuri = xarCoreCache::getCached('Themes.js',$cachekey);
        }

        //we will put src and code together in a file that is then served
        //it is already sorted per position according to load order and weight
        //usually the template takes care of code so we have to wrap it in this case in approprate cdata tags for xhtml
        //what about html4?
        $cdataprefix = "\n<!--//--><![CDATA[//><!--\n";
        $cdatasuffix = "\n//--><!]]>\n";
        $identifier = '';
        //how do we know what files are aggregated? We better include something in the aggregated file
        if (empty($cachefileuri) || !file_exists($cachefileuri))
        {
            //build the combined aggregate js file - this will be passed in by position and
            //page so we do not need to worry about separation on those basis. It will be aggregated and passed back and loaded in the correct position.
            //type src, code needs to be adjusted
            foreach ($jsfiles as $jsfile => $info) {

               if (isset($info['aggregate']) && $info['aggregate'] === TRUE) {
                    $identifier .= ' '.$jsfile.',';
                    $contents .= "/* ".$jsfile ."*/\n";
                    if ($info['type'] == 'src') {
                       // delimit each file added with a  ;
                        $contents .=  file_get_contents($info['data']) . ";\n";
                        unset($jsfiles[$jsfile]);
                    } elseif ($info['type'] == 'code') {
                        $contents .= $info['data']. ";\n";
                         unset($jsfiles[$jsfile]);
                    }
                }
            }
            if (substr($identifier,-1) == ',') {
                $identifier = substr($identifier,0,-1);
            }
            $contents = "/* Xarigami Aggregated files: ".$identifier." */\n".$contents;
            $newfile= 'js_' . md5($contents) . '.js';

            //create the cached file
            $newpath = $this->jscachedir;
            $cachefileuri = $newpath.'/'.$newfile;

            if (is_writeable($newpath)) {
                file_put_contents($cachefileuri, $contents);
            }
            ///what about optimization/compression?
            if (extension_loaded('zlib') && $this->compress) {
                $gzfile = $cachefileuri.'.gz';
                if (!file_exists($gzfile)) {
                    $gzdata =  gzencode($contents, 9, FORCE_GZIP);
                    $fp = fopen($gzfile,'wb');
                    fwrite($fp,$gzdata);
                    fclose($fp);
                }
            }

            //now update the cache and save
            xarCoreCache::setCached('Themes.js',$cachekey,$cachefileuri);

            //prepare the array to send back to the get js function
            $cachefileurl =str_replace('./',$this->basepath,$cachefileuri);
            $newfile = array($identifier => array('type'=> 'src', 'data'=> $cachefileurl, 'weight'=> $this->weight - 1)); //jojo - what to do about weight? review.
            $jsfiles = array_merge($newfile,$jsfiles);
          }

      return $jsfiles;
    }

    private function _orderJsWeight($jslist)
    {
        $jstype = '';
        $jsweight= '';
        $jsoriginal = '';
        $i = 0;
        //set up an original sort order first so we do not loose original load order
        //this will take priority if we sort on it first
        foreach ($jslist as $key => $rowinfo) {
            $jsoriginal[$key] = $i++;
            $jsweight[$key]  = isset($rowinfo['weight']) ? (int) $rowinfo['weight']: $this->weight;
            $jstypesort[$key] = isset($rowinfo['type']) && ($rowinfo['type'] == 'code') ? 2:1;
        }
        array_multisort( $jsweight, SORT_ASC,$jsoriginal,SORT_ASC,$jstypesort, SORT_ASC, $jslist);
        return $jslist;
    }
}
?>