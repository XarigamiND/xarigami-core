<?php
/**
 * Multi Language System
 *
 * @package Xarigami core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
 * @subpackage multilanguage
 *       Timezone and DST support (default offset is supported now)
 *       Write standard core translations
 *       Complete changes as described in version 0.9 of MLS RFC
 *       Implements the request(ed) locale APIs for backend interactions
 *       See how utf-8 works for xml backend
 */

/**
 * Multilange package defines
 */
define('XARMLS_SINGLE_LANGUAGE_MODE', 'SINGLE');
define('XARMLS_BOXED_MULTI_LANGUAGE_MODE', 'BOXED');
define('XARMLS_UNBOXED_MULTI_LANGUAGE_MODE', 'UNBOXED');


define('XARMLS_DNTYPE_CORE', 1);
define('XARMLS_DNTYPE_THEME', 2);
define('XARMLS_DNTYPE_MODULE', 3);

sys::import('xarigami.xarLocale');
sys::import('xarigami.transforms.xarCharset');

/**
 * Initializes the Multi Language System
 *
 * @access protected
 * @throws Exception
 * @return bool true
 */
function xarMLS_init($args, $whatElseIsGoingLoaded)
{
    $args['MLSEnabled'] = isset($args['MLSEnabled'])?$args['MLSEnabled']:false;

    switch ($args['MLSMode']) {
    case XARMLS_SINGLE_LANGUAGE_MODE:
    case XARMLS_BOXED_MULTI_LANGUAGE_MODE:
        $GLOBALS['xarMLS_mode'] = $args['MLSMode'];
        break;
    case XARMLS_UNBOXED_MULTI_LANGUAGE_MODE:
        $GLOBALS['xarMLS_mode'] = $args['MLSMode'];
        if (!function_exists('mb_http_input')) {
            // mbstring required
            throw new Exception('xarMLS_init: Mbstring PHP extension is required for UNBOXED MULTI language mode.');
        }
        break;
    default:
        $GLOBALS['xarMLS_mode'] = 'BOXED';
        //throw new Exception('xarMLS_init: Unknown MLS mode: '.$args['MLSMode']);
    }
    $GLOBALS['xarMLS_backendName'] = $args['translationsBackend'];

    // USERLOCALE FIXME Delete after new backend testing
    $GLOBALS['xarMLS_localeDataLoader'] = new xarMLS__LocaleDataLoader();
    $GLOBALS['xarMLS_localeDataCache']  = array();

    $GLOBALS['xarMLS_currentLocale']    = '';
    $GLOBALS['xarMLS_defaultLocale']    = $args['defaultLocale'];
    $GLOBALS['xarMLS_allowedLocales']   = $args['allowedLocales'];

    $GLOBALS['xarMLS_newEncoding']      = new xarCharset;
    $GLOBALS['xarMLS_enabled']          = $args['MLSEnabled'];
    $GLOBALS['xarMLS_defaultTimeZone']  = isset($args['defaultTimeZone']) ?
                                         $args['defaultTimeZone'] : @date_default_timezone_get();
    $GLOBALS['xarMLS_defaultTimeOffset']= isset($args['defaultTimeOffset']) ?
                                           $args['defaultTimeOffset'] : 0;

    // Set the timezone
    date_default_timezone_set ($GLOBALS['xarMLS_defaultTimeZone']);

    // Register MLS events
    // These should be done before the xarMLS_setCurrentLocale function
    xarEvents::register('MLSMissingTranslationString');
    xarEvents::register('MLSMissingTranslationKey');
    xarEvents::register('MLSMissingTranslationDomain');

    //do we need this conditionally loading the setCurrentLocale?
    if (!($whatElseIsGoingLoaded & XARCORE_SYSTEM_USER)) {
        // The User System won't be started
        // MLS will use the default locale
        xarMLS_setCurrentLocale($args['defaultLocale']);
    }

    return true;
}

/**
 * Gets the current MLS mode
 *
 * @access public
 * @return integer MLS Mode
 */
function xarMLSGetMode()
{
    if (isset($GLOBALS['xarMLS_mode'])){
        return $GLOBALS['xarMLS_mode'];
    } else {
        return 'BOXED';
    }
}

/**
 * Returns the site locale if running in SINGLE mode,
 * returns the site default locale if running in BOXED or UNBOXED mode
 *
 * @access public
 * @return string the site locale
 * @todo   check
 */
function xarMLSGetSiteLocale()
{
    return $GLOBALS['xarMLS_defaultLocale'];
}

/**
 * Returns an array of locales available in the site
 *
 * @access public
 * @return array of locales
 * @todo   check
 */
function xarMLSListSiteLocales()
{
    $mode = xarMLSGetMode();
    if ($mode == XARMLS_SINGLE_LANGUAGE_MODE) {
        return array($GLOBALS['xarMLS_defaultLocale']);
    } else {
        return $GLOBALS['xarMLS_allowedLocales'];
    }
}

/**
 * Gets the current locale
 *
 * @access public
 * @return string current locale
 */
function xarMLSGetCurrentLocale()
{
    return $GLOBALS['xarMLS_currentLocale'];
}

/**
 * Gets the charset component from a locale
 *
 * @access public
 * @return string the charset name
 * @throws BAD_PARAM
 */
function xarMLSGetCharsetFromLocale($locale='')
{
    //use current locale if none passed in
    if (empty($locale)) $locale = $GLOBALS['xarMLS_currentLocale'];
    if (!$parsedLocale = xarMLS__parseLocaleString($locale)) return; // throw back
    return $parsedLocale['charset'];
}
/**
 * Gets the lang component from a locale
 *
 * @access public
 * @return string the charset name
 * @throws BAD_PARAM
 */
function xarMLSGetLanguageFromLocale($locale='')
{
    //use current locale if none passed in
    if (empty($locale)) $locale = $GLOBALS['xarMLS_currentLocale'];
    if (!$parsedLanguage = xarMLS__parseLocaleString($locale)) return; // throw back
    $lang = $parsedLanguage['lang'];
    $country = $parsedLanguage['country'];
    if (!empty($country)) {
        $lang .='-'.$country;
    }
    return $lang;
}
// I18N API

/**
 * Translates a string
 *
 * @access public
 * @return string the translated string, or the original string if no translation is available
 */
function xarML($string/*, ...*/)
{
    // if an empty string is passed in, just return an empty string. it's
    // the most sensible thing to do
    $string = trim($string);
    if(empty($string)) return '';

    // Make sure string is sane
    // - hex 0D -> ''
    // - space around newline -> ' '
    // - multiple newlines -> 1 newline
    $string = preg_replace(array('[\x0d]','/[\t ]+/','/\s*\n\s*/'),
                           array('',' ',"\n"),$string);

    if (isset($GLOBALS['xarMLS_backend'])) {
        $trans = $GLOBALS['xarMLS_backend']->translate($string,1);
    } else {
        // This happen in rare cases when xarML is called before xarMLS_init has been called
        $trans = $string;
    }

    if (empty($trans)) {
        // FIXME: postpone
        //xarEvt_fire('MLSMissingTranslationString', $string);
        $trans = $string;
    }
    if (func_num_args() > 1) {
        $args = func_get_args();

       // if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
       // else array_shift($args); // Drop $string argument
        if (is_array($args[1])) $args[1] = current($args[1]); // Jo - changed ...
         array_shift($args); // Drop $string argument

        $trans = xarMLS__bindVariables($trans, $args);
    }

    return $trans;
}

/**
 * Return the translation associated to passed key
 *
 * @access public
 * @throws BadParameterException
 * @return string the translation string, or the key if no translation is available
 */
function xarMLByKey($key/*, ...*/)
{
    // Key must have a value and not contain spaces
    if(empty($key) || strpos($key," ")) throw new BadParameterException('key');

    if (isset($GLOBALS['xarMLS_backend'])) {
        $trans = $GLOBALS['xarMLS_backend']->translateByKey($key);
    } else {
        // This happen in rare cases when xarMLByKey is called before xarMLS_init has been called
        $trans = $key;
    }
    if (empty($trans)) {
        // FIXME: postpone
        //xarEvt_fire('MLSMissingTranslationKey', $key);
        $trans = $key;
    }
    if (func_num_args() > 1) {
        $args = func_get_args();
        if (is_array($args[1])) $args = $args[1]; // Only the second argument is considered if it's an array
        else array_shift($args); // Unset $string argument
        $trans = xarMLS__bindVariables($trans, $args);
    }

    return $trans;
}

// L10N API (Localisation)

/**
 * Gets the locale info for the specified locale string.
 * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
 *
 * @access public
 * @return array locale info
 */
function xarLocaleGetInfo($locale)
{
    return xarMLS__parseLocaleString($locale);
}

/**
 * Gets the locale string for the specified locale info.
 * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
 *
 * @access public
 * @throws BadParameterException
 * @return string locale string
 */
function xarLocaleGetString($localeInfo)
{
    if (!isset($localeInfo['lang']) ||
        !isset($localeInfo['country']) ||
        !isset($localeInfo['specializer']) ||
        !isset($localeInfo['charset'])) {
        throw new BadParameterException('localeInfo');
    }
    if (strlen($localeInfo['lang']) != 2) throw new BadParameterException('localeInfo');

    $locale = strtolower($localeInfo['lang']);
    if (!empty($localeInfo['country'])) {
        if (strlen($localeInfo['country']) != 2) throw new BadParameterException('localeInfo');

        $locale .= '_'.strtoupper($localeInfo['country']);
    }
    if (!empty($localeInfo['charset'])) {
        $locale .= '.'.$localeInfo['charset'];
    } else {
        $locale .= '.utf-8';
    }
    if (!empty($localeInfo['specializer'])) {
        $locale .= '@'.$localeInfo['specializer'];
    }
    return $locale;
}

/**
 * Gets a list of locale string which met the specified filter criteria.
 * Filter criteria are set as item of $filter parameter, they can be one or more of the following:
 * lang, country, specializer, charset.
 *
 * @access public
 * @return array locale list
 */
function xarLocaleGetList($filter=array())
{
    $list = array();
    $locales = xarMLSListSiteLocales();
    foreach ($locales as $locale) {
        $l = xarMLS__parseLocaleString($locale);
        if (isset($filter['lang']) && $filter['lang'] != $l['lang']) continue;
        if (isset($filter['country']) && $filter['country'] != $l['country']) continue;
        if (isset($filter['specializer']) && $filter['specializer'] != $l['specializer']) continue;
        if (isset($filter['charset']) && $filter['charset'] != $l['charset']) continue;
        $list[] = $locale;
    }
    return $list;
}

/**
 *  Returns a valid timestamp for the current user.  It will
 *  make adjustments for timezone and should be used in gmstrftime
 *  or gmdate functions only.
 *
 *  @access protected
 *  @return int unix timestamp.
 */
function xarMLS_userTime($time=null,$flag=1)
{
    // get the current UTC time
    if (!isset($time)) {
        $time = time();
    }

    if ($flag) $time += xarMLS_userOffset($time) * 3600;
    // return the corrected timestamp
    return $time;
}

/**
 *  Returns the user's current tz offset (+ daylight saving) in hours
 *
 *  @access protected
 *  @param int $timestamp optional unix timestamp that we want to get the offset for
 *  @return float tz offset + possible daylight saving adjustment
 */
function xarMLS_userOffset($timestamp = null)
{
    static $usertz;
    sys::import('xarigami.xarDate');

    $datetime = new XarDateTime();
    $datetime->setTimeStamp($timestamp);
    if ($usertz === NULL) {
        if (xarUserIsLoggedIn() && xarModGetVar('roles','setusertimezone')) {
            $usertzdata = xarModUserVars::get('roles','usertimezone', xarSessionGetVar('uid'));
            //consistency with prior format in decimal hours for offset -  held as timezone and offset in serialized array
            $usertzdata = @unserialize($usertzdata);
            $usertz = $usertzdata['timezone'];
        } else {
            // TODO what to do of xarConfigGetVar('Site.MLS.DefaultTimeOffset');
            // and see if we still need to use xarModGetVar('roles','usertimezone');
            $usertz = xarConfigGetVar('Site.Core.TimeZone');
        }
    }
    $useroffset = $datetime->getTZOffset($usertz);
    return $useroffset/3600;
}

// PROTECTED FUNCTIONS

/**
 * Sets current locale
 *
 * @access protected
 * @param locale site locale
 */
function xarMLS_setCurrentLocale($locale)
{
    static $called = 0;

    assert($called == 0, 'Can only be called once during a page request');
    $called++;

    $mode = xarMLSGetMode();
    switch ($mode) {
    case XARMLS_SINGLE_LANGUAGE_MODE:
            $locale  = xarMLSGetSiteLocale();
            break;
    case XARMLS_UNBOXED_MULTI_LANGUAGE_MODE:
    case XARMLS_BOXED_MULTI_LANGUAGE_MODE:
        // check for locale availability
        $siteLocales = xarMLSListSiteLocales();
        if (!in_array($locale, $siteLocales)) {
            // Locale not available, use the default
            $locale = xarMLSGetSiteLocale();
            xarLogMessage("WARNING: falling back to default locale: $locale");
        }
    }
    // Set current locale
    $GLOBALS['xarMLS_currentLocale'] = $locale;

    $curCharset = xarMLSGetCharsetFromLocale($locale);
    if ($mode == XARMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
        assert($curCharset == "utf-8", 'Resetting MLS Mode to BOXED');
        // To be able to continue, we set the mode to BOXED
        if ($curCharset != "utf-8") {
            xarLogMessage("Resetting MLS mode to BOXED");
            xarConfigVars::set(null,'Site.MLS.MLSMode','BOXED');
        } else {
            if (!xarFuncIsDisabled('ini_set')) ini_set('mbstring.func_overload', 7);
            mb_internal_encoding($curCharset);
        }
    }

    //if ($mode == XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
    //if (substr($curCharset, 0, 9) != 'iso-8859-' &&
    //$curCharset != 'windows-1251') {
    // Do not use mbstring for single byte charsets

    //}
    //}

    $alternatives = xarMLS__getLocaleAlternatives($locale);
/* TODO: delete after new backend testing
    switch ($GLOBALS['xarMLS_backendName']) {
    case 'xml':
        sys::import('xarigami.xarMLSXMLBackend');
        $GLOBALS['xarMLS_backend'] = new xarMLS__XMLTranslationsBackend($alternatives);
        break;
    case 'php':
        sys::import('xarigami.xarMLSPHPBackend');
        $GLOBALS['xarMLS_backend'] = new xarMLS__PHPTranslationsBackend($alternatives);
        break;
    case 'xml2php':
*/
        sys::import('xarigami.xarMLSXML2PHPBackend');
        $GLOBALS['xarMLS_backend'] = new xarMLS__XML2PHPTranslationsBackend($alternatives);

/*
        break;
    }
*/
    // Load core translations
    xarMLS_loadTranslations(XARMLS_DNTYPE_CORE, 'xarigami', 'core:', 'core');

    //xarMLSLoadLocaleData($locale);
}

/**
 * Loads translations for the specified context
 *
 * @access protected
 * @return bool
 */
function xarMLS_loadTranslations($dnType, $dnName, $ctxType, $ctxName)
{
    static $loadedCommons = array();
    static $loadedTranslations = array();

    if (!isset($GLOBALS['xarMLS_backend'])) {
        xarLogMessage("xarMLS: No translation backend was selected for ". "$dnType.$dnName.$ctxType.$ctxName");
        return false;
    }
    if (empty($GLOBALS['xarMLS_currentLocale'])) {
        xarLogMessage("xarMLS: No current locale was selected");
        return false;
    }

    // only load each translation once
    if (isset($loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"])) {
        return $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"];
    }

    if ($GLOBALS['xarMLS_backend']->bindDomain($dnType, $dnName)) {
        if ($dnType == XARMLS_DNTYPE_MODULE) {
            // Handle in a special way the module type
            // for which it's necessary to load common translations
            if (!isset($loadedCommons[$dnName.'module'])) {
                $loadedCommons[$dnName.'module'] = true;
                if (!$GLOBALS['xarMLS_backend']->loadContext('modules:', 'common')) return; // throw back
                if (!$GLOBALS['xarMLS_backend']->loadContext('modules:', 'version')) return; // throw back
            }
        }
        if ($dnType == XARMLS_DNTYPE_THEME) {
            // Load common translations
            if (!isset($loadedCommons[$dnName.'theme'])) {
                $loadedCommons[$dnName.'theme'] = true;
                if (!$GLOBALS['xarMLS_backend']->loadContext('themes:', 'common')) return; // throw back
            }
        }

        if (!$GLOBALS['xarMLS_backend']->loadContext($ctxType, $ctxName)) return; // throw back
        $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"] = true;
        return true;
    } else {
        // FIXME: postpone
        //xarEvt_fire('MLSMissingTranslationDomain', array($dnType, $dnName));

        $loadedTranslations["$dnType.$dnName.$ctxType.$ctxName"] = false;
        return false;
    }
}

/**
 * Load relevant translations for a specified relatvive path (be it file or directory)
 *
 * @return bool true on success, false on failure
 * @todo slowly add more intelligence for more scopes. (core, version, init?)
 * @todo static hash on path to prevent double loading?
 * @todo is directory support needed? i.e. modules/base/ load all for base module? or how does this work?
 * @todo pnFile.php type files support needed?
 * @todo xarversion.php type files support
 * @todo xar(whatever)api.php type files support? (javascript for example)
 * @todo do we want core per file support?
 **/
function xarMLSLoadTranslations($path)
{
    $possibleOverride = false;
    if (!$GLOBALS['xarMLS_mode']) return; //check for MLS need
    if(!file_exists($path)) {
        xarLogMessage("MLS: Trying to load translations for a non-existing path ($path)",XARLOG_LEVEL_WARNING);
        //die($path);
        return true;
    }

    // Get a structured representation of the path.
    $pathElements = explode("/",$path);

    // Initialise some defaults
    $dnType = XARMLS_DNTYPE_MODULE; $possibleOverride = false; $ctxType = 'modules';

    // Determine dnType
    // Lets get core files out of the way
    if($pathElements[0] == 'lib') return xarMLS_loadTranslations(XARMLS_DNTYPE_CORE, 'xarigami', 'core:', 'core');

    // modules have a fixed place, so if it's not 'modules/blah/blah' it's themes, period.
    // NOTE: $pathElements changes here!
    if( $pathElements[0] != 'modules') {
        $dnType = XARMLS_DNTYPE_THEME;
        $possibleOverride = true;
        $possibleOverride = in_array('modules',$pathElements);
        $ctxType= 'themes';
    }
    array_shift($pathElements);
    $ctxType .= ":";

    // Determine dnName
    // The specifics within that Type are in the next element, overridden or not
    // NOTE: $pathElements changes here!
    $dnName = array_shift($pathElements);

    // Determine ctxName, which is just the basename of the file without extension it seems
    // CHECKME: there was a hardcoded substr(str,0,-3) here earlier
    // NOTE: $pathElements changes here!
    $ctxName = preg_replace('/^(xar)?(.+)\..*$/', '$2', array_pop($pathElements));

    // Determine ctxType further if needed (i.e. more path components are there)
    // Peek into the first element and unwind the rest of the path elements into $ctxType
    // xartemplates -> templates, xarblocks -> blocks, xarproperties -> properties etc.
    // NOTE: pnFile.php type files support needed?
    if(!empty($pathElements)) {
        $pathElements[0] = preg_replace('/^xar(.+)/','$1',$pathElements[0]);
        $ctxType .= implode("/",$pathElements);
    }

    // Ok, based on possible overrides, we load internal only, or interal plus overrides
    $ok = false;
    if($possibleOverride) {
        $ok= xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE,$dnName,$ctxType,$ctxName);
        array_shift($pathElements);
         $module_name = array_shift($pathElements);
         if (count($pathElements)>0) {
            $ok= xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $module_name, "modules:templates".'/'.implode("/",$pathElements), $ctxName);
         }
         else {
             $ok= xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $module_name, "modules:templates", $ctxName);
         }
    }
    // And load the determined stuff
    // @todo: should we check for success on *both*, where is the exception here? further up the tree?
    return xarMLS_loadTranslations($dnType, $dnName, $ctxType, $ctxName);
    //return $ok;
}

function xarMLS_convertFromInput($var, $method)
{
    // FIXME: <marco> Can we trust browsers?
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_http_input')) {
        return $var;
    }
    // CHECKME: check this code
    return $var;
    // Cookies must contain only US-ASCII characters
    $inputCharset = strtolower(mb_http_input($method));
    $curCharset = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    if ($inputCharset != $curCharset) {
        $var = mb_convert_encoding($var, $curCharset, $inputCharset);
    }
    return $var;
}

// PRIVATE FUNCTIONS

function xarMLS__convertFromCharset($var, $charset)
{
    // FIXME: <marco> Can we trust browsers?
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE ||
        !function_exists('mb_convert_encoding')) return $var;
    $curCharset = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    $var = mb_convert_encoding($var, $curCharset, $charset);
    return $var;
}

function xarMLS__bindVariables($string, $args)
{

    // FIXME: <marco> Consider to use strtr to do the same, can we?
    $i = 1;
    foreach($args as $var) {
        $search = "#($i)";
        $string = str_replace($search, $var, $string);
        $i++;
    }
    return $string;
}

/**
 * Gets a list of alternatives for a certain locale.
 * The first alternative is the locale itself
 *
 * @return array alternative locales
 */
function xarMLS__getLocaleAlternatives($locale)
{
    if (!$parsedLocale = xarMLS__parseLocaleString($locale)) return; // throw back
    extract($parsedLocale); // $lang, $country, $charset

    $alternatives = array($locale);
    if (!empty($country) && !empty($specializer)) $alternatives[] = $lang.'_'.$country.'.'.$charset;
    if (!empty($country) && empty($specializer)) $alternatives[] = $lang.'.'.$charset;

    return $alternatives;
}

/**
 * Parses a locale string into an associative array composed of
 * lang, country, specializer and charset keys
 *
 * @return array parsed locale
 */
function xarMLS__parseLocaleString($locale)
{
    $res = array('lang'=>'', 'country'=>'', 'specializer'=>'', 'charset'=>'utf-8');
    // Match the locales standard format  : en_US.iso-8859-1
    // Thus: language code lowercase(2), country code uppercase(2), encoding lowercase(1+)
    if (!preg_match('/([a-z][a-z])(_([A-Z][A-Z]))?(\.([0-9a-z\-]+))?(@([0-9a-zA-Z]+))?/', $locale, $matches)) {
        throw new BadParameterException('locale');
    }

    $res['lang'] = $matches[1];
    if (!empty($matches[3])) $res['country'] = $matches[3];
    if (!empty($matches[5])) $res['charset'] = $matches[5];
    if (!empty($matches[7])) $res['specializer'] = $matches[7];

    return $res;
}

/**
 * Gets the single byte charset most typically used in the Web for the
 * requested language
 *
 * @return string the charset
 * @todo   Dont hardcode this
 */
function xarMLS__getSingleByteCharset($langISO2Code)
{
    static $charsets = array(
        'af' => 'iso-8859-1', 'sq' => 'iso-8859-1',
        'ar' => 'iso-8859-6',  'eu' => 'iso-8859-1',  'bg' => 'iso-8859-5',
        'be' => 'iso-8859-5',  'ca' => 'iso-8859-1',  'hr' => 'iso-8859-2',
        'cs' => 'iso-8859-2',  'da' => 'iso-8859-1',  'nl' => 'iso-8859-1',
        'en' => 'iso-8859-1',  'eo' => 'iso-8859-3',  'et' => 'iso-8859-15',
        'fo' => 'iso-8859-1',  'fi' => 'iso-8859-1',  'fr' => 'iso-8859-1',
        'gl' => 'iso-8859-1',  'de' => 'iso-8859-1',  'el' => 'iso-8859-7',
        'iw' => 'iso-8859-8',  'hu' => 'iso-8859-2',  'is' => 'iso-8859-1',
        'ga' => 'iso-8859-1',  'it' => 'iso-8859-1',  //'ja' => '',
        'lv' => 'iso-8859-13', 'lt' => 'iso-8859-13', 'mk' => 'iso-8859-5',
        'mt' => 'iso-8859-3',  'no' => 'iso-8859-1',  'pl' => 'iso-8859-2',
        'pt' => 'iso-8859-1',  'ro' => 'iso-8859-2',  'ru' => 'windows-1251',
        'gd' => 'iso-8859-1',  'sr' => 'iso-8859-2',  'sk' => 'iso-8859-2',
        'sl' => 'iso-8859-2',  'es' => 'iso-8859-1',  'sv' => 'iso-8859-1',
        'tr' => 'iso-8859-9',  'uk' => 'iso-8859-5'
    );

    return @$charsets[$langISO2Code];
}

// MLS CLASSES

/**
 * Translations backend interface
 *.
 * It defines a simple interface used by the Multi Language System to fetch both
 * string and key based translations.
 *
 * @package multilanguage
 * @todo    interface once php5 is there
 */

interface ITranslationsBackend {
    // Get the string based translation associated to the string param.
    function translate($string);

    // Get the key based translation associated to the key param.
    function translateByKey($key);

    // Unload loaded translations.
    function clear();

    // Bind the backend to the specified domain.
    function bindDomain($dnType, $dnName='xarigami');

    // Check if this backend supports a specified translation context.
    function hasContext($ctxType, $ctxName);

    // Load a set of translations into the backend.
    function loadContext($ctxType, $ctxName);

    // Get available context names for the specified context type
    function getContextNames($ctxType);
}


/**
 * This abstract class inherits from xarMLS__TranslationsBackend and provides
 * a powerful access to metadata associated to every translation entry.
 * A translation entry is an array that contains not only the translation,
 * but also the a list of references where it appears in the source by
 * reporting the file name and the line number.
 *
 * @package multilanguage
 * @throws Exception, BadParameterException
 */
abstract class xarMLS__ReferencesBackend extends xarObject implements ITranslationsBackend
{
    public $locales;
    public $locale;
    public $domainlocation;
    public $contextlocation;
    public $backendtype;
    public $space;
    public $spacedir;
    public $domaincache;

    function __construct($locales)
    {
        $this->locales = $locales;
        $this->domaincache = array();
    }
    /**
     * Gets a translation entry for a string based translation.
     */
    function getEntry($string)
    { throw new Exception('method is abstract? (todo)'); }

    /**
     * Gets a translation entry for a key based translation.
     */
    function getEntryByKey($key)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Gets a transient identifier (integer) that is guaranteed to identify
     * the translation entry for the string based translation in the next HTTP request.
     */
    function getTransientId($string)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Gets the translation entry identified by the passed transient identifier.
     */
    function lookupTransientId($transientId)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Enumerates every string based translation, use the reset param to restart the enumeration.
     */
    function enumTranslations($reset = false)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Enumerates every key based translation, use the reset param to restart the enumeration.
     */
    function enumKeyTranslations($reset = false)
    { throw new Exception('method is abstract? (todo)'); }

    function bindDomain($dnType, $dnName = 'xarigami')
    {
        // only bind each domain once (?)
        //if (isset($this->domaincache["$dnType.$dnName"])) {
        // CHECKME: make sure we can cache this (e.g. set $this->domainlocation here first ?)
        //    return $this->domaincache["$dnType.$dnName"];
        //}

        switch ($dnType) {
        case XARMLS_DNTYPE_MODULE:
            $this->spacedir = "modules";
            break;
        case XARMLS_DNTYPE_THEME:
            $this->spacedir = "themes";
            break;
        case XARMLS_DNTYPE_CORE:
            $this->spacedir = "core";
            break;
        default:
            $this->spacedir = NULL;
            break;
        }

        foreach ($this->locales as $locale) {
            if($this->spacedir == "core" || $this->spacedir == "xarigami") {
                $this->domainlocation  = sys::varpath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir;
            } else {
                $this->domainlocation  = sys::varpath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir . "/" . $dnName;
            }

            if (file_exists($this->domainlocation)) {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            } elseif ($GLOBALS['xarMLS_backendName'] == 'xml2php') {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            }
        }

        //$this->domaincache["$dnType.$dnName"] = false;
        return false;
    }

    function getDomainLocation()
    { return $this->domainlocation; }

    function getContextLocation()
    { return $this->contextlocation; }

    function hasContext($ctxType, $ctxName)
    {
        return $this->findContext($ctxType, $ctxName) != false;
    }

    function findContext($ctxType, $ctxName)
    {
        if (strpos($ctxType, 'modules:') !== false) {
            list ($ctxPrefix,$ctxDir) = explode(":", $ctxType);
            $fileName = $this->getDomainLocation() . "/$ctxDir/$ctxName." . $this->backendtype;
        } elseif (strpos($ctxType, 'themes:') !== false) {
            list ($ctxPrefix,$ctxDir) = explode(":", $ctxType);
            $fileName = $this->getDomainLocation() . "/$ctxDir/$ctxName." . $this->backendtype;
        } elseif (strpos($ctxType, 'core:') !== false) {
            $fileName = $this->getDomainLocation() . "/". $ctxName . "." . $this->backendtype;
        } else {
            throw new BadParameterException(array('context',$ctxType));
        }
        $fileName = str_replace('//','/',$fileName);
        if (!file_exists($fileName)) {
//            throw new FileNotFoundException($fileName);
            return false;
        }
        return $fileName;
    }

}

/**
 * Create directories tree
 *
 * @access protected
 * @return bool true
 */
function xarMLS__mkdirr($path)
{
    // Check if directory already exists
    if (is_dir($path) || empty($path)) {
        return true;
    }

    // Crawl up the directory tree
    $next_path = substr($path, 0, strrpos($path, '/'));
    if (xarMLS__mkdirr($next_path)) {
        if (!file_exists($path)) {
            $result = @mkdir($path, 0700);
            if (!$result) {
                $msg = xarML("The directories under #(1) must be writeable by PHP.", $next_path);
                xarLogMessage($msg);
                // throw new PermissionException?;
            }
            return $result;
        }
    }
    return false;
}

/**
 * Check directory writability and create directory if it doesn't exist
 *
 * @access protected
 * @return bool true
 */
function xarMLS__iswritable($directory=NULL)
{
    if ($directory == NULL) {
        $directory = getcwd();
    }

    if (file_exists($directory)) {
        if (!is_dir($directory)) return false;
        $isWritable = true;
        $isWritable &= is_writable($directory);
        $handle = opendir($directory);
        while ($isWritable && (false !== ($filename = readdir($handle)))) {
            if (($filename != ".") && ($filename != "..") && ($filename != "SCCS")) {
                if (is_dir($directory."/".$filename)) {
                    $isWritable &= is_writable($directory."/".$filename);
                    $isWritable &= xarMLS__iswritable($directory."/".$filename);
                } else {
                    $isWritable &= is_writable($directory."/".$filename);
                }
            }
        }
        return $isWritable;
    } else {
        $isWritable = xarMLS__mkdirr($directory);
        return $isWritable;
    }
}
/* *************************** VIRTUAL PATHS ************************************ */

/**
 * Create a name to get and to set virtual paths mapping from the config vars.
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access private
 * @return string
 */
function xarMLS__GetVirtualPathConfigVarId($locale)
{
    return 'Site.MLS.VirtualPaths.' . $locale;
}

/**
 * Set a virtual path (unsafe)
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access private
 * @return bool true
 */
function xarMLS__SetVirtualPath($locale, $virtualpath = '')
{
    return xarConfigVars::set(null,xarMLS__GetVirtualPathConfigVarId($locale), $virtualpath);
}

/**
 * Get a virtual path (unsafe)
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access private
 * @return bool string
 */
function xarMLS__GetVirtualPath($locale)
{
    $path = xarConfigVars::get(null,xarMLS__GetVirtualPathConfigVarId($locale), '');
    return $path;
}

/**
 * Get a 2D array with the active path mapping
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return array
 */
function xarMLSGetVirtualPathMappingArray()
{
    $paths = array();
    $locales = xarMLSListSiteLocales();
    foreach ($locales as $locale) {
        $paths[] = array('locale' => $locale, 'path' => xarMLS__GetVirtualPath($locale));
    }
    return $paths;
}

/**
 * Get an array with the active path mapping, locale is key
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return array
 */
function xarMLSGetVirtualPathMappingArrayFromLocale()
{
    $paths = array();
    $locales = xarMLSListSiteLocales();
    foreach ($locales as $locale) {
        $paths[$locale] = xarMLS__GetVirtualPath($locale);
    }
    return $paths;
}

/**
 * Get an array of the active path mapping, path is the key
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return array
 */
function xarMLSGetVirtualPathMappingArrayFromPath()
{
    $reverse = array();
    $locales = xarMLSListSiteLocales();
    foreach ($locales as $locale) {
        $path = xarMLS__GetVirtualPath($locale);
        if(!empty($path))
            $reverse[$path] = $locale;
    }
    return $reverse;
}

/**
 * Get an array of the existing paths
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return array
 */
function xarMLSGetVirtualPaths()
{
    $paths = array();
    $locales = xarMLSListSiteLocales();
    foreach ($locales as $locale) {
        $path = xarMLS__GetVirtualPath($locale);
        if(!empty($path))
            $paths[] = $path;
    }
    return $paths;
}

/**
 * Find the local corresponding to a virtual path
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return array
 */
function xarMLSGetLocaleFromVirtualPath($path = '')
{
    // Build the reverse map
    $reverse = xarMLSGetVirtualPathMappingArrayFromPath();
    // Return nothing if there is no reversed path - locale record.
    if (empty($path) || !array_key_exists($path, $reverse)) return;
    //
    return $reverse[$path];
}

/**
 * Extract from an URI path the virtual path if it exists
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return string
 */
function xarMLSExtractVirtualPath($path, $removeslash=true, $redirect=false)
{
    // Full URL is required to prevent call of xarServerBaseURL() and recursive calls
    $server = xarServer::getHost();
    $protocol = xarServerGetProtocol();
    $basepath = "$protocol://$server";
    $reverse = xarMLSGetVirtualPathMappingArrayFromPath();

    // Make it short if this is the root.
    if (empty($path) || $path == '/') {
        if (array_key_exists('/', $reverse))
            return '/';
        else
            return;
    }

    // Progressive approach to find the path
    $pathArr = explode('/', $path);
    $RePath = "/";
    $i = 0;
    $last = count($pathArr);

    foreach($pathArr as $sub) {
        $i++;
        if (empty($sub)) {
            if ($i == $last) return;
            continue;
        }
        // Attempt without any ending slash
        $RePath .= $sub;
        if (array_key_exists($RePath, $reverse)) break;
        // Attempt with an ending slash
        $RePath .= '/';
        if (array_key_exists($RePath, $reverse)) break;
        // Virtual paths do not take more than two levels /dir1/dir2/
        // If last element stop the search
        if ($i > 4 || $i == $last) return;
    }
    // We have definitely found something

    // Virtual path is exactly path
    if (strlen($RePath) == strlen($path) && $RePath == $path) {
        // Remove the ending slash for xarServer::getBaseURI()
        if ($removeslash) return rtrim($RePath, '/');
        return $RePath;
    }

    // Conform to the proper URL and ending slash.
    if ($redirect) {
        // Virtual path has one slash more than current path
        if (strlen($RePath) > strlen($path) && $RePath == $path . '/') {
            // Conform to virtual path set
            xarResponseRedirect($basepath . $RePath, 301);
        }

        // Virtual path is contained in the existing path

        // Existing path might have just one slash more. /fr/ where virtual path is /fr
        if (strlen($path) - strlen($RePath) == 1 && rtrim($path, '/') == $RePath) {
            // Conform to virtual path set
            xarResponseRedirect($basepath . $RePath, 301);
        }
    }
    // Nothing else special to do...
    // Remove the ending slash for xarServer::getBaseURI()
    if ($removeslash) return rtrim($RePath, '/');
    // For use with xarMLSLocaleFromURI()
    return $RePath;
}

/**
 * Attempt to retrieve locale from an URI path, the locale if there is some virtual path
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return string
 */
function xarMLSLocaleFromURI($path)
{
    $virtualpath = xarMLSExtractVirtualPath($path, false, false);
    if (!isset($virtualpath)) return;
    return xarMLSGetLocaleFromVirtualPath($virtualpath);
}

/**
.* Enforce the URL to fit to the given locale and eventually redirects.
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return bool
 */
function xarMLSEnforceLocale($locale, $request = '', $override=false)
{
    // Don't do anything if features are not activated or override
    if ($override) {
    } else {
        if (!xarMLSVirtualPathsIsEnforced() || !xarMLSVirtualPathsIsEnabled()) return false;
    }

    // Full URL is required to prevent call of xarServerBaseURL() and recursive calls
    $server = xarServer::getHost();
    $protocol = xarServerGetProtocol();
    $basepath = "$protocol://$server";

    if (empty($request)) {
        $request =  xarRequest::getClientUri();
    } else {
        $request = str_replace($basepath, '', $request);
    }

    $currentvirtualpath = xarMLSExtractVirtualPath($request, false, false);
    $currentlocale = xarMLSGetLocaleFromVirtualPath($currentvirtualpath);
    // VP locale and target locale are the same nothing to do
    if (isset($currentlocale) && $currentlocale == $locale) return true;

    // Retrieve the new virtual path
    $virtualpath = xarMLS__GetVirtualPath($locale);
    // no virtual path existing... sorry cannot do anything
    if (empty($virtualpath)) return false;

    // VP locale is probably the root
    if (!isset($currentvirtualpath) || !isset($currentlocale) || $currentvirtualpath == '/') {
        // We just need to add the new virtual path
        if ($request == '/') {
            $redirect = $basepath . $virtualpath;
        }
        else
        {
            $redirect = $basepath . rtrim($virtualpath, '/') . $request;
        }
    }
    // There is already a virtual path
    else {
        // Replace one by the other. Is it safe?
        $search = '%^' . rtrim($currentvirtualpath, '/') . '%';
        $replace = rtrim($virtualpath, '/');
        xarLogMessage($search . "==" . $replace . "==" . $request);
        $redirect = $basepath . preg_replace($search, $replace, $request);
    }
    // Finally redirects
    xarResponseRedirect($redirect, 301);
    return true;
}


/**
 * Map a virtual path to a locale
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return bool true
 */
function xarMLSSetVirtualPath($locale, $virtualpath = '')
{
    $locales = xarMLSListSiteLocales();
    // no additional mapping if it is not a site locale. Override if we clear it using '' value.
    if (!empty($virtualpath) && !in_array($locale, $locales)) return false;
    // locale exists then proceed to the mapping
    // Empty path, clear it
    if (empty($virtualpath))
        return xarMLS__SetVirtualPath($locale, '');
    // Path value exists, add start slash
    if (substr($virtualpath, 0 , 1) != '/')
        return xarMLS__SetVirtualPath($locale, '/' . $virtualpath);
    // Proceed normally
    return xarMLS__SetVirtualPath($locale, $virtualpath);
}

/**
 * Get a virtual path mapped to a locale
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return string
 */
function xarMLSGetVirtualPath($locale = '', $safecheck = 'true')
{
    // if it is not a valid site locale, return value is unset
    if (empty($locale)) return;
    //
    if ($safecheck) {
        $locales = xarMLSListSiteLocales();
        // no additional mapping if it is not a site locale
        if (!in_array($locale, $locales)) return;
    }
    // locale exists then proceed and return the mapped virtual path if it exists
    return xarMLS__GetVirtualPath($locale, $virtualpath);
}

/**
 * Activate / deactivate Virtual Paths feature
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return
 */
function xarMLSActivateVirtualPaths($bool)
{
    return xarConfigVars::set(null,'Site.MLS.VirtualPaths.Enabled', $bool);
}

/**
 * Is Virtual Paths feature enabled?
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return bool
 */
function xarMLSVirtualPathsIsEnabled()
{
    // Bug Issue xgami-000584 - Installer issues
    // http://xarigami.com/contrails/display/xgami/584
    if (!function_exists('xarConfigGetVar')) return false;

    $bool = xarConfigVars::get(null,'Site.MLS.VirtualPaths.Enabled', false);
    return $bool;
}

/**
 * Enforce the URLS/Virtual paths to fit to the navigation locale feature
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return
 */
function xarMLSSetEnforcedVirtualPaths($bool)
{
    return xarConfigVars::set(null,'Site.MLS.VirtualPaths.Enforced', $bool);
}

/**
 * Should URLs be enforced to navigation locale?
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return bool
 */
function xarMLSVirtualPathsIsEnforced()
{
    $bool = xarConfigVars::get(null,'Site.MLS.VirtualPaths.Enforced', false);
    return $bool;
}


/* **************** LOCALE AUTO-DETECTION  ************************* */

/**
 * Activate / deactivate Locale Auto-Detection feature
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return bool
 */
function xarMLSActivateAutoDectection($bool)
{
    return xarConfigVars::set(null,'Site.MLS.AutoDetection.Enabled', $bool);
}

/**
 * Is Virtual Paths feature enabled?
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return bool
 */
function xarMLSAutoDetectionIsEnabled()
{
    $bool = xarConfigVars::get(null,'Site.MLS.AutoDetection.Enabled');
    return isset($bool) ? $bool : false;
}

/**
 * Returns the locale detected from client web browser
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
 * @return string
 */
function xarMLSGetClientBrowserLocale()
{
    // Keep things compatible
    if (!xarMLSAutoDetectionIsEnabled()) return xarMLSGetSiteLocale();

    $BrowserDetection = new xarMLSBrowserLocales();
    if (empty($BrowserDetection->bestlocale)) return xarMLSGetSiteLocale();

    return $BrowserDetection->bestlocale;
}

/**
 * Class handling and parsing web browser settings seeking locales
 *
 * @author Arnaud Tetaz <lakys-xarigami@lakeworks.com>
 * @access public
*/
/* SAMPLES:
 *
 * Firefox 3
 * HTTP_ACCEPT_CHARSET:ISO-8859-1,utf-8;q=0.7,*;q=0.7
 * HTTP_ACCEPT_LANGUAGE:fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3
 *
 * Internet Explorer 7
 * HTTP_ACCEPT_LANGUAGE:fr
 *
 * Google Chrome
 * HTTP_ACCEPT_LANGUAGE:fr-FR,fr,en-US,en
 *
 * Opera
 * HTTP_ACCEPT_LANGUAGE:fr, en
 * HTTP_ACCEPT_CHARSET:windows-1252, utf-8, utf-16, iso-8859-1;q=0.6, *;q=0.1
 *
 * Safari
 * HTTP_ACCEPT_LANGUAGE:fr-FR
 *
*/
class xarMLSBrowserLocales
{
    private $accept_charset = '';
    private $accept_language = '';
    public $overall = array();
    protected $final = array();
    public $charset;
    public $language;
    public $locales;
    private $called = false;
    public $report = '';
    public $bestlocale = '';
    public $alternatelocale = '';

    function __construct($debug = false)
    {
        $this->accept_charset = xarServer::getVar('HTTP_ACCEPT_CHARSET');
        $this->accept_language = xarServer::getVar('HTTP_ACCEPT_LANGUAGE');
        $this->charset = new xarMLS_ParseBrowserCharset($this->accept_charset);

        $this->language = new xarMLS_ParseBrowserLanguage($this->accept_language);
        $this->locales = xarMLSListSiteLocales();

        $this->OverallRating($debug);
        $this->SetResults();

    }

    private function OverallRating($debug = false)
    {
        if($this->called) return;
        $this->called = true;
        //
        $charsetArr = $this->charset->getArray();
        $languageArr = $this->language->getArray();
        if ($debug)
        {
            $this->report .="<br />";
            $this->report .= $this->ShowArray($charsetArr);
            $this->report .="<br />";
            $this->report .= $this->ShowArray($languageArr);
            $this->report .= "<br />";
        }

        // 1st pass - languages
        foreach($this->locales as $locale) {
            // Try to find the language (fr_FR)
            $key = xarMLSGetLanguageFromLocale($locale);
            if (array_key_exists($key, $languageArr)) {
                $this->overall[$locale] = $languageArr[$key];
                continue;
            }
            // Else just fr
            $key = substr($key, 0, 2);
            if (array_key_exists($key, $languageArr)) {
                $this->overall[$locale] = $languageArr[$key];
                continue;
            }
            // Else rate is with the * value
            $key = '*';
            if (array_key_exists($key, $languageArr)) {
                $this->overall[$locale] = $languageArr[$key];
            }
            else {
                // else rate it at a very low value
                $this->overall[$locale] = 0.1;
            }
        }
        // 2nd pass - charsets
        // some browsers do not return charset. Then skip it.
        if (count($charsetArr) !== 0) {
            foreach($this->locales as $locale) {
                $key = xarMLSGetCharsetFromLocale($locale);
                // Charset exists?
                if(array_key_exists($key, $charsetArr)) {
                    $this->overall[$locale] *= $charsetArr[$key];
                    continue;
                }
                // Seek the *
                $key = '*';
                if(array_key_exists($key, $charsetArr)) {
                    $this->overall[$locale] *= $charsetArr[$key];
                    continue;
                }
                // Fallback to a very low value;
                $this->overall[$locale] *= 0.2;
            }
        }
        array_multisort($this->overall, SORT_DESC | SORT_NUMERIC);
        if ($debug) {
            $this->report .= $this->ShowArray($this->overall);
        }
        return true;
    }

    private function SetResults()
    {
        $i = 0;
        foreach($this->overall as $key => $value) {
            $i++;
            switch($i) {
                case 1:
                    $this->bestlocale = $key;
                    break;
                case 2:
                    $this->alternatelocale = $key;
                    return;
                default:
                    return;
            }

        }
    }

    private function ShowArray($arr)
    {
        $res = "";
        foreach($arr as $key => $value) {
            $res .= $key . ' ' . $value . '<br />';
        }
        return $res;
    }
    public function DisplayBrowserInfo()
    {
        if (!xarMLSAutoDetectionIsEnabled()) return "";
        $res = "";
        $res .= "CHARSET: " . $this->accept_charset;
        $res .= "<br />";
        $res .= "LANGUAGE: " . $this->accept_language;
        $res .= "<br />";
        $res .= $this->report;
        $res .= "<br />";
        $res .= "<b>Best locale found: " . $this->bestlocale . "&nbsp;&nbsp;&nbsp;Alternate locale proposed: " . $this->alternatelocale;
        return $res;
    }
}

class xarMLS_ParseBrowserLocaleBase
{
    protected $str = '';
    protected $element = '';
    protected $final = array();

    function __construct($str)
    {
        $this->str = $str;
    }

    public function getArray()
    {
        $this->Explode();
        return $this->final;
    }

    private function hasRating($str)
    {
        // Seek expression like ;q=0.3
        return preg_match('#;q=[01]\.[0-9]#', $str) > 0;
    }

    protected function isRating()
    {
        return preg_match('#q=[01]\.[0-9]#', $this->element) > 0;
    }

    protected function Explode()
    {
        $splitArr= array();
        // Is the expression using ratings?
        if ($this->hasRating($this->str)) {
            // Split using separators , ;
            $splitArr = preg_split('/[,;]/', $this->str, -1, PREG_SPLIT_NO_EMPTY);
            // Loop backward as rates are placed at the end
            $rate = 1.0;
            for($i = count($splitArr)-1; $i >= 0; $i--) {
                $this->element = $splitArr[$i];
                if(!$this->isRating()) {
                    // Put it in a normalized format
                    $this->ReformatElement();
                    // Then affect it with the score
                    $this->final[$this->element] = $rate + $this->AdjustRate();
                }
                else {
                    // Get rate
                    $res = preg_match('#q=([01]\.[0-9])#', $this->element, $matches);
                    if($res > 0 && isset($matches[1]))
                        $rate = floatval($matches[1]);
                }

            }
        }
        else {
            // We need to give better rates to elements in first positions
            $splitArr = preg_split('/[,;]/', $this->str, -1, PREG_SPLIT_NO_EMPTY);
            $total = count($splitArr);
            for($i = 0; $i < $total; $i++) {
                $rate = round(1.0 - $this->DecCoef()*$i, 1);
                $this->element = $splitArr[$i];
                $this->ReformatElement();
                $this->final[$this->element] = $rate + $this->AdjustRate();
            }
        }
        array_multisort($this->final, SORT_DESC | SORT_NUMERIC);
    }

    // Virtual functions
    protected function ReformatElement()
    {
        throw new Exception('method is abstract? (todo)');
    }

    protected function AdjustRate()
    {
         throw new Exception('method is abstract? (todo)');
    }

    protected function DecCoef()
    {
          throw new Exception('method is abstract? (todo)');
    }

}

class xarMLS_ParseBrowserCharset extends xarMLS_ParseBrowserLocaleBase
{
    protected function ReformatElement()
    {
        // Some browsers return the charset with CAPS or spaces
        $this->element = trim(strtolower($this->element));
    }

    protected function AdjustRate()
    {
        if ($this->element == '*') return -0.2;
        return 0.0;
    }

    protected function DecCoef()
    {
        return 0.1;
    }
}

class xarMLS_ParseBrowserLanguage extends xarMLS_ParseBrowserLocaleBase
{
    protected function ReformatElement()
    {
        $this->element = trim($this->element);
        // Reformat the locale to the same standard used in Xarigami. xx_XX or xx
        // @todo: this does not return the format mentioned in previous comment but separated with dash.
        $this->element = preg_replace_callback("/([a-z]{2})[_-]([a-zA-Z]{2})/", function($matches) {
            if(count($matches) == 3) {
                return $matches[1].'-'.strtoupper($matches[2]);
            } else if(count($matches) == 2) {
                return $matches[1];
            }
            return $matches[0];
        }, $this->element);
    }

    protected function AdjustRate()
    {
        if ($this->element == '*') return -0.2;
        return strlen($this->element) < 5 ? -0.1 : 0.0;
    }

    protected function DecCoef()
    {
        return 0.15;
    }
}

?>
