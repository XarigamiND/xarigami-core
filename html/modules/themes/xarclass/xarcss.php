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
 */

/**
 * Base CSS class
 *
 * @access public
 * @params array   $args array of optional parameters<br/>
 *         string  $args[scope] scope of style, one of common!theme(default)|module|block|script<br/>
 *         string  $args[method] style method, one of link(default)|import|embed<br/>
 *         string  $args[alternatedir] alternative base folder to look in, falling back to...<br/>
 *         string  $args[base] base folder to look in, optional, default "style"<br/>
 *         string  $args[file] name of file required for link or embed methods, optional, default "style"<br/>
 *         string  $args[filext] extension to use for file(s), optional, default "css"<br/>
 *         string  $args[source] source code, required for embed method, default ""<br/>
 *         string  $args[alternate] switch to set rel="alternate stylesheet", optional TRUE|FALSE(default)<br/>
 *         string  $args[rel] rel attribute, optional, default "stylesheet"<br/>
 *         string  $args[type] link/style type attribute, optional, default "text/css"<br/>
 *         string  $args[media] media attribute, optional, default "screen"<br/>
 *         string  $args[title] title attribute, optional, default ""<br/>
 *         string  $args[condition] conditionals for ie browser, optional, default ""<br/>
 *         string  $args[theme] theme name, optional first theme to look for in theme scope
 *         string  $args[module] module for module|block scope, optional, default current module<br/>
 *         string  $args[version] version string appended to css file name
 *         integer $args[weight] weight of css, the lower the weight the earlier the style sheet is loaded
 *         bool    $args[aggregate]      aggregate ==FALSE will not aggregate the CSS
 *         bool    $args[dynamic] allows dynamic css file to be compiled and used
 *         string  $args[dynfile] the dynamic filename without the fileext
 *         mixed   $args[params]
 *         string  $args[id] a unique identifier used for this css file
 *
 * @return boolean TRUE on success
 *
 */
class xarCss extends xarObject
{
    const CSSRELSTYLESHEET      = 'stylesheet';
    const CSSRELALTSTYLESHEET   = 'alternate stylesheet';
    const CSSTYPETEXT           = 'text/css';
    const CSSMEDIA              = 'media';
    const CSSMEDIATV            = 'tv';
    const CSSMEDIATTY           = 'tty';
    const CSSMEDIAALL           = 'all';
    const CSSMEDIAPRINT         = 'print';
    const CSSMEDIAAURAL         = 'aural';
    const CSSMEDIASCREEN        = 'screen';
    const CSSMEDIASPEECH        = 'speech';
    const CSSMEDIABRAILLE       = 'braille';
    const CSSMEDIAEMBOSSED      = 'embossed';
    const CSSMEDIAHANDHELD      = 'handheld';
    const CSSMEDIAPROJECTION    = 'projection';
    const CSSCOMMONSOURCE       = 'xarcore-xhtml1-strict';
    const CSSCOMMONBASE         = 'base';
    const CSSCOMMONSCRIPTS      = 'scripts';

    // class vars and their defaults
    public $language            = 'html';           // only (x)html compliant css inclusion is supported out of the box
    public $method              = 'link';           // supported are 'link', 'import', 'embed'

    // SUPPORTED SCOPES ARE MODULE, THEME, COMMON, BLOCK, SCRIPT, INIT, SKIN
    public $scope               = 'theme';          // component type - 'module, 'theme' or 'common', default 'theme'
    public $base                = 'theme';          // component name (e.g. module's name 'base')
    public $filename            = 'style';          // default css file name (without extension)
    public $fileext             = 'css';            // default css file extension
    public $commonbase          = self::CSSCOMMONBASE;    // base dirctory for common css
    public $commonsource        = self::CSSCOMMONSOURCE;  // filename for common css
    public $scriptbase          = self::CSSCOMMONSCRIPTS; //base directory for common javascript framework css, plugin css
    public $themedir            = 'theme/default';

    public $source     = NULL;                      // empty source should not be included (ideally)
    public $condition  = NULL;                      // encase in a conditions comment (think ie-win)

    public $version = NULL;                         // css file version
    public $weight =  20;                           // css file weight - lower weight loads first, default 20?? should be enough
    public $dynfile = NULL;                        // if TRUE, dynamic css is not included in aggregation
    public $dynamic = FALSE;
    public $aggregate = TRUE;                       // FALSE == do not include in aggregation  - consistent with js
    public $params = NULL;
    public $dynattributes = NULL;
    public $themevars = array();
    // TYPICAL REQUIRED/DEFAULT ATTRIBUTES FOR WELL-FORMED CSS REFERENCE TAGS (xhtml-wise)
    public $rel                 = self::CSSRELSTYLESHEET;
    public $type                = self::CSSTYPETEXT;
    public $media               = self::CSSMEDIASCREEN;
    public $title               = '';               // empty string title attribute will not be included
    public $id                  = '';

    // BASIC OVERRIDES SETTINGS (still TODO)
    public $overridden          = FALSE;            // TRUE == stylesheet has been overridden in theme or elsewhere
    public $alternatedir        = '';               // alternative directory for overridden css file

    /**
     * constructor
     */
    function __construct($args = array())
    {
        extract($args);

        if (isset($method))         { $this->method       = $method;            unset($args['method']); }
        if (isset($scope))          { $this->scope        = $scope;             unset($args['scope']); }
        if (isset($module))         { $this->module       = $module;            /* is passed to dynvar */ }
        if (isset($media))          { $this->media        = $media;             unset($args['media']); }
        if (isset($type))           { $this->type         = $type;              unset($args['type']); }
        if (isset($rel))            { $this->rel          = $rel;               unset($args['rel']); }
        if (isset($file))           { $this->filename     = $file;              /* is passed to dynvar */  }
        if (isset($title))          { $this->title        = $title;             unset($args['title']); }
        if (isset($fileext))        { $this->fileext      = $fileext;           unset($args['fileext']); }
        if (isset($alternate))      {                                           unset($args['alternate']); }
        if (isset($alternatedir))   { $this->alternatedir = $alternatedir;      unset($args['alternatedir']); }
        if (isset($commonsource))   { $this->commonsource = $commonsource;      unset($args['commonsource']); }
        if (isset($base))           { /* unused as computed */                  unset($args['base']); }
        if (isset($weight))         {                                           unset($args['weight']); }
        if (isset($aggregate))      {                                           unset($args['aggregate']); }
        if (isset($dynamic))        {                                           unset($args['dynamic']); }
        if (isset($dynfile))        {                                           unset($args['dynfile']); }
        if (isset($source))         { $this->source       = $source;            unset($args['source']); }
        if (isset($condition))      { $this->condition    = $condition;         unset($args['condition']); }
        if (isset($version))        { $this->version      = "?v=$version";      /* is passed to dynvar */ }
        if (isset($params))         { $this->params       = $params;            unset($args['params']); }
        if (isset($themevars))      { $this->themevars    = $themevars;         unset($args['themevars']); }
        if (isset($id))             { $this->id           = $id;                /* is passed to dynvar */ }

        if ($this->scope == 'common') {
            $this->base = $this->commonbase;
            $this->filename  = $this->commonsource;
        } elseif ($this->scope == 'module') {
            $this->base = xarMod::getName();
        } elseif ($this->scope == 'block') {
            // we basically need to find out which module this block belongs to
            // and then procede as with module scope
            $this->base = xarCoreCache::getCached('Security.Variables', 'currentmodule');
        } elseif ($this->scope =='script') {
            $this->base = $this->scriptbase;
        }
        if (isset($module) && ($scope != 'script'))     $this->base  = $module;

        $this->alternate = isset($alternate) && !is_null($alternate)? $alternate : FALSE;
        if (isset($alternate) && $alternate === 'TRUE') {
            $this->rel = 'alternate stylesheet';
        }
        if ($this->method == 'import' && isset($media)) {
            $this->media = str_replace(' ', ', ', $media);
        }

        $this->weight = isset($weight) ? $weight : $this->weight;

        if (isset($args['handler_type'])) unset($args['handler_type']);

        if (isset($dynfile) && !empty($dynfile)) {
            $this->dynfile = $dynfile;
            $this->dynamic = isset($dynamic) ? $dynamic != FALSE : TRUE;
            if (!empty($args)) $this->dynattributes = $args;
        } else {
            $this->dynamic = FALSE;
        }

        if (empty($this->id)) $this->id = $this->filename;

        $this->aggregate = isset($aggregate) && !is_null($aggregate)?$aggregate:$this->aggregate;

        $this->themedir         = xarTpl::getThemeDir();
        $this->tagdata = array(
                            'scope'            => $this->scope,
                            'method'           => $this->method,
                            'base'             => $this->base,
                            'file'             => $this->filename,
                            'fileext'          => $this->fileext,
                            'source'           => $this->source,
                            'rel'              => $this->rel,
                            'type'             => $this->type,
                            'media'            => $this->media,
                            'title'            => $this->title,
                            'condition'        => $this->condition,
                            'version'          => $this->version,
                            'weight'           => $this->weight,
                            'dynfile'          => $this->dynfile,
                            'dynamic'          => $this->dynamic,
                            'processed'        => FALSE,
                            'params'           => $this->params,
                            'dynattributes'    => $this->dynattributes,
                            'themevars'        => $this->themevars,
                            'aggregate'        => $this->aggregate,
                            'alternate'        => $this->alternate,
                            'id'               => $this->id
                            );
    }
    /**
     * The main method for generating tag output
     * stick tag data into the tag queue or get it
     */
    public function run_output()
    {
        if (!isset($tagqueue)) $tagqueue = new xarTagQueue();

        switch($this->method) {
            case 'render':
                $styles= $tagqueue->deliver();
                $data['styles'] = $styles;
                break;
            default:
                $this->tagdata['url'] = $this->geturl();
                $this->tagdata['dynamic'] = $this->tagdata['dynamic'] && $this->dynamic; // Could be changed in geturl.
                $tagqueue->register($this->tagdata);
                return TRUE;
        }
        //$data['comments']                   = $this->comments;
        $data['comments']                   = xarModVars::get('themes', 'ShowTemplates');
        $data['opencomment']                = "<!-- ";
        $data['closecomment']               = " -->\n";
        $data['openconditionalcomment']     = "<!--[if ";
        $data['closeconditionalcomment']    = "<![endif]-->\n";
        $data['openbracket']                = "<";
        $data['closebracket']               = ">";
        $data['closeconditionalbracket']    = "]>";

        return $data;
    }

    /**
     * returns xarigami url for the file
     */
    function geturl($dir = null)
    {
        // it's static var already in core
        $url = xarServer::getBaseURL();

        if (isset($dir)) {
            $fullurl = $url.$dir;
        } else {
            $dynamic = $this->dynamic && xarModVars::get('themes', 'dynamic');
            $fullurl = $url.$this->getrelativeurl($dynamic);
        }

        return $fullurl;
    }
    /**
     * Get the relative URL
     * @param none
     */
    function getrelativeurl($dynamic = FALSE)
    {
        // if requested method is 'embed', we dont really need any file checks, urls, scope etc.,
        // all we care about is the css source string as provided by the tag
        if ($this->method == "embed") {
            // could add a TODO to check validity of the actual source string, either here or earlier
            return $this->source;
        }

        $filename = $dynamic ? $this->dynfile : $this->filename;
        // <andyv> scope common is just a special case of a module based stylesheet ATM - matter of implementation
        // the original idea was to be able to provide common css out of various sources, like db or even inline
        if ($this->scope == 'common') $this->scope = 'module';

        switch ($this->scope) {
            case 'theme':
            case 'skin':
            case 'init':
                $themestylesheet = $this->themedir . "/style/" . $filename . "." . $this->fileext;
                if (is_file($themestylesheet)) return $themestylesheet;
                if ($dynamic) {
                    $this->dynamic = FALSE;
                    $themestylesheet = $this->themedir . "/style/" . $this->filename . "." . $this->fileext;
                    if (is_file($themestylesheet)) return $themestylesheet;
                }
                throw new FileNotFoundException($themestylesheet);

            case 'common':
                $this->scope = 'module';
                // no break;
            case 'module':
            case 'block':
                $original = sys::code()."modules/" . strtolower($this->base) . "/xarstyles/" . $filename . "." . $this->fileext;
                // we do not want to supply path for a non-existent original css file or override a bogus file
                // so lets check starting from original then fallback if there arent overriden versions
                // how about the overridden one?
                // Look for theme-based stylesheet whether the module contains one or not.
                if($this->alternatedir != '') {
                    $overridden =  $this->themedir . "/" . $this->alternatedir . "/" . $filename . "." . $this->fileext;
                } else {
                    $overridden =   $this->themedir . "/modules/" . strtolower($this->base) . "/xarstyles/" . $filename . "." . $this->fileext;
                }
                return is_file($overridden) ? $overridden : $original;
            case 'script':
               //These are the cases - not in priority order
                // 1. Plugin supplies it's own css/images - script style sheet
                // 2. Module writer wants to override the supplied css - modulesheet
                // 3. Themer wants to override the supplied module override css for the specific module only - themestylesheet
                // 4. Themer wants to override the original plugin css - themestylesheet
                //  Order of priority to load : 3 (theme/module css), 2 (module css), 4 theme/scripts css, 1 supplied plugin css in script
                $overridden = '';
                $modulesheet = '';
                if (isset($this->module) && !empty($this->module)) {
                    $modulesheet = sys::code().'modules/'.$this->module.'/xarstyles/' . $filename . "." . $this->fileext; //Module wants to supply it's own CSS for the plugin
                    $overridden =   $this->themedir . '/modules/' .$this->module.'/xarstyles/'. $filename . '.' . $this->fileext; // it's in the module's style directory in our theme
                }
                $themestylesheet =    $this->themedir.'/'.$this->base .'/'. $filename . '.' . $this->fileext;   //it's in the script directory in our theme
                $scriptstylesheet  = $this->base .'/'. $filename . '.' . $this->fileext; //Original supplied CSS file with plugin

                // jojo - fix for one return exit
                $csssheet = '';
                if (is_file($overridden)) {
                    $csssheet = $overridden;
                }elseif (is_file($modulesheet)){
                    $csssheet = $modulesheet;
                } elseif (is_file($themestylesheet)) {
                     $csssheet = $themestylesheet;
                } elseif (is_file($scriptstylesheet)) {
                     $csssheet = $scriptstylesheet;
                }
                return $csssheet;

                if ($dynamic) {
                    $this->dynamic = FALSE;
                    return getrelativeurl(FALSE); // Fall back on non dynamic file
                }

                $msg = xarML("#(1) css stylesheet file cannot be found at this location: ", $this->scope);
                throw new BadParameterException(null, $msg.$themestylesheet);

            default:
                // no scope, somebody overrode defaults and hasn't assign anything sensible? naughty - lets complain
                $msg = xarML("#(1) (no valid scope attribute could be deduced from this xar:style tag)",$this->scope);
                throw new BadParameterException(null, $msg);
        }
    }
}

/**
 * Queue class. Holds the tag data until it is sent to the template
 *
 * @package themes
 */

class xarTagQueue extends xarObject
{
    protected static $queue = array();
    public $cascadeorder = array('init','common','cached','module','script','theme','skin');

    // someone is bound to trip over that hack at some point
    // deprecated
    public function queue($op, $args)
    {
        switch($op) {
            case 'register':
                return $this->register($args);
            case 'deliver':
                return $this->deliver($args);
            default:
                return FALSE;
        }
    }

    public function register($args = array())
    {
        // Put it in the queue
        self::$queue[$args['scope']][$args['method']][$args['url']][$args['id']] = $args;
        return TRUE;
    }

    // $sort arg is deprecated
    public function deliver($sort = TRUE)
    {
        $styles = !empty(self::$queue) ? self::$queue : array();
        if ($sort && is_array($styles)){
           //setup custom sort by scope load order
            uksort($styles, array(&$this,'_stylecmp'));
            reset($styles);
        }

        $cssprocessor = xarProcessCss::getInstance();
        $styles = $cssprocessor->process($styles);
        if ($cssprocessor->aggregate) {
            uksort($styles, array(&$this,'_stylecmp'));
        }

        //now what about weight?
        //foreach ($styles as $styletype=>$st) {
            //foreach($st as $method=>$v) {
            //make sure we do not change order here, only if weight is different
            //TODO jojo - Continue on this later
            //   $styles[$styletype][$method]= $this->_subvalsort($v,'weight');
            //}
        //}
        self::$queue = array();
        return $styles;
    }

    private function _stylecmp($a, $b)
    {
        $cascadeorder = $this->cascadeorder;
        $pos1 = array_search($a, $cascadeorder);
        $pos2 = array_search($b, $cascadeorder);
        if ($pos1 == $pos2) {
            return 0;
        } else {
            return ($pos1 <$pos2 ? -1 :1);
        }
    }

    private function _subvalsort($a,$subkey, $sort='asort',$flag=SORT_NUMERIC)
    {
        $held = array();

        foreach($a as $k=>$v) {
            $b[$k] = strtolower($v[$subkey]);
        }
        $sort($b,$flag);
        foreach($b as $key=>$val) {
            $c[] = $a[$key];
        }

        return $c;
    }

}

class xarProcessCss extends xarSkinVars
{
    public $cssoptimize     = FALSE; // default value for optimizing
    public $basepath        = '';
    public $relativepath    = '';
    public $themedir        = '';
    public $themename       = '';
    public $csscachedir     = './var/cache/styles';
    public $compress        = FALSE;   //to use gzip or other compression as available
    public $compile         = FALSE;
    public $dynamic         = FALSE;
    public $aggregate       = FALSE;
    public $cachefilenumber = 100;

    protected $_cacheGbl = NULL;
    protected $_cacheLcl = NULL;
    protected $_cacheTgt = NULL;
    protected $_iscacheGlb = NULL;
    protected $_iscacheLcl = NULL;
    protected $_iscachedTgt = NULL;

    protected $_dummyFileTime = '';
    protected $_once = FALSE;

    private static $__instance = NULL;

    // Singletton pattern
    public static function getInstance()
    {
        if (self::$__instance === NULL) self::$__instance = new xarProcessCss();
        return self::$__instance;
    }


    public function __construct()
    {
        $this->basepath         = xarServer::getBaseURL();
        $this->cssoptimize      = xarModVars::get('themes','cssoptimize') == TRUE;
        $this->themedir         = xarTpl::getThemeDir();
        $this->themename        = xarTpl::getThemeName();
        $this->csscachedir      = xarModVars::get('themes','csscachedir');
        $this->compress         = xarModVars::get('themes','compress') == TRUE;
        $this->dynamic          = xarModVars::get('themes','dynamic') == TRUE;
        $this->aggregate        = xarModVars::get('themes', 'cssaggregate') == TRUE;
        $this->cachefilenumber  = xarModVars::get('themes','cachefilenumber');
        $this->_once = FALSE;

        // Prepares skin vars
        $key = parent::$_keyGbl . '.' . $this->themename;
        if (!isset(parent::$_iscached[$key])) {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }
        $this->_cacheGbl = &parent::$_cache[$key];
        $this->_iscachedGbl = &parent::$_iscached[$key];

        $key = parent::$_keyLcl . '.' . $this->themename;

        if (!isset(parent::$_iscached[$key])) {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }
        $this->_cacheLcl = &parent::$_cache[$key];
        $this->_iscachedLcl = &parent::$_iscached[$key];
    }


    public function flushCssCache($limit = 100)
    {
        if (is_dir($this->csscachedir) && is_writable($this->csscachedir)) {
            $files = glob($this->csscachedir . '/css_*.css');
            if (count($files) > $limit) {
                if ($this->aggregate || $this->dynamic) {
                    // Flush the output cache
                    if (class_exists('xarPageCache')) xarPageCache::flushCached('');
                    // Flush the sessionless cache
                    if (class_exists('xarOutputCache')) {
                        $dir = xarOutputCache::$cacheDir . "/page/";
                        if (is_dir($dir)) {
                            $files = glob($dir);
                            foreach ($files as $file) {
                                @unlink($file);
                            }
                            unset($files2);
                        }
                    }
                }
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }

    //create the css aggregated cache file we want to serve
    protected function _createCssCacheFile ($cssfiles, $context = '', $compile = FALSE)
    {
        $cachefileuri = '';
        //that is the cache key for this group of files for a given context
        $cachekey = sha1($context);

        //check to see if the cache key exists and thus the existing cache file $uri
        if (xarCoreCache::isCached('Themes.css', $cachekey)) {
            $cachefileuri = xarVarGetCached('Themes.css',$cachekey);
        } else {
            $cachefileuri = $this->csscachedir.'/css_'.$cachekey.'.css';;
        }
        $newcontent = '';

        if (empty($cachefileuri) || !is_file($cachefileuri)) {
            //start adding to an aggregated file
            $processed = TRUE;
            foreach ($cssfiles as $scope => $csstype) {
                foreach ($csstype as $k => $cssinfo) {
                    foreach ($cssinfo as $csslink => $cssinstances) {
                        foreach ($cssinstances as $cssid => $styledata) {
                            $content = $this->_getStylesheet($styledata['url'], !$styledata['processed'], $compile && !$styledata['processed'], $styledata['id']);

                            if (!$styledata['processed']) {
                                $processed = FALSE; // one file is not processed
                                $relativepath = '';
                                //make sure all paths in the CSS itself are updated with a relative path  and ignore external or absolute paths
                                $stylefile = str_replace($this->basepath,'',$styledata['url']);
                                $relativepath = dirname($stylefile);
                                $this->relativepath = $relativepath;
                                $content = preg_replace_callback('/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i', array( &$this, '_getCssPath'), $content);
                            }

                            $newcontent .= $content;
                        }
                    }
                }
            }

            if (!$processed) {
                //W3C specification at http://www.w3.org/TR/REC-CSS2/cascade.html#at-import
                //now @import must proceed any other style so we need those first at the top
                $regexdata =  '/@import[^;]+;/i';
                preg_match_all($regexdata, $content, $matches);
                $content = preg_replace($regexdata, '', $content);
                $content = implode('',$matches[0]).$content;
            }

            //now create it
            if (is_writeable($this->csscachedir)) {
                file_put_contents($cachefileuri,$newcontent);
            }
            ///what about optimization/compression?
            if (extension_loaded('zlib') && $this->compress) {
                $gzfile = $cachefileuri.'.gz';
                if (!file_exists($gzfile)) {
                    $gzdata =  gzencode($newcontent, 9, FORCE_GZIP);
                    $fp = fopen($gzfile,'wb');
                    fwrite($fp,$gzdata);
                    fclose($fp);
                }
            }
            //now update the cache and save
            xarCoreCache::setCached('Themes.css',$cachekey,$cachefileuri);
        }
        return $cachefileuri;
    }

    protected function _getCssPath($matches)
    {
        //erg .. for testing only!
        $path = $matches[1];
        $themedir = $this->themedir;
        $moddir = sys::code().'modules';
        $relpath = $this->relativepath;
        $checkarray = array($themedir,$moddir,'scripts');
        //style sheets are in sys::path()./cache/styles'; this is 3 dirs down from root dir
        $found = 0;
        foreach($checkarray as $check) {
            $foo = strpos($path,$check,0);
            if ($foo === 0) {
               $found = 1;
               break;
            }
        }
        if (!$found) {
            $path = $relpath.'/'.$path;
        }
        $stylecache =  $this->csscachedir;

        //get the directory depth .... isn't there a function for this or what?
        if (substr($stylecache, -1) != '/') {
            $stylecache = $stylecache .'/';
        }
        if (substr($stylecache, 0) == '/') {
            $stylecache =  substr($stylecache,1);
        } elseif (substr($stylecache, 0,1) == './') {
             $stylecache =  substr($stylecache,2);
        }
        $arr = explode('/',$stylecache);
        $slashcount = count($arr) -1;
        $styledir = '';
        for ($i = 1; $i <= $slashcount; $i++) {
            $styledir .= '../';
        }
        $path = $styledir.$path;
        $hasmore = '';
        while ($path != $hasmore) {
            $hasmore = $path;
            $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
        }
        return 'url(' . $path . ')';

    }
    //get the contents of a stylesheet
    protected function _getStylesheet($stylefile , $optimize = FALSE, $compile = FALSE, $id = '', $relativepath = TRUE)
    {
        $basepath = $this->basepath;
        // we need to have some better function for setting/checking file as relative etc
        $relativestylefile = str_replace($basepath,'',$stylefile);
        $this->stylefile =  $relativestylefile;

        $this->relativepath = dirname( $relativestylefile);

        //load up the stylesheet
        if ($content = @file_get_contents( $relativestylefile)) {

            return $this->_getStylesheetContent($content,$optimize, $compile, $id);
       }
    }

    protected function _getStylesheetContent($content, $optimize = FALSE, $compile = FALSE, $id = '')
    {
        //replace @import with stylesheet content
        $content = preg_replace_callback('/@import\s*(?:url\(\s*)?[\'"]?(?![a-z]+:)([^\'"\()]+)[\'"]?\s*\)?\s*;/', array( &$this,'_loadFile'), $content);
        $content = preg_replace('/^@charset\s+[\'"](\S*)\b[\'"];/i', '', $content);

        if ($compile && $this->dynamic) {
            $content = $this->_compile($content, $id, FALSE);
        }

        //jojo - these regex are used in a number of other css compression utilities so seems good to go with
        if ($optimize && $this->cssoptimize) {
            // Perform some safe CSS optimizations.
            // Regexp to match comment blocks.
            $comment     = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
            // Regexp to match double quoted strings.
            $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
            // Regexp to match single quoted strings.
            $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
            // Strip all comment blocks, but keep double/single quoted strings.
            $content = preg_replace(
              "<($double_quot|$single_quot)|$comment>Ss",
              "$1",
              $content
            );

            // Remove some types of whitespace.
            // There are different conditions for removing leading and trailing whitespace
            // @see http://php.net/manual/en/regexp.reference.subpatterns.php
            $content = preg_replace('<
              # Strip leading and trailing whitespace.
                \s*([@{};,])\s*
              # Strip only leading whitespace from:
              # - Closing parenthesis: Retain "@media (bar) and foo".
              | \s+([\)])
              # Strip only trailing whitespace from:
              # - Opening parenthesis: Retain "@media (bar) and foo".
              # - Colon: Retain :pseudo-selectors.
              | ([\(:])\s+
            >xS',
             // Only one of the three capturing groups will match, so its reference
              // will contain the wanted value and the references for the
              // two non-matching groups will be replaced with empty strings.
              '$1$2$3',
              $content
            );
            // End the file with a new line.
            $content = trim($content);
            $content .= "\n";
        }

        return $content;

    }

    /**
     * Load the stylesheet each in turn and return with corrected paths throughout the content
     */
    protected function _loadFile($matches)
    {
        $filename = $this->relativepath.'/'.$matches[1];

        // Load the imported stylesheet and replace @import commands in there as well.
        $file = $this->_getStylesheet($filename, FALSE, FALSE);
        // Determine the file's directory.
        $directory = dirname($filename);
        // If the file is in the current directory, make sure '.' doesn't appear in
        // the url() path.
        $directory = $directory == '.' ? '' : $directory .'/';

        // Alter all internal url() paths. Leave external paths alone. We don't need
        // to normalize absolute paths here (i.e. remove folder/... segments) because
        // that will be done later.
        return preg_replace('/url\(\s*([\'"]?)(?![a-z]+:|\/+)/i', 'url(\1'. $directory, $file);
    }


    protected function _compile($content, $target, $generator = FALSE)
    {
        if (!isset($this->_cacheLcl[$target])) {
            $this->_cacheLcl[$target] = array();
            $this->_iscacheLcl[$target] = array();
        }
        $this->_cacheTgt = &$this->_cacheLcl[$target];
        $this->_iscachedTgt = &$this->_iscachedLcl[$target];

        // This template is intended only for calculations, and should not provide any inputs but skin vars.
        if (!$generator) {
            $data = array();
            $data['target'] = $target;
            $data['global'] = $this->_cacheGbl;
            $data['themevars'] = xarThemeVars::getall($this->themename);
            //var_dump($data['themevars']); die();
            $targetname = str_replace('-', '_', $target);
            $data[$targetname] = $this->_cacheTgt;
            $data['once'] = $this->_once;
            xarTpl::styleThemeTemplate(NULL, $data);
            //var_dump($this->_cacheLcl); die();
            $this->_once = TRUE;
        }

        // This is essential to have that line once again, as the generators might have changed the target in the meanwhile
        $this->_cacheTgt = &$this->_cacheLcl[$target];
        $this->_iscachedTgt = &$this->_iscachedLcl[$target];

        $s = array(); $r = array();
        if (!empty($this->_iscachedTgt)) {
            $keys = array_keys($this->_iscachedTgt);
            $c = count($keys);
            for ($i=0; $i !== $c; $i++) {
                $size = strlen($keys[$i]); // We need to replace first the longest varnames, otherwise we might accidentally do wrong things
                $s[$size][] = '@'.$keys[$i];
                $r[$size][] = &$this->_cacheTgt[$keys[$i]];
            }
        }
        if (!$generator && !empty($this->_iscachedGlb)) {
            $keys = array_keys($this->_iscachedGbl);
            $c = count($keys);
            for ($i=0; $i !== $c; $i++) {
                $size = strlen($keys[$i]); // We need to replace first the longest varnames, otherwise we might accidentally do wrong things
                $s[$size][] = '@'.$keys[$i];
                $r[$size][] = &$this->_cacheGbl[$keys[$i]];
            }
        }

        $lengths = array_keys($s);
        rsort($lengths, SORT_NUMERIC);
        $lengths = array_values($lengths);

        $c = count($lengths);

        $search = array(); $replace = array();
        for ($i=0; $i !== $c; $i++) {
            $length = $lengths[$i];
            $tot = count($s[$length]);
            for ($j = 0; $j !== $tot; $j++) {

                $skinvar =  &$r[$length][$j];
                if (is_object($skinvar) && $skinvar instanceof xarSkinVar) {
                    // generator skinvars gets an additional :;. ie: @gen_shadows:;
                    $search[] = $skinvar instanceof xarSkinGenerator ? $s[$length][$j] .':;' : $s[$length][$j];
                    $replace[] = $skinvar->render();
                } else {
                    $search[] = &$s[$length][$j];
                    $replace[] = $skinvar;
                }
            }
        }

        if (empty($search)) return $content;
        return str_replace($search, $replace, $content);
    }

    // generate some css dynamically and save it in a skin var
    public function generate($cssfile, $cssaltfile='', $target = NULL)
    {
        if (empty($cssfile)) throw new EmptyParameterException('cssfile');
        $file = $this->themedir . "/style/generators/" . $cssfile . '.css';
        if (!is_file($file)) {
            if (!empty($cssaltfile)) {
                $file = $this->themedir . "/style/generators/" . $cssaltfile . '.css';
                if (!is_file($file)) throw new FileNotFoundException($file);
            } else {
                throw new FileNotFoundException($file);
            }
        }
        //that is the cache key for this group of files for a given context
        $content = @file_get_contents($file);
        return $this->_compile($content, $target, TRUE);
    }

    protected function _getContextFileTime($url)
    {
        static $dummytime = NULL;
        $relativestylefile = str_replace($this->basepath,'',$url);
        if (is_file($relativestylefile)) {
            $filetime = filemtime($relativestylefile);
            return $filetime;
        }

        if ($dummytime === NULL) {
            // For cache files we create a dummy file
            if (is_writeable($this->csscachedir)) {
                $cachefileuri = $this->csscachedir . '/css_dummy.txt';
                $newcontent = 'Dummy file changed at '.time();
                file_put_contents($cachefileuri,$newcontent);
                $dummytime = filemtime($cachefileuri);
            }
        }

        return $dummytime;
    }

    protected function _getContext($styledata)
    {
        $target = $styledata['id'];
        $filetime = $this->_getContextFileTime($styledata['url']);

        if ($styledata['dynamic']) {
            if (!isset($this->_iscachedLcl[$target])) {
                $this->_cacheLcl[$target] = array();
                $this->_iscachedLcl[$target] = array();
            }

            $this->_cacheTgt = &$this->_cacheLcl[$target];
            $this->_iscachedTgt = &$this->_iscachedLcl[$target];

            foreach ($styledata['dynattributes'] as $name => $value) {
                if (!isset($this->_iscachedTgt[$name])) {
                    $this->_cacheTgt[$name] = $value;
                    $this->_iscachedTgt[$name] = TRUE;
                }
            }

            if (!empty($styledata['params']) && substr($styledata['params'],0,2) === 'a:') {
                $params = unserialize($styledata['params']);
            } else {
                $params = &$styledata['params'];
            }
            if (is_array($params)) {
                foreach ($params as $name => $value) {
                    if (!isset($this->_iscachedTgt[$name])) {
                        $this->_cacheTgt[$name] = $value;
                        $this->_iscachedTgt[$name] = TRUE;
                    }
                }
            }

            if (!empty($styledata['themevars'])) {
                if (strpos($styledata['themevars'], ',') !== FALSE) {
                    $themevars = explode(',', $styledata['themevars']);
                } else {
                    $themevars = array($styledata['themevars']);
                }

                foreach ($themevars as $themevar) {
                    $var = xarThemeVars::get($this->themename, $themevar, FALSE, FALSE);
                    if ($var === NULL) continue;
                    if (is_array($var)) {
                        foreach ($var as $name => $value) {
                            if (!isset($this->_iscachedTgt[$themevar.'_'.$name])) {
                                $this->_cacheTgt[$themevar.'_'.$name] = $value;
                                $this->_iscachedTgt[$themevar.'_'.$name] = TRUE;
                            }
                        }
                    } else {
                        if (!isset($this->_iscachedTgt[$themevar])) {
                            $this->_cacheTgt[$themevar] = $var;
                            $this->_iscachedTgt[$themevar] = TRUE;
                        }
                    }
                }
            }

            $stylefiletime = xarTpl::styleThemeTemplateLastChange();
            return sha1(serialize(array($this->themename, $styledata, $filetime, $target, $stylefiletime, $this->_cacheGbl, $this->_cacheTgt, $this->cssoptimize)));
        } else {
            return sha1(serialize(array($this->themename, $styledata, $filetime, $target, $this->cssoptimize)));
        }
    }
    /**
     * Process and aggregate the queue array
     * @param styles (array) the queue array
     * @return array the process queue
     */
    public function process($styles)
    {
        $cssfiles = array();
        $cachestyles = array();
        $context = '';

        // Seems that flushCssCache is very slow. We don't want to call it every times
        $this->flushCssCache($this->cachefilenumber);

        foreach ($styles as $scope=>$cssstyle) {
            foreach ($cssstyle as $csstype =>$cssinfo) {
                foreach ($cssinfo as $csslink => $cssinstances) {
                    foreach ($cssinstances as $cssid => $styledata) {
                        $sd = &$styles[$scope][$csstype][$csslink][$cssid];
                        if ($this->dynamic && $sd['dynamic']) {
                            $dyncontext = $this->_getContext($sd);
                            $cachefile = $this->_createCssCacheFile(array(array(array(array($sd)))), $dyncontext, TRUE);
                            $cachefileurl = str_replace('./', $this->basepath, $cachefile);
                            $cachename = basename($cachefile);
                            $sd['scope'] = 'cache';
                            $sd['processed'] = TRUE;
                            $sd['url'] = $cachefileurl;
                            $sd['dynamic'] = FALSE;
                        }
                        if ($this->aggregate) {
                            $noaggregate = $sd['aggregate'] === FALSE || $sd['alternate']  == TRUE;
                            if (isset($sd['url'])
                                    && ($sd['media'] == 'screen' || $sd['media'] == 'all')
                                    && ($csstype != 'embed')
                                    && $noaggregate === FALSE
                                    && (!isset($sd['condition']) || empty($sd['condition']))) {
                                $context .= $this->_getContext($sd);
                                $cssfiles[$scope][$csstype][$csslink][$cssid] = $styles[$scope][$csstype][$csslink][$cssid];
                                unset($sd, $styles[$scope][$csstype][$csslink][$cssid]);
                            }
                        }
                    }
                    if (empty($styles[$scope][$csstype][$csslink])) unset($styles[$scope][$csstype][$csslink]);
                }
                if (empty($styles[$scope][$csstype])) unset($styles[$scope][$csstype]);
            }
            if (empty($styles[$scope])) unset($styles[$scope]);
        }
        $cachestyles = array();
        if (!empty($cssfiles)) {
            $cachefile = $this->_createCssCacheFile($cssfiles, $context, FALSE);
            $cachefileurl = str_replace('./',$this->basepath,$cachefile);
            $cachename = basename($cachefile);
            //create the array to send back to the deliver class

            $cachestyles['link'] =
                    array("$cachefileurl" => array( 'aggregation' => array(
                                        'scope'=>'cache',
                                        'method'=> 'link',
                                        'file'=> "$cachename",
                                        'fileext' => 'css',
                                        'source' =>'',
                                        'rel'=> 'stylesheet',
                                        'type'=> 'text/css',
                                        'media'=> 'screen',
                                        'title'=>'',
                                        'condition'=>'',
                                        'version'=>'',
                                        'dynamic' => FALSE,
                                        'processed'=> TRUE,
                                        'aggregate'=> TRUE,
                                        'url'=>$cachefileurl
                                        )));
            //add the cachestyles to the remaining styles that were not aggregated
            $styles['cached'] = $cachestyles;
        } else {
            $this->aggregate = FALSE; // We use this to notify the queue to not sort
        }
        //return them for rendering

        return $styles;
    }

}

?>
