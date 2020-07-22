<?php
/**
 * BlockLayout Template Engine
 *
 * @package Xarigami core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage blocklayout
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

sys::import('xarigami.exceptions.types');
/**
 * Exceptions for this subsystem
 *
**/

class DeprecatedTemplateFunctionException extends DeprecationExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML("The function '#(1)' in xarTemplate.php is deprecated.");
        else
            return $this->xarML("This function in xarTemplate.php is deprecated.");
    }
}


class BLValidationException extends ValidationExceptions
{
    protected $message = 'A blocklayout tag or attribute construct was invalid, see the tag documentation for the correct syntax';
}
class DuplicateTagException extends DuplicationExceptions
{
    protected $message = 'The tag definition for the tag: "#(1)" already exists.';
}

class BLException extends xarExceptions
{
    protected $message = 'Unknown blocklayout exception (TODO)';
}

/**
 * Defines for template handling
 *
 */

/// OLD STUFF //////////////////////////////////
define ('XAR_TPL_OPTIONAL', 2);
define ('XAR_TPL_REQUIRED', 0); // default for attributes

define ('XAR_TPL_STRING', 64);
define ('XAR_TPL_BOOLEAN', 128);
define ('XAR_TPL_INTEGER', 256);
define ('XAR_TPL_FLOAT', 512);
define ('XAR_TPL_ANY', XAR_TPL_STRING|XAR_TPL_BOOLEAN|XAR_TPL_INTEGER|XAR_TPL_FLOAT);
/// END OLD STUFF

/**
 * Define for reg expressions for attributes and tags
 *
 */
define ('XAR_TPL_ATTRIBUTE_REGEX','/^[a-z][-_a-z0-9]*$/i');
define ('XAR_TPL_TAGNAME_REGEX',  '/^[a-z][-_a-z0-9]*$/i');

/**
 * Defines for tag properties
 *
 */
define('XAR_TPL_TAG_HASCHILDREN'               ,1);
define('XAR_TPL_TAG_HASTEXT'                   ,2);
define('XAR_TPL_TAG_ISASSIGNABLE'              ,4);
define('XAR_TPL_TAG_ISPHPCODE'                 ,8);
define('XAR_TPL_TAG_NEEDASSIGNMENT'            ,16);
define('XAR_TPL_TAG_NEEDPARAMETER'             ,32);
define('XAR_TPL_TAG_NEEDEXCEPTIONSCONTROL'     ,64);

/**
 * Miscelaneous defines
 *
 */
// Let's do this once here, not scattered all over the place
if(!defined('XAR_TPL_CACHE_DIR')) {
    define('XAR_TPL_CACHE_DIR',sys::varpath() . '/cache/templates');
}

class xarTpl extends xarObject
{
    const TPL_OPTIONAL = XAR_TPL_OPTIONAL;
    const TPL_REQUIRED = XAR_TPL_REQUIRED; // default for attributes
    const TPL_STRING = XAR_TPL_STRING;
    const TPL_BOOLEAN = XAR_TPL_BOOLEAN;
    const TPL_INTEGER = XAR_TPL_INTEGER;
    const TPL_FLOAT = XAR_TPL_FLOAT;
    const TPL_ANY = XAR_TPL_ANY;

    protected static $_themesBaseDir = NULL;
    protected static $_themeName = NULL;
    protected static $_themeDir = NULL;
    protected static $_defaultThemeDir = NULL;
    protected static $_cacheTemplates = NULL;
    protected static $_generateXMLURLs = NULL;
    protected static $_doctype = NULL;
    protected static $_additionalStyles = NULL;
    protected static $_pageTemplateName = NULL;
    protected static $_pageRawTitle = '';
    protected static $_pageTitle = '';
    protected static $_outputTemplateFilenames = FALSE;
    protected static $_outputPHPCommentBlockInTemplates = FALSE;
    protected static $_varDump = FALSE;
    /**
     * Initializes the BlockLayout Template Engine
     *
     * @access protected
     * @param  array  $args   Elements: themesBaseDir, defaultThemeName, enableTemplateCaching
     * @param  int    $whatElseIsGoingLoaded Bitfield to specify which subsystem will be loaded.
     * @return bool true
     */
    public static function init($args, $whatElseIsGoingLoaded)
    {
        self::$_themesBaseDir = $args['themesBaseDirectory'];
        self::$_defaultThemeDir = $args['defaultThemeDir'];
        self::$_cacheTemplates = $args['enableTemplatesCaching'];
        self::$_generateXMLURLs = $args['generateXMLURLs'];
        self::$_doctype = '';
        self::$_additionalStyles = '';
        self::$_themeName = NULL;
        self::$_themeDir = NULL;
        self::$_pageTemplateName = NULL;
        self::$_pageRawTitle = '';
        self::$_pageTitle = '';

        if (sys::isStable() && function_exists('xarModGetVar')){
            self::$_outputTemplateFilenames = xarModGetVar('themes', 'ShowTemplates');
            self::$_outputPHPCommentBlockInTemplates = xarModGetVar('themes', 'ShowPHPCommentBlockInTemplates');
            self::$_varDump = xarModGetVar('themes', 'var_dump');
        } else {
            self::$_outputTemplateFilenames = FALSE;
            self::$_outputPHPCommentBlockInTemplates = FALSE;
            self::$_varDump = FALSE;
        }

        if (!self::setThemeDir(self::$_defaultThemeDir)) {
            // If there is no theme, there is no page template, we dont know what to do now.
            throw new BadParameterException(self::$_defaultThemeDir, 'xarTpl::init Nonexistent theme directory: "#(1)" ');
        }

        if (!xarTplSetPageTemplateName('default')) {
            // If there is no page template, we can't show anything
            //xarCore_die("xarTpl_init: Nonexistent default.xt page in theme directory '". xarTplGetThemeDir() ."'");
            throw new BadParameterException(self::getThemeDir(), 'xarTpl::init Nonexistent default.xt page in theme directory  "#(1)" ');
        }

        if (self::$_cacheTemplates !== NULL) {
            if (!is_writeable(XAR_TPL_CACHE_DIR)) {
                $msg = "xarTpl::init Cannot write in cache/templates directory '"
                    . XAR_TPL_CACHE_DIR . "', but the setting: 'cache templates' is set to 'On'.\n"
                    ."Either change the permissions on the mentioned file/directory or set template caching to 'Off' (not recommended).";
                self::$_cacheTemplates = FALSE;
            }
        }
        return TRUE;
    }

    public static function generateXMLURLs()
    {
        return self::$_generateXMLURLs;
    }

    public static function cacheTemplates()
    {
        return self::$_cacheTemplates;
    }

    public static function outputPHPCommentInTemplates()
    {
        return self::$_outputPHPCommentBlockInTemplates;
    }

    public static function outputTemplateFilenames()
    {
        return self::$_outputTemplateFilenames;
    }

    /**
     * Get theme name for the theme in use.
     *
     * @access public
     * @return string themename
     */
    public static function getThemeName()
    {
        if (self::$_themeName === NULL) {
            // If it is not set, set it and return the default theme.
            if (function_exists('xarUserGetVar') && method_exists('xarModUserVars', 'get')) {
               $uid = xarUserGetVar('uid');
                $defaultTheme = xarModUserVars::get('themes', 'default',$uid);
                if (!empty($defaultTheme)) self::setThemeName($defaultTheme);
            }
        }
        return self::$_themeName;
    }

    /**
     * Set theme name
     *
     * @access public
     * @param  string $themeName Themename to set
     * @return bool
     */
    public static function setThemeName($themeName)
    {
        return self::__setThemeNameAndDir($themeName);
    }

    /**
     * Set theme dir
     *
     * @access public
     * @param  string themeDir
     * @return bool
     */
    public static function setThemeDir($themeDir=NULL)
    {
        if ($themeDir === NULL) {
            $defaultTheme = xarModGetVar('themes','default');
            $themeDir =  xarMod::getDirFromName($defaultTheme,'theme');
        }

        $themeDir = self::$_themesBaseDir.'/'.$themeDir;
        if (!is_dir($themeDir)) return FALSE;

        self::$_themeDir = $themeDir;
        return TRUE;
    }

    /**
     * Private helper function for the xarTplSetThemeName and xarTplSetThemeDir
     *
     * @access private
     * @param  string $name Name of the theme
     * @todo jojo- deprecate this after further testing now we have separate theme and dir names
     * @return void
     */
    private static function __setThemeNameAndDir($name)
    {
        self::$_themeName = $name;

        //now set the directory at least for the time being
        if (function_exists('xarModGetDirFromName')) {
            $themeDir = xarMod::getDirFromName($name,'theme');
        } elseif (!empty(self::$_defaultThemeDir)) {
            //just in case - until we have installer sorted out - fall back to default theme dir
            $themeDir = self::$_defaultThemeDir;
        } else {
            return FALSE;
        }

        self::$_themeDir = self::$_themesBaseDir.'/'.$themeDir;
        return TRUE;
    }

    /**
     * Get theme directory
     *
     * @access public
     * @return sring  Theme directory
     */
    public static function getThemeDir()
    {

        if (self::$_themeDir === NULL) {
            // If it is not set, set it and return the default theme.dir
            //get the theme
            $theme = self::getThemeName();
            $themeDir = xarMod::getDirFromName($theme,'theme');
            $themeDir = self::$_themesBaseDir.'/'.$themeDir;
            if (is_dir($themeDir)) {
                self::$_themeDir = $themeDir;
            }
        }
        return self::$_themeDir;
    }

    /**
     * Get page template name
     *
     * @access public
     * @return string page template name
     */
    public static function getPageTemplateName()
    {
        return self::$_pageTemplateName;
    }

    /**
     * Set the Admin template or page
     * jojo - we need to fall back to main theme if admin template  doesn't exist
     */
    public static function setAdminTheme($modName='', $template='default')
    {
        $istemplate = TRUE;
        if (xarModVars::get('themes', 'usedashboard')) {
            $dashtemplate = xarModVars::get('themes', 'dashtemplate');
            //if dashboard is enabled, use the dashboard template else fallback
            //on the normal template override system for admin templates
            if (!self::setPageTemplateName($dashtemplate.'-'.$modName)) {
               $istemplate  = self::setPageTemplateName($dashtemplate);
            }
        } elseif (xarModVars::get('themes', 'useadmintheme')) {
            $admintheme = xarModVars::get('themes', 'admintheme');
            if (!empty($admintheme)) {
                self::setThemeName($admintheme);
            }
        }
        if (self::getPageTemplateName() == 'default') {
            // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!self::setPageTemplateName('admin-'.$modName)) {
                if (!self::setPageTemplateName('admin')) {
                     $istemplate = self::setPageTemplateName('default');
                } else {
                     $istemplate = self::setPageTemplateName('admin');
                }
            }
        }
        if (!$istemplate) {
            //admin theme or template not available
            if (function_exists('xarModGetUserVar')) {
               $uid = xarUserGetVar('uid');
                $defaulttheme = xarModUserVars::get('themes', 'default',$uid);
            }
            self::setThemeName($defaulttheme);
            self::setPageTemplateName('default');
        }
    }

    /**
     * Set page template name
     *
     * @access public
     * @param  string $templateName Name of the page template
     * @return bool
     */
    public static function setPageTemplateName($templateName)
    {
        if (empty($templateName)) {
            $templateName = 'default';
        }

        if (!file_exists(xarTplGetThemeDir() . "/pages/$templateName.xt")) {
            return FALSE;
        }
        self::$_pageTemplateName = $templateName;
        return TRUE;
    }

    /**
     * Get doctype declared by page template
     *
     * @access public
     * @return string doctype identifier
     */
    public static function getDoctype()
    {
        return self::$_doctype;
    }

    /**
     * Set doctype declared by page template
     *
     * @access public
     * @param  string $doctype Identifier string of the doctype
     * @return bool
     */
    public static function setDoctype($doctype)
    {
        if (!is_string($doctype)) throw new BadParameterException();
        self::$_doctype = $doctype;
        return TRUE;
    }

    /**
     * Set page title
     *
     * @access public
     * @param  string $title
     * @param  string $module
     * @todo   this needs to be moved into the templating domain somehow
     * @return bool
     */
    public static function setPageTitle($title = NULL, $module = NULL)
    {
        //xarLogMessage("TPL: Setting pagetitle to $title");
        self::$_pageRawTitle = $title;
        if (!function_exists('xarModGetVar')){
            self::$_pageTitle = $title;
        } else {
            $order      = xarModVars::get('themes', 'SiteTitleOrder');
            $separator  = xarModVars::get('themes', 'SiteTitleSeparator');
            if (empty($module)) {
                $module = xarMod::getDisplayableName();
            }
            //title is sometimes empty
            $titleafter = !empty($title) ? $separator.$title: '';
            $titlebefore = !empty($title) ? $title.$separator: '';
            switch(strtolower($order)) {
                case 'default':
                default:
                    self::$_pageTitle = xarModGetVar('themes', 'SiteName') . $separator . $module . $titleafter;
                break;
                case 'sp':
                    self::$_pageTitle = xarModGetVar('themes', 'SiteName') . $titleafter;
                break;
                case 'mps':
                    self::$_pageTitle = $module . $titleafter . $separator .  xarModGetVar('themes', 'SiteName');
                break;
                case 'ps':
                    self::$_pageTitle = $titlebefore. xarModGetVar('themes', 'SiteName');
                break;
                case 'pms':
                    self::$_pageTitle = $titlebefore .  $module . $separator . xarModGetVar('themes', 'SiteName');
                break;
                case 'to':
                    self::$_pageTitle = $title;
                break;
            }
        }

        return TRUE;
    }

    /**
     * Get page title (eventually formatted)
     *
     * @access public
     * @return string
     */
    public static function getPageTitle()
    {
        return self::$_pageTitle;
    }

    /**
     * Get page title (raw format)
     *
     * @access public
     * @return string
     */
    public static function getPageRawTitle()
    {
        return self::$_pageRawTitle;
    }


    /**
     * Set user message
     *
     * @access public
     * @param string  $message The message displayed to the user
     * @param string  $type    The type of message either 'status', 'alert', 'error'
     * @param boolean $persist (optional) Do not clear the message if it is already in the queue
     */
    public static function setMessage($message = NULL, $type = 'status', $persist= TRUE)
    {
        //if there is a message continue otherwise don't bother
        if (isset($message)) {
            $messages = xarSession::getVar('xarmessages');
            if (!isset($messages[$type]) || !is_array($messages[$type])) $messages[$type] = array();
            //set the message if necessary
            if (($persist === TRUE) || !in_array($message, $messages[$type])) {
                $messages[$type][] = $message;
                xarSession::setVar('xarmessages',$messages);
            }
        }
        return true;
    }
    /**
     * Get user messages
     *
     * @access public
     * @param string  $type         The type of message either 'status', 'alert', 'error'
     * @param boolean $flush        (optional) Flush the messages
     * @param string $usetemplate  (optional) The return is templated using $template if provided
     * @return array  An associative array of messages of $type, or all message types of no $type given
     *                The array is returned templated, unless $usertemplate is FALSE
     */
    public static function getMessage($type = NULL, $template = 'user', $flush = TRUE )
    {
        $messages = xarSession::getVar('xarmessages');
        $usermessages = array();

        if (isset($messages) && !empty($messages))
        {
            if (isset($type) && !empty($type)) {
                $usermessages = array($type => $messages[$type]);
                if ($flush === TRUE) {
                    unset($messages[$type]);
                }
            } else {
                $usermessages = $messages;

                 if ($flush === TRUE) $messages = array(); //reset them all
            }

            if (isset($messages)) {
                xarSession::setVar('xarmessages',$messages);
            }
        }
        if (!isset($usermessages)) $usermessages = array();

        $data['usermessages'] = $usermessages;

        if (empty($template))
        {
            return $data;
        } else {
           return xarTpl::module('base', 'message',  $template, $data);
        }
    }

    /**
     * Turns module output into a template.
     *
     * @access public
     * @param  string $modName      the module name
     * @param  string $modType      user|admin
     * @param  string $funcName     module function to template
     * @param  array  $tplData      arguments for the template
     * @param  string $templateName string the specific template to call
     * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
     */
    public static function module($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
    {
        if (!empty($templateName)) {
            $templateName = xarVarPrepForOS($templateName);
        }

        // Basename of module template is apitype-functioname
        $tplBase        = "$modType-$funcName";

        // Get the right source filename
        $sourceFileName = self::__getSourceFileName($modName, $tplBase, $templateName);
        // Common data for BL
        $tplData['_bl_module_name'] = $modName;
        $tplData['_bl_module_type'] = $modType;
        $tplData['_bl_module_func'] = $funcName;
        $tpl = (object) null;
        $tpl->pageTitle = self::getPageTitle();
        $tplData['tpl'] = $tpl;

        // TODO: make this work different, for example:
        // 1. Only create a link somewhere on the page, when clicked opens a page with the variables on that page
        // 2. Create a page in the themes module with an interface
        // 3. Use 1. to link to 2.
        if (self::$_varDump){
            if (function_exists('var_export')) {
                $pre = var_export($tplData, true);
                echo "<pre>$pre</pre>";
            } else {
                echo '<pre>',var_dump($tplData),'</pre>';
            }
        }

        return self::__executeFromFile($sourceFileName, $tplData);
    }


    /**
     * Renders a block content through a block template.
     *
     * @access public
     * @param  string $modName   the module name
     * @param  string $blockType the block type (xar_block_types.xar_type)
     * @param  array  $tplData   arguments for the template
     * @param  string $tplName   the specific template to call
     * @param  string $tplBase   the base name of the template (defaults to $blockType)
     * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
     */
    public static function block($modName, $blockType, $tplData = array(), $tplName = NULL, $tplBase = NULL)
    {
        if (!empty($tplName)) {
            $tplName = xarVarPrepForOS($tplName);
        }

        // Basename of block can be overridden
        $templateBase   = xarVarPrepForOS(empty($tplBase) ? $blockType : $tplBase);

        // Get the right source filename
        $sourceFileName =  self::__getSourceFileName($modName, $templateBase, $tplName, 'blocks');

        return self::__executeFromFile($sourceFileName, $tplData);
    }
    /**
     * Renders a DD element (object or property) through a template.
     *
     * @access private
     * @param  string $modName      the module name owning the object/property, with fall-back to dynamicdata
     * @param  string $ddName       the name of the object/property type, or some other name specified in BL tag or API call
     * @param  string $tplType      the template type to render
     *                              properties: ( showoutput(default)|showinput|showhidden|validation|label )
     *                              objects   : ( showdisplay(default)|showview|showform|showlist )
     * @param  array  $tplData      arguments for the template
     * @param  string $tplBase      the template type can be overridden too ( unused )
     * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
     */
    public static function ddElement($modName, $ddName, $tplType, $tplData, $tplBase,$elements)
    {
        $tplType = xarVarPrepForOS($tplType);

        // Template type for the property can be overridden too (currently unused)
        $templateBase   = xarVarPrepForOS(empty($tplBase) ? $tplType : $tplBase);

        // Get the right source filename
        $sourceFileName = self::__getSourceFileName($modName, $templateBase, $ddName, $elements);

        // Final fall-back to default template in dynamicdata
        if ((empty($sourceFileName) || !file_exists($sourceFileName)) &&
            $modName != 'dynamicdata') {
            $sourceFileName = self::__getSourceFileName('dynamicdata', $templateBase, $ddName, $elements);
        }

        return self::__executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Execute a pre-compiled template string with the supplied template variables
     *
     * @access public
     * @param  string $templateCode pre-compiled template code (see xarTplCompileString)
     * @param  array  $tplData      template variables
     * @return string filled-in template
     */
    public static function string($templateCode, $tplData)
    {
        return self::__execute($templateCode, $tplData);
    }

    /**
     * Execute a specific template file with the supplied template variables
     *
     * @access public
     * @param  string $fileName location of the template file
     * @param  array  $tplData  template variables
     * @return string filled-in template
     */
    public static function file($fileName, $tplData)
    {
        return self::__executeFromFile($fileName, $tplData);
    }

    /**
     * Renders a page template.
     *
     * @access protected
     * @param  string $mainModuleOutput       the module output
     * @param  string $otherModulesOutput
     * @param  string $templateName           the template page to use without extension .xt
     * @return string
     *
     * @todo Needs a rewrite, i.e. finalisation of tplOrder scenario
     */
    public static function renderPage($mainModuleOutput, $otherModulesOutput = NULL, $templateName = NULL)
    {
        if (empty($templateName)) {
            $templateName = self::getPageTemplateName();
        }

        // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
        $templateName = xarVarPrepForOS($templateName);
        $sourceFileName = self::getThemeDir() . "/pages/$templateName.xt";

        $tpl = (object) null; // Create an object to hold the 'specials'
        $tpl->pageTitle = self::getPageTitle();

        // NOTE: This MUST be a reference, since we havent filled the global yet at this point
        $tpl->additionalStyles =& self::$_additionalStyles;

        $tplData = array(
            'tpl'                      => $tpl,
            '_bl_mainModuleOutput'     => $mainModuleOutput,
        );

        return  self::__executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Render a block box
     *
     * @access protected
     * @param  array  $blockInfo  Information on the block
     * @param  string $templateName string
     * @return bool xarTpl__executeFromFile($sourceFileName, $blockInfo)
     *
     * @todo the search logic for the templates can perhaps use the private function?
     * @todo fallback to some internal block box template?
     */
    public static function renderBlockBox($blockInfo, $templateName = NULL)
    {
        // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
        $templateName = xarVarPrepForOS($templateName);
        $themeDir = self::getThemeDir();

        if (!empty($templateName) && file_exists("$themeDir/blocks/$templateName.xt")) {
            $sourceFileName = "$themeDir/blocks/$templateName.xt";
        } else {
            // We must fall back to the default, as the template passed in could be the group
            // name, allowing an optional template to be utilised.
            $templateName = 'default';
            $sourceFileName = "$themeDir/blocks/default.xt";
        }
        return self::__executeFromFile($sourceFileName, $blockInfo);
    }

    /**
     * Include a subtemplate from the theme space
     *
     * @access protected
     * @param  string $templateName Basically handler function for <xar:template type="theme".../>
     * @param  array  $tplData      template variables
     * @return string
     */
    public static function includeThemeTemplate($templateName, $tplData)
    {
        // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
        $templateName = xarVarPrepForOS($templateName);
        $sourceFileName = self::getThemeDir() ."/includes/$templateName.xt";
        return self::__executeFromFile($sourceFileName, $tplData);
    }

    /**
     * Include the skin subtemplate from the theme space if it exists
     *
     * @access protected
     * @param  string $templateName Basically handler function for <xar:template type="theme".../>
     * @param  array  $tplData      template variables
     * @return string
     */
    public static function styleThemeTemplate($templateName = NULL, $tplData)
    {
        $path = self::getThemeDir() .'/style/processing/';
        if ($templateName !== NULL) {
            $template = xarVarPrepForOS($templateName).'.xt';
        } else {
            $template = 'skins.xt'; // Main skins template
        }
        $sourceFileName = $path . $template;
        if (is_file($sourceFileName)) {
            self::__executeFromFile($sourceFileName, $tplData);
        }
    }

    /**
     * Get the timestamp of the latest modified file in the style template folder
     * @return int the timestamp of the lastest file change
     */
    public static function styleThemeTemplateLastChange()
    {
        $sourceFileName = self::getThemeDir() ."/style/processing/skins.xt";
        $path = self::getThemeDir() .'/style/processing/';
        $files = glob($path.'*.xt');
        $lastchange = 0;

        foreach ($files as $file) {
             $time = filemtime($file);
             if ($lastchange < $time) $lastchange = $time;
        }
        return $lastchange;
    }

    /**
     * Include a subtemplate from the module space
     *
     * @access protected
     * @param  string $modName      name of the module from which to include the template
     * @param  string $templateName Basically handler function for <xar:template type="module".../>
     * @param  array  $tplData      template variables
     * @return string
     */
    public static function includeModuleTemplate($modName, $templateName, $tplData)
    {
        // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
        $templateName = xarVarPrepForOS($templateName);
        $sourceFileName = self::getThemeDir() . "/modules/$modName/includes/$templateName.xt";
        if (!file_exists($sourceFileName)) {
            $sourceFileName = "modules/$modName/xartemplates/includes/$templateName.xd";
        }
        return self::__executeFromFile($sourceFileName, $tplData);
    }
    /**
     * Execute Template, i.e. run the compiled php code of a cached template
     *
     * @access private
     * @param  string $templateCode   Templatecode to execute
     * @param  array  $tplData        Template variables
     * @param  string $sourceFileName
     * @return string output
     *
     * @todo Can we migrate the eval() out, as that is hard to cache?
     * @todo $sourceFileName looks wrong here
     */
    private static function __execute($templateCode, $tplData, $sourceFileName = '', $cachedFileName = null)
    {
        assert('is_array($tplData); /* Template data should always be passed in an array */');

        //POINT of ENTRY for cleaning variables
        // We need to be able to figure what is the template output type: RSS, XHTML, XML or whatever

        $tplData['_bl_data'] = &$tplData;
        extract($tplData, EXTR_OVERWRITE);

        // Start output buffering
        ob_start();
        try {
            if(!isset($cachedFileName)) {
                // This eval is only used for cases like xarTplString, which is quite rare, and should probably not exist
                // TODO: consider writing it to a temp file and using include here too, so the bytecacher can use it (risky?)
                // and we can get rid of the eval alltogether.
                eval('?>' . $templateCode);
            } else {
                // Otherwise use an include, much better :-)
                assert('file_exists($cachedFileName); /* Compiled templated disappeared in mid air, race condition? */');
                $res = include($cachedFileName);
            }

            if($sourceFileName != '') {
                $tplOutput = ob_get_contents();
                ob_end_clean();
                ob_start();
                // this outputs the template and deals with start comments accordingly.
                echo self::outputTemplate($sourceFileName, $tplOutput);
            }
        } catch(Exception $e) {
            ob_clean();
            throw $e;
        }
        // Fetch output and clean buffer
        $output = ob_get_contents();
        ob_end_clean();

        // Return output
        return $output;
    }

    /**
     * Execute template from file
     *
     * @access private
     * @param  string $sourceFileName       From which file do we want to execute?
     * @param  array  $tplData              Template variables
     * @return mixed
     *
     * @todo  inserting the header part like this is not output agnostic
     * @todo  insert log warning when double entry in cachekeys occurs? (race condition)
     * @todo  make the checking whethet templatecode is set more robst (related to templated exception handling)
     */
    private static function __executeFromFile($sourceFileName, $tplData)
    {
        assert('is_array($tplData); /* Template data should always be passed in an array */');
        // Load up translations for the files
        xarMLSLoadTranslations($sourceFileName);

        // Do we need to compile?
        $needCompilation = true;
        $cachedFileName = null;
        if (self::$_cacheTemplates) {
            $cacheKey = self::__getCacheKey($sourceFileName);
            $cachedFileName = XAR_TPL_CACHE_DIR . '/' . $cacheKey . '.php';
            if (file_exists($cachedFileName)
                && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
                $needCompilation = false;
            }
        }

        if (!file_exists($sourceFileName) && $needCompilation == true) {
            throw new BadParameterException($sourceFileName,'Template does not exist  "#(1)" ');
        }
        xarLogMessage("Using template : $sourceFileName");
        //xarLogVariable('needCompilation', $needCompilation, XARLOG_LEVEL_ERROR);
        $templateCode = null;

        if ($needCompilation) {
            $blCompiler = xarTpl__getCompilerInstance();
            $templateCode = $blCompiler->compileFile($sourceFileName);
            // we check the error stack here to make sure no new errors happened during compile
            // but we do not check the core stack
            if (!isset($templateCode)) {
                return; // exception! throw back
            }
            if (self::$_cacheTemplates) {
                $fd = fopen($cachedFileName, 'w');
                if(xarTpl_outputPHPCommentBlockInTemplates()) {
                    $commentBlock = "<?php\n/*"
                                  . "\n * Source:     " . $sourceFileName
                                  . "\n * Theme:      " . xarTplGetThemeName()
                                  . "\n * Compiled: ~ " . date('Y-m-d H:i:s T', filemtime($cachedFileName))
                                  . "\n */\n?>\n";
                    fwrite($fd, $commentBlock);
                }
                fwrite($fd, $templateCode);
                fclose($fd);
                // Add an entry into CACHEKEYS
                self::__setCacheKey($sourceFileName);
            }
        }

        // Execute either the compiled template, or the code determined
        // TODO: this signature sucks

        $output = self::__execute($templateCode,$tplData, $sourceFileName, $cachedFileName);

        return $output;
    }

    /**
     * Determine the template sourcefile to use
     *
     * Based on the module, the basename for the template
     * a possible overribe and a subpart and the active
     * theme, determine the template source we should use and loads
     * the appropriate translations based on the outcome.
     *
     * @param  string $modName      Module name doing the request
     * @param  string $tplBase      The base name for the template
     * @param  string $templateName The name for the template to use if any
     * @param  string $tplSubPart   A subpart ('' or 'blocks' or 'properties')
     * @return string
     *
     * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction)
     */
    private static function __getSourceFileName($modName,$tplBase, $templateName = NULL, $tplSubPart = '')
    {
        if(function_exists('xarMod_getBaseInfo')) {
            if(!($modBaseInfo = xarMod::getBaseInfo($modName))) return;
            $modOsDir = $modBaseInfo['osdirectory'];
        } elseif(!empty($modName)) {
            $modOsDir = $modName;
        }

        // For modules: {tplBase} = {modType}-{funcName}
        // For blocks : {tplBase} = {blockType} or overridden value
        // For props  : {tplBase} = {propertyName} or overridden value

        // Template search order:
        // 1. {theme}/modules/{module}/{tplBase}-{templateName}.xt
        // 2. modules/{module}/xartemplates/{tplBase}-{templateName}.xd
        // 3. {theme}/modules/{module}/{tplBase}.xt
        // 4. modules/{module}/xartemplates/{tplBase}.xd
        // 5. {theme}/modules/{module}/{templateName}.xt (-syntax)
        // 6. modules/{module}/xartemplates/{templateName}.xd (-syntax)
        // 7. complain (later on)

        $tplThemesDir = self::getThemeDir();
        $tplBaseDir   = "modules/$modOsDir";
        $subpart = !empty($tplSubPart)? $tplSubPart.'/':'';
        $use_internal = false;
        unset($sourceFileName);

        xarLogMessage("TPL: 1. $tplThemesDir/$tplBaseDir/{$subpart}$tplBase-$templateName.xt");
        xarLogMessage("TPL: 2. $tplBaseDir/xartemplates/{$subpart}$tplBase-$templateName.xd");
        xarLogMessage("TPL: 3. $tplThemesDir/$tplBaseDir/{$subpart}$tplBase.xt");
        xarLogMessage("TPL: 4. $tplBaseDir/xartemplates/{$subpart}$tplBase.xd");

        $canTemplateName = strtr($templateName, "-", "/");
        $canonical = ($canTemplateName == $templateName) ? false : true;

        if(!empty($templateName) &&
            file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/{$subpart}$tplBase-$templateName.xt")) { //1.
            $tplBase .= "-$templateName";
        } elseif(!empty($templateName) &&
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/{$subpart}$tplBase-$templateName.xd")) {//2.
            $use_internal = true;
            $tplBase .= "-$templateName";
        } elseif(
            file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/{$subpart}$tplBase.xt")) {//3.
            ;
        } elseif(
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/{$subpart}$tplBase.xd")) {
            $use_internal = true;
        } elseif($canonical &&
            file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/{$subpart}$canTemplateName.xt")) {
        } elseif($canonical &&
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/$canTemplateName.xd")) {//4
            $use_internal = true;
        } else {
            xarLogMessage("TPL Error: Template $templateName for tplBaseDir $tplBaseDir and tplThemesDir $tplThemesDir could not be found.");
            // CHECKME: should we do something here ? At the moment, translations still get loaded,
            //          the (invalid) $sourceFileName gets passed back to xarTpl*, and we'll get
            //          an exception when it's passed to xarTpl__executeFromFile().
            //          We probably don't want to throw an exception here, but we might return
            //          now, or have some final fall-back template in base (resp. DD for properties)
        }
        // Subpart may have been empty,
        $sourceFileName = str_replace('//','/',$sourceFileName);
        // assert('isset($sourceFileName); /* The source file for the template has no value in xarTplModule */');

        // Load the appropriate translations
        if($use_internal) {
            $domain  = XARMLS_DNTYPE_MODULE; $instance= $modName;
            $context = rtrim("modules:templates/$tplSubPart",'/');
        } else {
            $domain = XARMLS_DNTYPE_THEME; $instance = xarTpl::getThemeName();
            $context = rtrim("themes:modules/$modName/$tplSubPart",'/');
        }

        return $sourceFileName;
    }


    /**
     * Output template
     *
     * @access private
     * @param  string $sourceFileName
     * @param  string $tplOutput
     * @return void
     *
     * @todo Rethink this function, it contains hardcoded xhtml
     */
    public static function outputTemplate($sourceFileName, &$tplOutput)
    {
        // flag used to determine if the header content has been found.
        static $isHeaderContent;
        if(!isset($isHeaderContent))
            $isHeaderContent = false;

        $finalTemplate ='';
        if (self::$_outputTemplateFilenames) {
            $outputStartComment = true;
            if($isHeaderContent === false) {
                if($isHeaderContent = self::modifyHeaderContent($sourceFileName, $tplOutput))
                    $outputStartComment = false;
            }
            // optionally show template filenames if start comment has not already
            // been added as part of a header determination.
            if($outputStartComment === true)
                $finalTemplate .= "<!-- start: " . $sourceFileName . " -->\n";
            $finalTemplate .= $tplOutput;
            $finalTemplate .= "<!-- end: " . $sourceFileName . " -->\n";
        } else {
            $finalTemplate .= $tplOutput;
        }
        return $finalTemplate;
    }

    /**
     * Modify header content
     *
     * Attempt to determine if $tplOutput contains header content and if
     * so append a start comment after the first matched header tag
     * found.
     *
     * @access private
     * @param  string $sourceFileName
     * @param  string $tplOutput
     * @return bool found header content
     *
     * @todo it is possible that the first regex <!DOCTYPE[^>].*]> is too
     *       greedy in more complex xml documents and others.
     * @todo The doctype of the output belongs in a template somewhere (probably the xar:blocklayout tag, as an attribute
     */
    public static function modifyHeaderContent($sourceFileName, &$tplOutput)
    {
        $foundHeaderContent = false;

        // $headerTagsRegexes is an array of string regexes to match tags that could
        // be sent as part of a header. Important: the order here should be inside out
        // as the first regex that matches will have a start comment appended.
        // fixes bugs: #1427, #1190, #603, #3559, #6226
        // - Comments that precede <!doctype... cause ie6 not to sniff the doctype
        //   correctly.
        // - xml parsers dont like comments that precede xml output.
        // At this time attempting to match <!doctype... and <?xml version... tags.
        // This is about the best we can do now, until we process xar documents with an xml parser and actually 'parse'
        // the document.
        //jojo - review these changes - causes validation problems
        $headerTagRegexes = array( //'<!DOCTYPE[^>].*]>',// eg. <!DOCTYPE doc [<!ATTLIST e9 attr CDATA "default">]>
                              //'<!DOCTYPE[^>]*>',// eg. <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                              '<!DOCTYPE.{0,}?>(?!\W{0,}])',  // Negative lookahead for first > which is not followed by ]
                              '<\?xml\s+version[^>]*\?>');// eg. <?xml version="1.0"? > // remove space between qmark and gt

        /* jojo - this causes incorrect parsing of special charcters such as $ in document titles etc
        $headerTagRegexes = array('<!DOCTYPE[^>].*]>',// eg. <!DOCTYPE doc [<!ATTLIST e9 attr CDATA "default">]>
                                '<!DOCTYPE[^>]*>',// eg. <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                                 '<\?xml\s+version[^>]*\?>');// eg. <?xml version="1.0"? > // remove space between qmark and gt
        */
        foreach($headerTagRegexes as $headerTagRegex) {
            if(preg_match("/$headerTagRegex/smix", $tplOutput, $matchedHeaderTag)) {
                // FIXME: the next line assumes that we are not in a comment already, no way of knowing that,
                // keep the functionality for now, but dont change more than necessary (see bug #3559)
                // $startComment = '<!-- start(output actually commenced before header(s)): ' . $sourceFileName . ' -->';
                $startComment ='';
                // jojo - Commenting out these changes - they cause problems in various renderings when comments in HTML turned on
                // $startComment = "\n<!-- start: " . $sourceFileName .
                //                ' (file started before DOCTYPE/xml header(s)!) -->';
                //                */
                // replace matched tag with an appended start comment tag in the first match
                // in the template output $tplOutput
                $tplOutput = preg_replace("/$headerTagRegex/smix", $matchedHeaderTag[0] . $startComment, $tplOutput, 1);
                // dont want start comment to be sent below as it has already been added.
               // don't set start comment in calling function as it was set here
                $foundHeaderContent = true;
                break;
            }
        }
        return $foundHeaderContent;
    }

    /**
     * Load template from file (e.g. for use with recurring template snippets someday,
     * using xarTplString() to "fill in" the template afterwards)
     *
     * @access private
     * @param  string $sourceFileName     From which file do we want to load?
     * @return mixed
     */
    private static function __loadFromFile($sourceFileName)
    {
        $needCompilation = true;

        if (self::$_cacheTemplates) {
            $cacheKey = self::__setCacheKey($sourceFileName);
            $cachedFileName = XAR_TPL_CACHE_DIR . '/' . $cacheKey . '.php';
            if (file_exists($cachedFileName)
                && (!file_exists($sourceFileName) || (filemtime($sourceFileName) < filemtime($cachedFileName)))) {
                $needCompilation = false;
            }
        }

        if (!file_exists($sourceFileName) && $needCompilation == true) {
            throw new BadParameterException($sourceFileName,'Template does not exist  "#(1)" ');
        }

        //xarLogVariable('needCompilation', $needCompilation, XARLOG_LEVEL_ERROR);
        if ($needCompilation) {
            $blCompiler = xarTpl__getCompilerInstance();
            $templateCode = $blCompiler->compileFile($sourceFileName);
            if (!isset($templateCode) ) {
                throw new EmptyParameterException($templateCode,'Template Code does not exist "#(1)" ');
            }
            if (self::$_cacheTemplates) {
                $fd = fopen($cachedFileName, 'w');
                fwrite($fd, $templateCode);
                fclose($fd);
                // Add an entry into CACHEKEYS
                self::__setCacheKey($sourceFileName);
            }
            return $templateCode;
        }

        // Load cached template file
        $output = implode('', file($cachedFileName));

        // Return output
        return $output;
    }

    /**
     * Set the cache key for a sourcefile
     *
     * @access private
     * @param  string $sourceFileName  For which file are we entering the key?
     * @return string the generated cachekey
     * @todo obviously we should prevent the dupes in the first place (flock()?, semaphores?, portable?)
     * @todo the price for the dupe check is that CACHEKEY is read into memory on each compile, we can optimize it by doing this once then
    **/
    private static function __setCacheKey($sourceFileName)
    {
        $cacheKey = self::__getCacheKey($sourceFileName);
        $filename = XAR_TPL_CACHE_DIR . '/CACHEKEYS';
        $eol = "\n";

        // Get the existing cache file lines, create if not existing
        // which is why we dont use file() here.
        if($fd = fopen($filename, 'a+')) {
            rewind($fd); // needed?
            while(!feof($fd)) {
                $lines[] = fgets($fd, 4096);
            }
            fclose($fd);
        }

        // If the cache key is already in the file, then no need to add it again.
        $line = $cacheKey . ': ' . $sourceFileName . $eol;
        if (in_array($line, $lines))
            return $cacheKey;

        // Add the line to the end of the file, then remove duplicates.
        $lines_count = count($lines);
        $lines = array_unique($lines);
        if (count($lines) == $lines_count) {
           // No duplicates were removed, so just tag this line onto the end of the file.
           $fd = fopen($filename, 'a');
           fwrite($fd, $line);
        } else {
           // Duplicate lines were removed, so write the whole file back.
           // @todo: we might consider logging this, as duplicates indicate a bug under normal operation
           $lines[] = $line;
           $fd = fopen($filename, 'w');
           fwrite($fd, implode('', $lines));
        }
        fflush($fd); // needed?
        fclose($fd);
        return $cacheKey;
    }

    /** Get the cache key for a sourcefile
     *
     * @access private
     * @param  string $sourceFileName  For which file do we need the key?
     * @return string                  The cache key for this sourcefilename
     *
     * @todo  consider using a static array
     */
    private static function __getCacheKey($sourceFileName)
    {
        return md5($sourceFileName);
    }
}

/**
 * Initializes the BlockLayout Template Engine
 *
 * @access protected
 * @param  array  $args                  Elements: themesBaseDir, defaultThemeName, enableTemplateCaching
 * @param  int    $whatElseIsGoingLoaded Bitfield to specify which subsystem will be loaded.
 * @return bool true
 */
function xarTpl_init($args, $whatElseIsGoingLoaded)
{
    return xarTpl::init($args, $whatElseIsGoingLoaded);
}

/**
 * Get theme name for the theme in use.
 *
 * @access public
 * @return string themename
 */
function xarTplGetThemeName()
{
    return xarTpl::getThemeName();
}

/**
 * Set theme name
 *
 * @access public

 * @param  string $themeName Themename to set
 * @return bool
 */
function xarTplSetThemeName($themeName)
{
    return xarTpl::setThemeName($themeName);
}

/**
 * Set theme dir
 *
 * @access public
 * @param  string themeDir
 * @return bool
 */
function xarTplSetThemeDir($themeDir=NULL)
{
    return xarTpl::setThemeDir($themeDir);
}

/**
 * Private helper function for the xarTplSetThemeName and xarTplSetThemeDir
 *
 * @access private
 * @param  string $name Name of the theme
 * @todo jojo- deprecate this after further testing now we have separate theme and dir names
 * @return void
 */
function xarTpl__SetThemeNameAndDir($name)
{
    // DEPRECATED
    throw new DeprecatedTemplateFunctionException(__FUNCTION__);
}

/**
 * Get theme directory
 *
 * @access public
 * @return sring  Theme directory
 */
function xarTplGetThemeDir()
{
    return xarTpl::getThemeDir();
}


/**
 * Get page template name
 *
 * @access public
 * @return string page template name
 */
function xarTplGetPageTemplateName()
{
    return xarTpl::getPageTemplateName();
}

/**
 * Set page template name
 *
 * @access public
 * @param  string $templateName Name of the page template
 * @return bool
 */
function xarTplSetPageTemplateName($templateName)
{
    return xarTpl::setPageTemplateName($templateName);
}

/**
 * Get doctype declared by page template
 *
 * @access public
 * @return string doctype identifier
 */
function xarTplGetDoctype()
{
    return xarTpl::getDoctype();
}

/**
 * Set doctype declared by page template
 *
 * @access public
 * @param  string $doctypeName Identifier string of the doctype
 * @return bool
 */
function xarTplSetDoctype($doctypeName)
{
    return xarTpl::setDoctype($doctypeName);
}

/**
 * Set page title
 *
 * @access public
 * @param  string $title
 * @param  string $module
 * @todo   this needs to be moved into the templating domain somehow
 * @return bool
 */
function xarTplSetPageTitle($title = NULL, $module = NULL)
{
    return xarTpl::setPageTitle($title, $module);
}

/**
 * Get page title
 *
 * @access public
 * @return string
 */
function xarTplGetPageTitle()
{
    return xarTpl::getPageTitle();
}

/**
 * Add JavaScript code or links to template output
 * This is a convenience function during deprecation period and should be removed at 1.5.0
 *
 * @access public
 * @param  string $position         Either 'head' or 'body'
 * @param  string $type             Either 'src' or 'code'
 * @param  string $data             pathname or raw JavaScript
 * @param  string $index            optional (unique key and/or ordering)
 * @return bool
 * @deprecated V 1.3.4 use the Themes module userapi-registerjs function
 */
function xarTplAddJavaScript($position, $type, $data, $index = '', $weight = 10, $aggregate= null, $comment='')
{
    if (empty($position) || empty($type) || empty($data)) {return;}
    if (!class_exists('xarJs')) sys::import('modules.themes.xarclass.xarjs');
    $js = new xarJs();
    return $js->addJs($position, $type, $data, $index, $weight, $aggregate, $comment);
}

/**
 * Get JavaScript code or links cached for template output
 * This is a convenience function during deprecation period and should be removed at 1.5.0
 *
 * @access public
 * @param  string $position
 * @param  string $index
 * @return array
 * @deprecated V 1.3.4 use the Themes module userapi-renderjs function
 */
function xarTplGetJavaScript($position = '', $index = '')
{
  if (!class_exists('xarJs')) sys::import('modules.themes.xarclass.xarjs');
    $js = new xarJs();
    return $js->getJs(array('position' => $position, 'index' => $index));

}

/**
 * Set user message
 *
 * @access public
 * @param string  $message The message displayed to the user
 * @param string  $type    The type of message either 'status', 'alert', 'error'
 * @param boolean $persist (optional) Do not clear the message if it is already in the queue
 */
function xarTplSetMessage($message = NULL, $type = 'status', $persist= TRUE)
{
    return xarTpl::setMessage($message,$type,$persist);
}

/**
 * Get user messages
 *
 * @access public
 * @param string  $type         The type of message either 'status', 'alert', 'error'
 * @param boolean $flush        (optional) Flush the messages
 * @param string $usetemplate  (optional) The return is templated using $template if provided
 * @return array  An associative array of messages of $type, or all message types of no $type given
 *                The array is returned templated, unless $usertemplate is FALSE
 */
function xarTplGetMessage($type = NULL, $template = 'user', $flush = TRUE )
{
    return xarTpl::getMessage($type, $template, $flush);
}

/**
 * Turns module output into a template.
 *
 * @access public
 * @param  string $modName      the module name
 * @param  string $modType      user|admin
 * @param  string $funcName     module function to template
 * @param  array  $tplData      arguments for the template
 * @param  string $templateName string the specific template to call
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    return xarTpl::module($modName, $modType, $funcName, $tplData, $templateName);
}

/**
 * Renders a block content through a block template.
 *
 * @access public
 * @param  string $modName   the module name
 * @param  string $blockType the block type (xar_block_types.xar_type)
 * @param  array  $tplData   arguments for the template
 * @param  string $tplName   the specific template to call
 * @param  string $tplBase   the base name of the template (defaults to $blockType)
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplBlock($modName, $blockType, $tplData = array(), $tplName = NULL, $tplBase = NULL)
{
    return xarTpl::block($modName, $blockType, $tplData, $tplName, $tplBase);
}
/**
 * Renders a DD element (object or property) through a template.
 *
 * @access private
 * @param  string $modName      the module name owning the object/property, with fall-back to dynamicdata
 * @param  string $ddName       the name of the object/property type, or some other name specified in BL tag or API call
 * @param  string $tplType      the template type to render
 *                              properties: ( showoutput(default)|showinput|showhidden|validation|label )
 *                              objects   : ( showdisplay(default)|showview|showform|showlist )
 * @param  array  $tplData      arguments for the template
 * @param  string $tplBase      the template type can be overridden too ( unused )
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTpl__DDElement($modName, $ddName, $tplType, $tplData, $tplBase,$elements)
{
    return xarTpl::ddElement($modName, $ddName, $tplType, $tplData, $tplBase, $elements);
}
function xarTplProperty($modName, $propertyName, $tplType = 'showoutput', $tplData = array(), $tplBase = NULL)
{
    return xarTpl::ddElement($modName, $propertyName, $tplType, $tplData, $tplBase, 'properties');
}
function xarTplObject($modName, $objectName, $tplType = 'showdisplay', $tplData = array(), $tplBase = NULL)
{
    return xarTpl::ddElement($modName, $objectName, $tplType, $tplData, $tplBase, 'objects');
}

/**
 * Get theme template image replacement for a module's image
 *
 * Example:
 * $my_module_image = xarTplGetImage('button1.png');
 * $other_module_image = xarTplGetImage('set1/info.png','modules');
 *
 * Correct practices:
 *
 * 1. module developers should never rely on theme's images, but instead
 * provide their own artwork inside modules/<module>/xarimages/ directory
 * and use this function to reference their images in the module's functions.
 * Such reference can then be safely passed to the module template.
 *
 * 2. theme developers should always check for the modules images
 * (at least for all core modules) and provide replacements images
 * inside the corresponding themes/<theme>/modules/<module>/images/
 * directories as necessary
 *
 * Note : your module is still responsible for taking care that "images"
 *        don't contain nasty stuff. Filter as appropriate when using
 *        this function to generate image URLs...
 *
 * @access  public
 * @param   string $modImage the module image url relative to xarimages/
 * @param   string $modName  the module to check for the image <optional>
 * @return  string $theme    image url if it exists or module image url if not, or NULL if neither found
 *
 * @todo    provide examples, improve description, add functionality
 * @todo    provide XML URL override flag
 * @todo    XML encode absolute URIs too?
*/
function xarTplGetImage($modImage, $modName = NULL)
{
    // return absolute URIs and URLs "as is"
    if (empty($modImage) || substr($modImage,0,1) == '/' || preg_match('/^https?\:\/\//',$modImage)) {
        return $modImage;
    }

    // obtain current module name if not specified
    // FIXME: make a fallback for weird requests
    if(!isset($modName)){
        list($modName) = xarRequest::getInfo();
    }

    // get module directory (could be different from module name)
    if(function_exists('xarMod_getBaseInfo')) {
        $modBaseInfo = xarMod::getBaseInfo($modName);
        if (!isset($modBaseInfo)) return; // throw back
        $modOsDir = $modBaseInfo['osdirectory'];
    } else {
        // Assume dir = modname
        $modOsDir = $modName;
    }

    // relative url to the current module's image
    $moduleImage = 'modules/'.$modOsDir.'/xarimages/'.$modImage;

    // obtain current theme directory
    $themedir = xarTpl::getThemeDir();

    // relative url to the replacement image in current theme
    $themeImage = $themedir . '/modules/'.$modOsDir.'/images/'.$modImage;

    $return = NULL;

    // check if replacement image exists in the theme
    if (file_exists($themeImage)) {
        // image found, return its path in the theme
        $return = $themeImage;
    } elseif (file_exists($moduleImage)) {
        // image found, return it's path in the module
        $return = $moduleImage;
    }

    // Return as an XML URL if required.
    // This will generally have little effect, but is here for
    // completeness to support alternative types of URL.
    if (isset($return) && xarTpl::generateXMLURLs()) {
        $return = htmlspecialchars($return);
    }

    return $return;
}

/**
 * Creates pager information with no assumptions to output format.
 *
 * @since 2003/10/09
 * @access public
 * @param integer $startNum     start item
 * @param integer $total        total number of items present
 * @param integer $itemsPerPage number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 *
 * @todo  Move this somewhere else, preferably transparent and a widget (which might be mutually exclusive)
 */
function xarTplPagerInfo($currentItem, $total, $itemsPerPage = 10, $blockOptions = 10)
{
    // Default block options.
    if (is_numeric($blockOptions)) {
        $pageBlockSize = $blockOptions;
    }

    if (is_array($blockOptions)) {
        if (!empty($blockOptions['blocksize'])) {$blockSize = $blockOptions['blocksize'];}
        if (!empty($blockOptions['firstitem'])) {$firstItem = $blockOptions['firstitem'];}
        if (!empty($blockOptions['firstpage'])) {$firstPage = $blockOptions['firstpage'];}
        if (!empty($blockOptions['urltemplate'])) {$urltemplate = $blockOptions['urltemplate'];}
        if (!empty($blockOptions['urlitemmatch'])) {
            $urlItemMatch = $blockOptions['urlitemmatch'];
        } else {
            $urlItemMatch = '%%';
        }
        $urlItemMatchEnc = rawurlencode($urlItemMatch);
    }

    // Default values.
    if (empty($blockSize) || $blockSize < 1) {$blockSize = 10;}
    if (empty($firstItem)) {$firstItem = 1;}
    if (empty($firstPage)) {$firstPage = 1;}

    // The last item may be offset if the first item is not 1.
    $lastItem = ($total + $firstItem - 1);

    // Sanity check on arguments.
    if ($itemsPerPage < 1) {$itemsPerPage = 10;}
    if ($currentItem < $firstItem) {$currentItem = $firstItem;}
    if ($currentItem > $lastItem) {$currentItem = $lastItem;}

    // If this request was the same as the last one, then return the cached pager details.
    // TODO: is there a better way of caching for each unique request?
    $request = md5($currentItem . ':' . $lastItem . ':' . $itemsPerPage . ':' . serialize($blockOptions));
    if (xarCoreCache::getCached('Pager.core', 'request') == $request) {
        return xarCoreCache::getCached('Pager.core', 'details');
    }

    // Record the values in this request.
    xarCoreCache::setCached('Pager.core', 'request', $request);

    // Max number of items in a block of pages.
    $itemsPerBlock = ($blockSize * $itemsPerPage);

    // Get the start and end items of the page block containing the current item.
    $blockFirstItem = $currentItem - (($currentItem - $firstItem) % $itemsPerBlock);
    $blockLastItem = $blockFirstItem + $itemsPerBlock - 1;
    if ($blockLastItem > $lastItem) {$blockLastItem = $lastItem;}

    // Current/Last page numbers.
    $currentPage = (int)ceil(($currentItem-$firstItem+1) / $itemsPerPage) + $firstPage - 1;
    $totalPages = (int)ceil($total / $itemsPerPage);

    // First/Current/Last block numbers
    $firstBlock = 1;
    $currentBlock = (int)ceil(($currentItem-$firstItem+1) / $itemsPerBlock);
    $totalBlocks = (int)ceil($total / $itemsPerBlock);

    // Get start and end items of the current page.
    $pageFirstItem = $currentItem - (($currentItem-$firstItem) % $itemsPerPage);
    $pageLastItem = $pageFirstItem + $itemsPerPage - 1;
    if ($pageLastItem > $lastItem) {$pageLastItem = $lastItem;}

    // Initialise data array.
    $data = array();

    $data['middleitems'] = array();
    $data['middleurls'] = array();
    $pageNum = (int)ceil(($blockFirstItem - $firstItem + 1) / $itemsPerPage) + $firstPage - 1;
    for ($i = $blockFirstItem; $i <= $blockLastItem; $i += $itemsPerPage) {
        if (!empty($urltemplate)) {
            $data['middleurls'][$pageNum] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $i, $urltemplate);
        }
        $data['middleitems'][$pageNum] = $i;
        $data['middleitemsfrom'][$pageNum] = $i;
        $data['middleitemsto'][$pageNum] = $i + $itemsPerPage - 1;
        if ($data['middleitemsto'][$pageNum] > $total) {$data['middleitemsto'][$pageNum] = $total;}
        $pageNum += 1;
    }

    $data['currentitem'] = $currentItem;
    $data['totalitems'] = $total;
    $data['lastitem'] = $lastItem;
    $data['firstitem'] = $firstItem;
    $data['itemsperpage'] = $itemsPerPage;
    $data['itemsperblock'] = $itemsPerBlock;
    $data['pagesperblock'] = $blockSize;

    $data['currentblock'] = $currentBlock;
    $data['totalblocks'] = $totalBlocks;
    $data['firstblock'] = $firstBlock;
    $data['lastblock'] = $totalBlocks;
    $data['blockfirstitem'] = $blockFirstItem;
    $data['blocklastitem'] = $blockLastItem;

    $data['currentpage'] = $currentPage;
    $data['currentpagenum'] = $currentPage;
    $data['totalpages'] = $totalPages;
    $data['pagefirstitem'] = $pageFirstItem;
    $data['pagelastitem'] = $pageLastItem;

    // These two are item numbers. The naming is historical.
    $data['firstpage'] = $firstItem;
    $data['lastpage'] = $lastItem - (($lastItem-$firstItem) % $itemsPerPage);

    if (!empty($urltemplate)) {
        // These two links are for first and last pages.
        $data['firsturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['firstpage'], $urltemplate);
        $data['lasturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['lastpage'], $urltemplate);
    }

    $data['firstpagenum'] = $firstPage;
    $data['lastpagenum'] = ($totalPages + $firstPage - 1);

    // Data for previous page of items.
    if ($currentPage > $firstPage) {
        $data['prevpageitems'] = $itemsPerPage;
        $data['prevpage'] = ($pageFirstItem - $itemsPerPage);
        if (!empty($urltemplate)) {
            $data['prevpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevpage'], $urltemplate);
        }
    } else {
        $data['prevpageitems'] = 0;
    }

    // Data for next page of items.
    if ($pageLastItem < $lastItem) {
        $nextPageLastItem = ($pageLastItem + $itemsPerPage);
        if ($nextPageLastItem > $lastItem) {$nextPageLastItem = $lastItem;}
        $data['nextpageitems'] = ($nextPageLastItem - $pageLastItem);
        $data['nextpage'] = ($pageLastItem + 1);
        if (!empty($urltemplate)) {
            $data['nextpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextpage'], $urltemplate);
        }
    } else {
        $data['nextpageitems'] = 0;
    }

    // Data for previous block of pages.
    if ($currentBlock > $firstBlock) {
        $data['prevblockpages'] = $blockSize;
        $data['prevblock'] = ($blockFirstItem - $itemsPerBlock);
        if (!empty($urltemplate)) {
            $data['prevblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevblock'], $urltemplate);
        }
    } else {
        $data['prevblockpages'] = 0;
    }

    // Data for next block of pages.
    if ($currentBlock < $totalBlocks) {
        $nextBlockLastItem = ($blockLastItem + $itemsPerBlock);
        if ($nextBlockLastItem > $lastItem) {$nextBlockLastItem = $lastItem;}
        $data['nextblockpages'] = ceil(($nextBlockLastItem - $blockLastItem) / $itemsPerPage);
        $data['nextblock'] = ($blockLastItem + 1);
        if (!empty($urltemplate)) {
            $data['nextblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextblock'], $urltemplate);
        }
    } else {
        $data['nextblockpages'] = 0;
    }

    // Cache all the pager details.
    xarCoreCache::setCached('Pager.core', 'details', $data);

    return $data;
}

/**
 * Equivalent of pnHTML()'s Pager function (to get rid of pnHTML calls in modules while waiting for widgets)
 *
 * @since 1.13 - 2003/10/09
 * @access public
 * @param integer $startnum     start item
 * @param integer $total        total number of items present
 * @param string  $urltemplate  template for url, will replace '%%' with item number
 * @param integer $perpage      number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 * @param integer $template     alternative template name within base/user (default 'pager')
 *
 * @todo Move this somewhere else
 */
function xarTplGetPager($startNum, $total, $urltemplate, $itemsPerPage = 10, $blockOptions = array(), $template = 'default')
{
    // Quick check to ensure that we have work to do
    if ($total <= $itemsPerPage) {return '';}

    // Number of pages in a page block - support older numeric 'pages per block'.
    if (is_numeric($blockOptions)) {
        $blockOptions = array('blocksize' => $blockOptions);
    }

    // Pass the url template into the pager calculator.
    $blockOptions['urltemplate'] = $urltemplate;

    // Get the pager information.
    $data = xarTplPagerInfo($startNum, $total, $itemsPerPage, $blockOptions);

    // Nothing to do: perhaps there is an error in the parameters?
    if (empty($data)) {return '';}

    // Couple of cached values used in various pages.
    // It is unclear what these values are supposed to be used for.
    if ($data['prevblockpages'] > 0) {
        xarCoreCache::setCached('Pager.first', 'leftarrow', $data['firsturl']);
    }

    // Links for next block of pages.
    if ($data['nextblockpages'] > 0) {
        xarCoreCache::setCached('Pager.last', 'rightarrow', $data['lasturl']);
    }

    return trim(xarTplModule('base', 'pager', $template, $data));
}

/**
 * Execute a pre-compiled template string with the supplied template variables
 *
 * @access public
 * @param  string $templateCode pre-compiled template code (see xarTplCompileString)
 * @param  array  $tplData      template variables
 * @return string filled-in template
 */
function xarTplString($templateCode, $tplData)
{
    return xarTpl::string($templateCode, $tplData);
}

/**
 * Execute a specific template file with the supplied template variables
 *
 * @access public
 * @param  string $fileName location of the template file
 * @param  array  $tplData  template variables
 * @return string filled-in template
 */
function xarTplFile($fileName, $tplData)
{
    return xarTpl::file($fileName, $tplData);
}

/**
 * Compile a template string for storage and/or later use in xarTplString()
 * Note : your module should always support the possibility of re-compiling
 *        template strings e.g. after an upgrade, so you should store both
 *        the original template and the compiled version if necessary
 *
 * @access public
 * @param  string $templateSource template source
 * @return string compiled template
 */
function xarTplCompileString($templateSource)
{
    $blCompiler = xarTpl__getCompilerInstance();
    return $blCompiler->compileString($templateSource);
}

/**
 * Renders a page template.
 *
 * @access protected
 * @param  string $mainModuleOutput       the module output
 * @param  string $otherModulesOutput
 * @param  string $templateName           the template page to use without extension .xt
 * @return string
 *
 * @todo Needs a rewrite, i.e. finalisation of tplOrder scenario
 */
function xarTpl_renderPage($mainModuleOutput, $otherModulesOutput = NULL, $templateName = NULL)
{
    return xarTpl::renderPage($mainModuleOutput, $otherModulesOutput, $templateName);
}

/**
 * Render a block box
 *
 * @access protected
 * @param  array  $blockInfo  Information on the block
 * @param  string $templateName string
 * @return bool xarTpl__executeFromFile($sourceFileName, $blockInfo)
 *
 * @todo the search logic for the templates can perhaps use the private function?
 * @todo fallback to some internal block box template?
 */
function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    return xarTpl::renderBlockBox($blockInfo, $templateName);
}

/**
 * Include a subtemplate from the theme space
 *
 * @access protected
 * @param  string $templateName Basically handler function for <xar:template type="theme".../>
 * @param  array  $tplData      template variables
 * @return string
 */
function xarTpl_includeThemeTemplate($templateName, $tplData)
{
    return xarTpl::includeThemeTemplate($templateName, $tplData);
}

/**
 * Include a subtemplate from the module space
 *
 * @access protected
 * @param  string $modName      name of the module from which to include the template
 * @param  string $templateName Basically handler function for <xar:template type="module".../>
 * @param  array  $tplData      template variables
 * @return string
 */
function xarTpl_includeModuleTemplate($modName, $templateName, $tplData)
{
    return xarTpl::includeModuleTemplate($modName, $templateName, $tplData);
}

// PRIVATE FUNCTIONS

/**
 * Get BL compiler instance
 *
 * @access private
 * @return object xarTpl__Compiler()
 */
function xarTpl__getCompilerInstance()
{

    sys::import('xarigami.xarBLCompiler');

    //$newcompiler = new xarTpl__Compiler();
    $newcompiler = xarTpl__Compiler::instance();
    return $newcompiler;
}

/**
 * Execute Template, i.e. run the compiled php code of a cached template
 *
 * @access private
 * @param  string $templateCode   Templatecode to execute
 * @param  array  $tplData        Template variables
 * @param  string $sourceFileName
 * @return string output
 *
 * @todo Can we migrate the eval() out, as that is hard to cache?
 * @todo $sourceFileName looks wrong here
 */
function xarTpl__execute($templateCode, $tplData, $sourceFileName = '', $cachedFileName = null)
{
    throw new DeprecatedTemplateFunctionException(__FUNCTION__);
    // should we set the method in xarTemplates public to call it statically?
}

/**
 * Execute template from file
 *
 * @access private
 * @param  string $sourceFileName       From which file do we want to execute?
 * @param  array  $tplData              Template variables
 * @return mixed
 *
 * @todo  inserting the header part like this is not output agnostic
 * @todo  insert log warning when double entry in cachekeys occurs? (race condition)
 * @todo  make the checking whethet templatecode is set more robst (related to templated exception handling)
 */
function xarTpl__executeFromFile($sourceFileName, $tplData)
{
    throw new DeprecatedTemplateFunctionException(__FUNCTION__);
}

/**
 * Determine the template sourcefile to use
 *
 * Based on the module, the basename for the template
 * a possible overribe and a subpart and the active
 * theme, determine the template source we should use and loads
 * the appropriate translations based on the outcome.
 *
 * @param  string $modName      Module name doing the request
 * @param  string $tplBase      The base name for the template
 * @param  string $templateName The name for the template to use if any
 * @param  string $tplSubPart   A subpart ('' or 'blocks' or 'properties')
 * @return string
 *
 * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction)
 */
function xarTpl__getSourceFileName($modName,$tplBase, $templateName = NULL, $tplSubPart = '')
{
    throw new DeprecatedTemplateFunctionException(__FUNCTION__);
}


/**
 * Output template
 *
 * @access private
 * @param  string $sourceFileName
 * @param  string $tplOutput
 * @return void
 *
 * @todo Rethink this function, it contains hardcoded xhtml
 */
function xarTpl_outputTemplate($sourceFileName, &$tplOutput)
{
    return xarTpl::outputTemplate($sourceFileName, $tplOutput);
}

/**
 * Output php comment block in templates
 *
 * @access private
 * @return int value of xarTpl_showPHPCommentBlockInTemplates (0 or 1)
 */
function xarTpl_outputPHPCommentBlockInTemplates()
{
    return xarTpl::outputPHPCommentInTemplates();
}

/**
 * Output template filenames
 *
 * @access private
 * @return int value of xarTpl_showTemplateFilenames (0 or 1)
 *
 * @todo Check whether the check for xarModGetVar is needed
 * @todo Rethink this function
 */
function xarTpl_outputTemplateFilenames()
{
    return xarTpl::outputTemplateFilenames();
}

/**
 * Modify header content
 *
 * Attempt to determine if $tplOutput contains header content and if
 * so append a start comment after the first matched header tag
 * found.
 *
 * @access private
 * @param  string $sourceFileName
 * @param  string $tplOutput
 * @return bool found header content
 *
 * @todo it is possible that the first regex <!DOCTYPE[^>].*]> is too
 *       greedy in more complex xml documents and others.
 * @todo The doctype of the output belongs in a template somewhere (probably the xar:blocklayout tag, as an attribute
 */
function xarTpl_modifyHeaderContent($sourceFileName, &$tplOutput)
{
    return xarTpl::modifyHeaderContent($sourceFileName, $tplOutput);
}

/**
 * Load template from file (e.g. for use with recurring template snippets someday,
 * using xarTplString() to "fill in" the template afterwards)
 *
 * @access private
 * @param  string $sourceFileName     From which file do we want to load?
 * @return mixed
 */
function xarTpl__loadFromFile($sourceFileName)
{
    throw new DeprecatedTemplateFunctionException(__FUNCTION__);
}

/**
 * Set the cache key for a sourcefile
 *
 * @access private
 * @param  string $sourceFileName  For which file are we entering the key?
 * @return string the generated cachekey
 * @todo obviously we should prevent the dupes in the first place (flock()?, semaphores?, portable?)
 * @todo the price for the dupe check is that CACHEKEY is read into memory on each compile, we can optimize it by doing this once then
**/
function xarTpl__SetCacheKey($sourceFileName)
{
    throw new DeprecatedTemplateFunctionException(__FUNCTION__);
}

/** Get the cache key for a sourcefile
 *
 * @access private
 * @param  string $sourceFileName  For which file do we need the key?
 * @return string                  The cache key for this sourcefilename
 *
 * @todo  consider using a static array
 */
function xarTpl__getCacheKey($sourceFileName)
{
    throw new DeprecatedTemplateFunctionException(__FUNCTION__);
}

/**
 * Model of a tag attribute
 *
 * Mainly used for custom tags
 *
 * @package blocklayout
 * @access protected
 *
 * @todo see FIXME
 */
class xarTemplateAttribute
{
    public $_name;     // Attribute name
    public $_flags;    // Attribute flags (datatype, required/optional, etc.)

    function __construct($name, $flags = NULL)
    {
        // See defines at top of file
        //xgami-000432
        if (!preg_match(XAR_TPL_ATTRIBUTE_REGEX, $name)) {
            $msg = xarML("Illegal attribute name ('#(1)'): Attribute name may contain letters, numbers, _ and -, and must start with a letter.", $name);
            throw new BadParameterException($msg);
        }

        if (!is_integer($flags) && $flags != NULL) {
            $msg = xarML("Illegal attribute flags ('#(1)'): flags must be of integer type.", $flags);
             throw new BadParameterException($msg);
        }

        $this->_name  = $name;
        $this->_flags = $flags;

        // FIXME: <marco> Why do you need both XAR_TPL_REQUIRED and XAR_TPL_OPTIONAL when XAR_TPL_REQUIRED = ~XAR_TPL_OPTIONAL?
        if ($this->_flags == NULL) {
            $this->_flags = XAR_TPL_ANY|XAR_TPL_REQUIRED;
        } elseif ($this->_flags == XAR_TPL_OPTIONAL) {
            $this->_flags = XAR_TPL_ANY|XAR_TPL_OPTIONAL;
        }
    }

    function getFlags()
    {
        return $this->_flags;
    }

    function getAllowedTypes()
    {
        return ($this->getFlags() & (~ XAR_TPL_OPTIONAL));
    }

    function getName()
    {
        return $this->_name;
    }

    function isRequired()
    {
        return !$this->isOptional();
    }

    function isOptional()
    {
        if ($this->_flags & XAR_TPL_OPTIONAL) {
            return true;
        }
        return false;
    }
}

/**
 * Model of a template tag
 *
 * Only used for custom tags atm
 * @package blocklayout
 * @access  protected
 *
 * @todo Make this more general
 * @todo _module, _type and _func and _handler introduce unneeded redundancy
 * @todo pass handler check at template registration someday (<mrb>what does this mean?)
 */
class xarTemplateTag
{
    //jojo - quick update for php 5.3 - need to review variable scope and adjust as necessary
    public $_name = NULL;          // Name of the tag
    public $_attributes = array(); // Array with the supported attributes
    public $_handler = NULL;       // Name of the handler function
    public $_module;               // Modulename
    public $_type;                 // Type of the handler (user/admin etc.)
    public $_func;                 // Function name
    // properties for registering what kind of tag we have here
    public  $_hasChildren = false;
    public  $_hasText = false;
    public  $_isAssignable = false;
    public  $_isPHPCode = true;
    public  $_needAssignment = false;
    public  $_needParameter = false;
    public  $_needExceptionsControl = false;


    function __construct($module, $name, $attributes = array(), $handler = NULL, $flags = XAR_TPL_TAG_ISPHPCODE)
    {
        // See defines at top of file
        if (!preg_match(XAR_TPL_TAGNAME_REGEX, $name)) {
             throw new BadParameterException($name,'Illegal tag definition: "#(1)" is an invalid tag name.');
        }

        if (preg_match("/($module)_(\w+)api_(.*)/",$handler,$matches)) {
            $this->_type = $matches[2];
            $this->_func = $matches[3];
        } else {
            throw new BadParameterException($handler,'Illegal tag definition: "#(1)" is an invalid handler.');
        }

        if (!is_integer($flags)) {
            throw new BadParameterException($flags,'Illegal tag registration flags ("#(1)"): flags must be of integer type.');
        }

        // Everything seems to be in order, set the properties
        $this->_name = $name;
        $this->_handler = $handler;
        $this->_module = $module;

        if (is_array($attributes)) {
            $this->_attributes = $attributes;
        }
        $this->_setflags($flags);
    }

    function _setflags($flags)
    {
        $this->_hasChildren    = ($flags & XAR_TPL_TAG_HASCHILDREN)    == XAR_TPL_TAG_HASCHILDREN;
        $this->_hasText        = ($flags & XAR_TPL_TAG_HASTEXT)        == XAR_TPL_TAG_HASTEXT;
        $this->_isAssignable   = ($flags & XAR_TPL_TAG_ISASSIGNABLE)   == XAR_TPL_TAG_ISASSIGNABLE;
        $this->_isPHPCode      = ($flags & XAR_TPL_TAG_ISPHPCODE)      == XAR_TPL_TAG_ISPHPCODE;
        $this->_needAssignment = ($flags & XAR_TPL_TAG_NEEDASSIGNMENT) == XAR_TPL_TAG_NEEDASSIGNMENT;
        $this->_needParameter  = ($flags & XAR_TPL_TAG_NEEDPARAMETER)  == XAR_TPL_TAG_NEEDPARAMETER;
        $this->_needExceptionsControl = ($flags & XAR_TPL_TAG_NEEDEXCEPTIONSCONTROL)   == XAR_TPL_TAG_NEEDEXCEPTIONSCONTROL;
    }

    function hasChildren()
    {
        return $this->_hasChildren;
    }

    function hasText()
    {
        return $this->_hasText;
    }

    function isAssignable()
    {
        return $this->_isAssignable;
    }

    function isPHPCode()
    {
        return $this->_isPHPCode;
    }

    function needAssignement()
    {
        return $this->_needAssignment;
    }

    function needParameter()
    {
        return $this->_needParameter;
    }

    function needExceptionsControl()
    {
        return $this->_needExceptionsControl;
    }

    function getAttributes()
    {
        return $this->_attributes;
    }

    function getName()
    {
        return $this->_name;
    }

    function getModule()
    {
    return $this->_module;
    }

    function getHandler()
    {
    return $this->_handler;
    }

    function callHandler($args, $handler_type='render')
    {
        // FIXME: get rid of this once installation includes the right serialized info
        if (empty($this->_type) || empty($this->_func)) {
            $handler = $this->_handler;
            $module = $this->_module;
            if (preg_match("/($module)_(\w+)api_(.*)/",$handler,$matches)) {
                $this->_type = $matches[2];
                $this->_func = $matches[3];
            } else {
                throw new BadParameterException($handler,'Illegal tag definition: "#(1)" is an invalid handler.');
                // FIXME: why is this needed?
                $this->_name = NULL;
                return;
            }
        }
        // Add the type to the args
        $args['handler_type'] = $handler_type;
        $code = xarMod::apiFunc($this->_module, $this->_type, $this->_func, $args);
        assert('is_string($code); /* A custom tag should return a string with the code to put into the compiled template */');
        // Make sure the code has UNIX line endings too
        $code = str_replace(array("\r\n","\r"),"\n",$code);
        return $code;
    }
}

/**
 * Registers a tag to the theme system
 *
 * @access public
 * @param string  $tag_module  parent module of tag to register
 * @param string  $tag_name    tag to register with the system
 * @param array   $tag_attrs   array of attributes associated with tag (xarTemplateAttribute objects)
 * @param string  $tag_handler Which function is the handler?
 * @param integer $flags       Bitfield which contains the flags to turn on for the tag registration.
 * @return bool
 *
 * @todo Make this more generic, now only 'childless' tags are supported (only one handler)
 * @todo Consider using handler-array (define 'events' like in SAX)
 * @todo wrap the registration into constructor, either it succeeds creating the object or not, not having an object without succeeding sql.
 **/
function xarTplRegisterTag($tag_module, $tag_name, $tag_attrs = array(), $tag_handler = NULL, $flags = XAR_TPL_TAG_ISPHPCODE)
{
    // Check to make sure tag does not exist first
    if (xarTplGetTagObjectFromName($tag_name) != NULL) {
        // Already registered
        $msg = xarML('<xar:#(1)> tag is already defined.', $tag_name);
        throw new DuplicateTagException($msg);
    }

    // Validity of tagname is checked in class.
    $tag = new xarTemplateTag($tag_module, $tag_name, $tag_attrs, $tag_handler, $flags);
    if(!$tag->getName()) return; // tagname was not set, exception pending
    try {
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;

        $systemPrefix = xarDB::$sysprefix;
        $tag_table = $systemPrefix . '_template_tags';

        // Get next ID in table
        $tag_id = $dbconn->GenId($tag_table);

        $query = "INSERT INTO $tag_table
                    (xar_id, xar_name, xar_module, xar_handler, xar_data)
                  VALUES
                    (?,?,?,?,?)";

        $bindvars = array($tag_id,
                          $tag->getName(),
                          $tag->getModule(),
                          $tag->getHandler(),
                          serialize($tag));

        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;
    } catch (Exception $e) {
        throw $e;
    }
    return true;
}

/**
 * Unregisters a tag to the theme system
 *
 * @access public
 * @param  string $tag      tag to remove
 * @return bool
 * @todo   wrap in unregister method of tag class? (kinda compicates things, as now no object is needed)
 **/
function xarTplUnregisterTag($tag_name)
{
    if (!preg_match(XAR_TPL_TAGNAME_REGEX, $tag_name)) {
        // throw exception
        return false;
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $tag_table = $xartable['template_tags'];

    $query = "DELETE FROM $tag_table WHERE xar_name = ?";

    $result = $dbconn->Execute($query,array($tag_name));
    if (!$result) return;

    return true;
}


/**
 * Check the attributes of a tag
 *
 * @access  protected
 * @param   string    $name Name of the tag
 * @param   array     $args Attribute array
 * @return  bool
 *
 * @todo Rename the function to reflect that it is a protected function
 * @todo wrap in method of tag or attribute class (or both)
*/
function xarTplCheckTagAttributes($name, $args)
{
    $tag_ref = xarTplGetTagObjectFromName($name);
    if ($tag_ref == NULL) {
        $msg = xarML('<xar:#(1)> tag is not defined.', $name);
        throw new BadParameterException($msg);
        return;
    }

    $tag_attrs = $tag_ref->getAttributes();

    foreach ($tag_attrs as $attr) {
        $attr_name = $attr->getName();
        if (isset($args[$attr_name])) {
            // check that type matches
            $attr_types = $attr->getAllowedTypes();

            if ($attr_types & XAR_TPL_STRING) {
                continue;
            } elseif (($attr_types & XAR_TPL_BOOLEAN)
                      && preg_match ('/^(true|false|1|0)$/', $args[$attr_name])) {
                continue;
            } elseif (($attr_types & XAR_TPL_INTEGER)
                      && preg_match('/^\-?[0-9]+$/', $args[$attr_name])) {
                continue;
            } elseif (($attr_types & XAR_TPL_FLOAT)
                      && preg_match('/^\-?[0-9]*.[0-9]+$/', $args[$attr_name])) {
                continue;
            }

            // bad type for attribute
            $msg = xarML("'#(1)' attribute in <xar:#(2)> tag does not have correct type. See tag documentation.", $attr_name, $name);
            throw new BadParameterException($msg);

        } elseif ($attr->isRequired()) {
            // required attribute is missing!
            $msg = xarML("Required '#(1)' attribute is missing from <xar:#(2)> tag. See tag documentation.", $attr_name, $name);
            throw new BadParameterException($msg);
        }
    }

    return true;
}

/**
 * Get the object belonging to the tag
 *
 * @access protected
 * @param  string $tag_name
 *
 * @return mixed  The object
 *
 */
function xarTplGetTagObjectFromName($tag_name)
{
    // cache tags for compile performance
    static $tag_objects = array();
    if (isset($tag_objects[$tag_name])) {
        return $tag_objects[$tag_name];
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $systemPrefix = xarDB::$sysprefix;
    $tag_table = $systemPrefix . '_template_tags';
    $query = "SELECT xar_data, xar_module FROM $tag_table WHERE xar_name=?";

    $result = $dbconn->SelectLimit($query, 1,-1,array($tag_name));
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        return NULL; // tag does not exist
    }

    list($obj,$module) = $result->fields;
    $result->Close();

    // Module must be active for the tag to be active
    if(!xarMod::isAvailable($module)) return; //throw back

    $obj = unserialize($obj);

    $tag_objects[$tag_name] = $obj;

    return $obj;
}
?>