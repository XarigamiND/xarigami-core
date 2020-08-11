<?php
/**
 * Locales (Multi Language System)
 *
 * @package Xarigami core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami multilanguage
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
class LocaleNotFoundException extends NotFoundExceptions
{
       protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The locale "#(1)" could not be found or is currently unavailable.');
        else
            return $this->xarML('The locale could not be found or is currently unavailable.');
    }
}

/**
 * Gets the locale data for a certain locale.
 * Locale data is an associative array, its keys are described at the top
 * of this file
 *
 * @access public
 * @return array locale data
 * @throws LocaleNotFoundException
 * @todo   figure out why we go through this function for xarMod::isAvailable
 */
function xarMLSLoadLocaleData($locale = NULL)
{
    static $loaded = array(); // keep track of files we have loaded
    if (!isset($locale)) {
        $locale = xarMLSGetCurrentLocale();
    }

    // rraymond : move the check for the loaded locale before processing as
    //          : all of this would have been taken care of the first time
    //          : the locale data was loaded - saves processing time
    if (isset($GLOBALS['xarMLS_localeDataCache'][$locale])) {
        return $GLOBALS['xarMLS_localeDataCache'][$locale];
    }

    // check for locale availability
    $siteLocales = xarMLSListSiteLocales();

    $nullreturn = null; $falsereturn = false;
    if (!in_array($locale, $siteLocales)) {
        if (strstr($locale,'ISO')) {
            $locale = str_replace('ISO','iso',$locale);
            if (!in_array($locale, $siteLocales)) {
                throw new LocaleNotFoundException($locale);
            }
        } else {
            throw new LocaleNotFoundException($locale);
        }
    }

    // @todo PHP based locale.php files. Is that something from the past and now using XML, so this code should be deleted until "ELSE"?
    $fileName = sys::varpath() . '/locales/$locale/locale.php';
    if (!$parsedLocale = xarMLS__parseLocaleString($locale)) return false;
    $siteCharset = $parsedLocale['charset'];
    $utf8locale = $parsedLocale['lang'].'_'.$parsedLocale['country'].'.utf-8';
    $utf8FileName = sys::varpath() . '/locales/$utf8locale/locale.php';
    if (file_exists($fileName) && !(isset($loaded[$fileName]))) {
        // @todo do we need to wrap this in a try/catch construct?
        include $fileName;
        $loaded[$fileName] = true;
        $GLOBALS['xarMLS_localeDataCache'][$locale] = $localeData;
    } else if (file_exists($utf8FileName) && !isset($loaded[$utf8FileName])) {
        include $utf8FileName;
        $loaded[$utf8FileName] = true;
        if ($siteCharset != 'utf-8') {
            foreach ( $localeData as $tempKey => $tempValue ) {
                $tempValue = $GLOBALS['xarMLS_newEncoding']->convert($tempValue, 'utf-8', $siteCharset, 0);
                $localeData[$tempKey] = $tempValue;
            }
        }
        $GLOBALS['xarMLS_localeDataCache'][$locale] = $localeData;
    } else {
/* TODO: delete after new backend testing
        if ($GLOBALS['xarMLS_backendName'] == 'xml2php') {
*/
            if (!$parsedLocale = xarMLS__parseLocaleString($locale)) return $falsereturn;
            $utf8locale = $parsedLocale['lang'].'_'.$parsedLocale['country'].'.utf-8';
            $siteCharset = $parsedLocale['charset'];
            $res = $GLOBALS['xarMLS_localeDataLoader']->load($utf8locale);
            if (isset($res) && $res == false) {
                throw new LocaleNotFoundException($utf8locale);
            }
            if (!isset($res)) return $nullreturn; // Throw back
            $tempArray = $GLOBALS['xarMLS_localeDataLoader']->getLocaleData();
            if ($siteCharset != 'utf-8') {
                foreach ( $tempArray as $tempKey => $tempValue ) {
                    $tempValue = $GLOBALS['xarMLS_newEncoding']->convert($tempValue, 'utf-8', $siteCharset, 0);
                    $tempArray[$tempKey] = $tempValue;
                }
            }
            $GLOBALS['xarMLS_localeDataCache'][$locale] = $tempArray;
/* TODO: delete after new backend testing
        } else {
            $res = $GLOBALS['xarMLS_localeDataLoader']->load($locale);
            if (!isset($res)) return $nullreturn; // Throw back
            if ($res == false) {
                // Can we use xarML here? border case, play it safe for now.
                throw new LocaleNotFoundException($locale);

            }
            $GLOBALS['xarMLS_localeDataCache'][$locale] = $GLOBALS['xarMLS_localeDataLoader']->getLocaleData();
        }
*/
    }

    return $GLOBALS['xarMLS_localeDataCache'][$locale];
}

/**
 * Parses a string as a currency amount according to specified locale data
 *
 * @access public
 * @return string representing a currency amount
 */
function xarLocaleParseCurrency($currency, $localeData = NULL)
{
    if ($localeData == NULL) {
        $localeData = xarMLSLoadLocaleData();
    }

    $currencySym = $localeData['/monetary/currencySymbol'];
    $currency = str_replace($currencySym,'',$currency);
    $currency = xarLocaleParseNumber($currency, $localeData, true);
    return trim($currency);
}

/**
 * Parses a string as a number according to specified locale data
 *
 * @access public
 * @return string representing a number
 */
function xarLocaleParseNumber($number, $localeData = NULL, $isCurrency = false)
{
    if ($localeData == NULL) {
        $localeData = xarMLSLoadLocaleData();
    }
    if ($isCurrency == true) $bp = 'monetary';
    else $bp = 'numeric';

    $groupSep = $localeData["/$bp/groupingSeparator"];
    $number = str_replace($groupSep,'',$number);
    return trim($number);
}

/**
 * Formats a currency according to specified locale data
 *
 * @access public
 * @return string formatted currency
 */
function xarLocaleFormatCurrency($currency, $localeData = NULL)
{
    if ($localeData == NULL) {
        $localeData = xarMLSLoadLocaleData();
    }
    $currencySym = $localeData['/monetary/currencySymbol'];
    return $currencySym.' '.xarLocaleFormatNumber($currency, $localeData, true);
}

/**
 * Formats a number according to specified locale data
 *
 * @access public
 * @return string formatted number
 * @throws BAD_PARAM
 */
function xarLocaleFormatNumber($number, $localeData = NULL, $isCurrency = false)
{
    if (!is_numeric($number)) {
        $number = (float) $number;
    }

    if ($localeData == NULL) {
        $localeData = xarMLSLoadLocaleData();
    }

    if ($isCurrency == true) $bp = 'monetary';
    else $bp = 'numeric';

    $groupSize = $localeData["/$bp/groupingSize"];
    $groupSep = $localeData["/$bp/groupingSeparator"];
    $decSep = $localeData["/$bp/decimalSeparator"];
    $decSepShown = $localeData["/$bp/isDecimalSeparatorAlwaysShown"];
    $maxFractDigits = $localeData["/$bp/fractionDigits/maximum"];
    $minFractDigits = $localeData["/$bp/fractionDigits/minimum"];

    $zeroDigit = $localeData['/decimalSymbols/zeroDigit'];
    $minusSign = $localeData['/decimalSymbols/minusSign'];

    if ($number < 0) {
        $number = -1 * $number;
        $minus = true;
    }

    // Round according to $maxFractDigits and convert to string
    $str_num = (string) round($number, $maxFractDigits);

    if (($dsep_pos = strpos($str_num, '.')) !== false) {
        $int_part = substr($str_num, 0, $dsep_pos);
        $dec_part = substr($str_num, $dsep_pos + 1);
    } else {
        $int_part = $str_num;
    }
    // FIXME: <marco> Do we really need the maximum integer digits?
    $int_part_len = strlen($int_part);
    if ($groupSize > 0) {
        $sepNum = (int) ($int_part_len / $groupSize);
        $firstSkip = $int_part_len - ($sepNum * $groupSize);

        $str_num = '';

        $pos = $firstSkip;
        while ($pos < $int_part_len) {
            $str_num .= $groupSep . substr($int_part, $pos, $groupSize);
            $pos += $groupSize;
        }
        if ($firstSkip > 0) {
            $str_num = substr($int_part, 0, $firstSkip) . $str_num;
        } else {
            $str_num = substr($str_num, 1);
        }
    } else {
        $str_num = $int_part;
    }

    if (isset($dec_part) || $decSepShown) {
        $str_num .= $decSep;
        if (!isset($dec_part)) {
            for ($i = 0; $i < $minFractDigits; $i++) $str_num .= '0';
        } else {
            $dec_part_len = strlen($dec_part);
            if ($dec_part_len < $minFractDigits) {
                for ($i = 0; $i < $minFractDigits - $dec_part_len; $i++) $dec_part .= '0';
            } elseif ($dec_part_len > $maxFractDigits) {
                $dec_part = substr($dec_part, 0, $maxFractDigits - $dec_part_len); // Note negative length
            }
            $str_num .= $dec_part;
        }
    }

    if (isset($minus)) {
        $str_num = $minusSign . $str_num;
    }

    if ($zeroDigit != '0') {
        $str_num = str_replace('0', $zeroDigit, $str_num);
    }

    return $str_num;
}

/**
 *  Wrapper to xarLocaleGetFormattedDate without timezone offset
 */
function xarLocaleGetFormattedUTCDate($length = 'short', $timestamp = null, $addoffset = false)
{
    if(!isset($timestamp)) {
        // get UTC timestamp
        $timestamp = time();
    }

    // pass this to the regular function, but without using the timezone offset here
    return xarLocaleGetFormattedDate($length,$timestamp,$addoffset);
}

/**
 *  Grab the formated date by the user's current locale settings
 *
 * @access public
 * @param string $length what date locale we want (short|medium|long)
 *    Selectors can be extended with (time|date|toly) to get "time date", "date time" or only "time" instead of "date"
 * @param int $timestamp unix timestamp in UTC (default current users time, fallback to site time)
 * @param int $timestamp optional unix timestamp in UTC to format
 * @param bool $addoffset add user timezone offset (default true)
 * @todo Check the exceptions when $length is not in the $validlengths (assert on it?)
 */
function xarLocaleGetFormattedDate($length = 'short', $timestamp = null, $addoffset = true)
{
    //$validLengths = array('short','medium','long');
    // list of predefined format names
    $validLengths  = array('short', 'medium', 'long',
                     'shorttime', 'mediumtime', 'longtime',
                     'shortdate', 'mediumdate', 'longdate',
                     'shorttoly', 'mediumtoly', 'longtoly',
                     'full',
                     'iso'
                    );
    $locale_format = '';

    $length = strtolower($length);

    if(!in_array($length,$validLengths)) {
        //TODO: We should throw a USER exception here
        //Set to ISO datetime format yyyy-MM-dds
        $locale_format = $length; //'%Y-%m-%d';
    }

    // the locale data should already be a static var in the main loader script
    // so we no longer need to make it a static in this function
    $localeData = xarMLSLoadLocaleData();
    // grab the right set of locale data
    //$locale_format = $localeData["/dateFormats/$length"];
   // use the last 4 chars in $length to grab a combination of locale data
    $set = substr($length, 0, strlen($length)-4);
    switch (substr($length, -4)) {
        case 'time' :
            $locale_format = $localeData["/timeFormats/$set"];
            $locale_format .= ' ' . $localeData["/dateFormats/$set"];
            break;
        case 'date' :
            $locale_format = $localeData["/dateFormats/$set"];
            $locale_format .= ' ' . $localeData["/timeFormats/$set"];
            break;
        case 'toly' :
            $locale_format = $localeData["/timeFormats/$set"];
            break;
        default:
            // No combination found. Try if $length as such is available. Matches
            // also for 'iso' and 'full' which may not exist in locale.xml yet.
            if (isset($localeData["/dateFormats/$length"])) {
                $locale_format = $localeData["/dateFormats/$length"];
            } else {
                if ($addoffset) {
                    // ISO date with timezone
                    $locale_format = 'yyyy-MM-ddTHH:mm:ssz';
                } else {
                    // ISO date based on GMT
                    $locale_format = 'yyyy-MM-ddTHH:mm:ss\Z';
                }
            }
            break;
     }

 // The backslash \ is the escape char for verbatim chars
    $search = array();
    $search['/yyyy/']           = '%Y';  // yyyy  4 digit year
    $search['/yy/']             = '%y';  // yy    2 digit year
    //
    $search['/MMMM/']           = '%B';  // MMMM  full month name
    $search['/MMM/']            = '%b';  // MMM   abbreviated month
    $search['/MM/']             = '%m';  // MM    month
    $search['/(?<![\\\\])M/']   = '%m';  // M     1 digit month (TODO)
    //
    $search['/ww/']             = '%U';  // ww    week of the year, not ISO
    $search['/(?<![\\\\])w/']   = '%U';  // w     1 digit week of the year (TODO)
    //
    $search['/(eeee|EEEE)/']    = '%A';  // EEEE  full weekday name
    $search['/dddd/']           = '%A';  // dddd  (deprecated)full weekday name
    $search['/EEE/']            = '%a';  // EEE   abbreviated weekday name
    $search['/ddd/']            = '%a';  // ddd   (deprecated) abbreviated weekday name
    $search['/DDD/']            = '%j';  // DDD   3 digit day of year
    $search['/dd/']             = '%d';  // dd    2 digit day of month
    $search['/(?<![%\\\\])d/']  = '%e';  // d     1 digit day of month, non space preceeding
    // ISO-8601 numeric day of the week 1 (for Monday) though 7 (for Sunday)
    $search['/(?<![%\\\\])e/']  = '%u';  // e     1 digit day of ISO week
    //
    $search['/(?<![%\\\\])a/']  = '%p';  // a     'AM' or 'PM', upper-case
    $search['/HH/']             = '%H';  // HH    hour [01-23]
    $search['/(?<![%\\\\])H/']  = '%H';  // H     hour [1-23]
    $search['/hh/']             = '%I';  // hh    hour [01-12]
    $search['/(?<![%\\\\])h/']  = '%I';  // h     hour [1-12]
    $search['/mm/']             = '%M';  // mm    2 digit minute TODO: m for 1 digit
    $search['/ss/']             = '%S';  // ss    2 digit second TODO: s for 1 digit
    //
    $search['/(?<![%\\\\])z/']  = '%Z';  // z     time zone offset/abbreviation
    $search['/\\\\/']           = '';    // \     Remove the escape char \

    $locale_format = preg_replace(array_keys($search), array_values($search), $locale_format);
    return xarLocaleFormatDate($locale_format,$timestamp,$addoffset);
}

/**
 * Wrapper to xarLocaleGetFormattedTime without timezone offset
 * Returns the formatted time in UTC without timezone offset
 * @access public
 * @param string $length what date locale we want (short|medium|long).
 * @param int $timestamp optional unix timestamp in UTC to format
 * @return string
 */
function xarLocaleGetFormattedUTCTime($length = 'short',$timestamp = null, $addoffset = false)
{
    if(!isset($timestamp)) {
        // get UTC timestamp
        $timestamp = time();
    }

    // pass this to the regular function, but without using the timezone offset here
    return xarLocaleGetFormattedTime($length,$timestamp,$addoffset);
}

/**
 * Grab the formated time by the user's current locale settings
 *
 * @access public
 * @param string $length what time locale we want (short|medium|long)
 * @param int $timestamp optional unix timestamp in UTC to format
 * @param bool $addoffset add user timezone offset (default true)
 */
function xarLocaleGetFormattedTime($length = 'short',$timestamp = null, $addoffset = true)
{
    return xarLocaleGetFormattedDate($length.'toly', $timestamp, $addoffset);

}

/**
 *  Wrapper to xarLocaleFormatDate without timezone offset
 */
function xarLocaleFormatUTCDate($format = null, $time = null, $addoffset = false)
{
    if(!isset($time)) {
        $time = time();
    }

    // pass this to the regular function, but without using the timezone offset here
    return xarLocaleFormatDate($format,$time,$addoffset);
}

/**
 * Format a date/time according to the current locale (and/or user's preferences)
 *
 * @access public
 * @param time mixed timestamp or date string (default now)
 * @param format strftime() format to use (TODO: default locale-dependent or configurable ?)
 * @param addoffset bool add user timezone offset (default true)
 * @return date string
 *
 */
function xarLocaleFormatDate($format = null, $timestamp = null, $addoffset = true)
{
    // CHECKME: should we default to current time only when timestamp is not set at all ?
    //if (!isset($timestamp)) {
    if (empty($timestamp)) {
        // starting with PHP 5.1.0, strtotime returns false instead of -1
        if (isset($timestamp) && $timestamp === false) {
            return '';
        }
        if ($addoffset) {
            $timestamp = xarMLS_userTime();
        } else {
            $timestamp = time();
        }
    } elseif ($timestamp >= 0) {
        if ($addoffset) {
            // adjust for the user's timezone offset
            $timestamp += xarMLS_userOffset($timestamp) * 3600;
        }
    } else {
        // invalid dates < 0 (e.g. from strtotime) return an empty date string
        return '';
    }
    return xarMLS_strftime($format,$timestamp);
}

/**
 *  Used in place of strftime() for locale translation.
 *  This function uses gmstrftime() so it should be passed
 *  a timestamp that has been modified for the user's current
 *  timezone setting.
 *
 *  @access protected
 *  @param string $format valid format params from strftime() function\
 *  @param int $timestamp optional unix timestamp to translate
 *  @return string datetime string with locale translations
 *
 *  // supported strftime() format rules
 *  %a - abbreviated weekday name according to the current locale
 *  %A - full weekday name according to the current locale
 *  %b - abbreviated month name according to the current locale
 *  %B - full month name according to the current locale
 *  %c - preferred date and time representation for the current locale
 *  %D - same as %m/%d/%y (abbreviated date according to locale)
 *  %h - same as %b
 *  %p - either `am' or `pm' according to the given time value, or the corresponding strings for the current locale
 *  %r - time in a.m. and p.m. notation
 *  %R - time in 24 hour notation (for windows compatibility)
 *  %T - current time, equal to %H:%M:%S (for windows compatibility)
 *  %x - preferred date representation for the current locale without the time (same at %D)
 *  %X - preferred time representation for the current locale without the date
 *  %e - day of the month as a decimal number, a single digit is NOT preceded by a space (range ' 1' to '31')
 *
 *  @todo unsupported strftime() format rules
 *  %Z - time zone or name or abbreviation - we should use the user or site's info for this
 *  %z - time zone or name or abbreviation - we should use the user or site's info for this
 */
function xarMLS_strftime($format=null,$timestamp=null)
{
    // if we don't have a timestamp, get the user's current time
    if(!isset($timestamp)) {
        $timestamp = xarMLS_userTime();
    } elseif ($timestamp < 0) {
        // invalid dates < 0 (e.g. from strtotime) return an empty date string
        return '';
    } elseif ($timestamp === false) {
        // starting with PHP 5.1.0, strtotime returns false instead of -1
        return '';
    }

    // we need to get the correct timestamp format if we do not have one
    if(!isset($format)) {
         $format = '%c';
     }

    // the locale data should already be a static var in the main loader script
    // so we no longer need to make it a static in this function
    $localeData = xarMLSLoadLocaleData();

    // parse the format string
    preg_match_all('/%[a-z]/i',$format,$modifiers);
    // replace supported format rules
    foreach($modifiers[0] as $modifier) {
        switch($modifier) {
            case '%a' :
                // figure out what weekday it is
                $w = (int) gmstrftime('%w',$timestamp);
                // increment because the locales start at 1
                $w++;
                // replace the weekeday in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/weekdays/$w/short"],$format);
                // clean up
                unset($w);
                break;

            case '%A' :
                // figure out what weekday it is
                $w = (int) gmstrftime('%w',$timestamp);
                // increment because the locales start at 1
                $w++;
                // replace the weekeday in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/weekdays/$w/full"],$format);
                // clean up
                unset($w);
                break;

            case '%b' :
            case '%h' :
                // figure out what month it is
                $m = (int) gmstrftime('%m',$timestamp);
                // replace the month in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/months/$m/short"],$format);
                // clean up
                unset($m);
                break;

            case '%B' :
                // figure out what month it is
                $m = (int) gmstrftime('%m',$timestamp);
                // replace the month in the format string
                $format = str_replace($modifier,$localeData["/dateSymbols/months/$m/full"],$format);
                // clean up
                unset($m);
                break;

            case '%c' :
                // TODO: we want to display the user or site's timezone not the servers
                $fdate = xarLocaleGetFormattedUTCDate('medium',$timestamp);
                $ftime = xarLocaleGetFormattedUTCTime('medium',$timestamp);
                $format = str_replace($modifier,$fdate.' '.$ftime,$format);
                break;

            case '%D' :
            case '%x' :
                $format = str_replace($modifier,xarLocaleGetFormattedUTCDate('short',$timestamp),$format);
                break;

            case '%e' :
                // implement %e for windows - grab the day number and remove the preceding zero
                $e = sprintf('%1d',gmstrftime('%d',$timestamp));
                // pad with a space if necessary
                //if(strlen($e) < 2) {
                //    $e = '&nbsp;'.$e;
                //}
                $format = str_replace($modifier,$e,$format);
                break;

            case '%r' :
                // recursively call the xarMLS_strftime function
                $format = str_replace($modifier,xarMLS_strftime('%I:%M %p',$timestamp),$format);
                break;

            case '%R' :
                // 24 hour time for windows compatibility
                $format = str_replace($modifier,gmstrftime('%H:%M',$timestamp),$format);
                break;

            case '%T' :
                // current time for windows compatibility
                $format = str_replace($modifier,gmstrftime('%H:%M:%S',$timestamp),$format);
                break;

            case '%X' :
                // TODO: we want to display the user or site's timezone not the servers
                $format = str_replace($modifier,xarLocaleGetFormattedUTCTime('short',$timestamp),$format);
                break;

            case '%Z' :
            case '%z' :
                $user_offset = (string) xarMLS_userOffset($timestamp);
                // check to see if this is a negative or positive offset
                $f_offset = strstr($user_offset,'-')  ? '-' : '+';
                $user_offset = str_replace('-','',$user_offset); // replace the - if it exists
                if(strpos($user_offset,'.')) {
                   $fragments = explode('.',$user_offset);
                   // extract hours - AZ
                   if( (int) $fragments[0] < 10) {
                      $f_offset_hours = "0{$fragments[0]}";
                   } else {
                      $f_offset_hours = "{$fragments[0]}";
                   }
                   // extract minutes- AZ
                   $f_offset_minutes = ('.'.$fragments[1])*60;
                   if( (int) $f_offset_minutes < 10) {
                      $f_offset_minutes = "0{$f_offset_minutes}";
                   } else {
                      $f_offset_minutes = "{$f_offset_minutes}";
                   }
                   // Bug 5211, Code of AZ: beautify display with common ":" delimiter
                   $f_offset .= sprintf('%02d',$f_offset_hours).':'.$f_offset_minutes;
                } elseif( (int) $user_offset < 10) {
                    $f_offset .= "0{$user_offset}:00";
                } else {
                    $f_offset .= "{$user_offset}:00";
                }

                $format = str_replace($modifier,$f_offset,$format);
                break;

            case '%p' :
                // figure out if it's am or pm
                $h = (int)gmstrftime('%H',$timestamp);
                if($h > 11) {
                    // replace with PM string
                    $format = str_replace($modifier,$localeData["/dateSymbols/pm"],$format);
                } else {
                    // replace with AM string
                    $format = str_replace($modifier,$localeData["/dateSymbols/am"],$format);
                }
                break;
        }
    }
    // convert the rest of the format string and return it
    return gmstrftime($format,$timestamp);
}

// MLS CLASSES

/**
 * This class loads a valid locale descriptor XML file and returns its content
 * in the form of a locale data array
 *
 * @subpackage multilanguage
 */
class xarMLS__LocaleDataLoader extends xarObject
{
    public $curData;
    public $curPath;

    public $parser;

    public $localeData;

    public $attribsStack = array();

    public $tmpVars;

    function load($locale)
    {
        $fileName = sys::varpath() . "/locales/$locale/locale.xml";
        if (!file_exists($fileName)) {
            return false;
        }

        if(filesize($fileName) == 0 ) {
            return false;
        }

        $this->tmpVars = array();

        $this->curData = '';
        $this->curPath = '';
        $this->localeData = array();

        // TRICK: <marco> Since this xml parser sucks, we obviously use utf-8 for utf-8 charset
        // and iso-8859-1 for other charsets, even if they're not single byte.
        // The only important thing here is to split utf-8 from other charsets.
        $charset = xarMLSGetCharsetFromLocale($locale);
        // FIXME: <marco> try, re-try and re-re-try this!
        if ($charset == 'utf-8') {
            $this->parser = xml_parser_create('utf-8');
        } else {
            $this->parser = xml_parser_create('iso-8859-1');
        }
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->parser, "beginElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        if (!($fp = fopen($fileName, 'r'))) {
            return false;
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                throw new XMLParseException(array($fileName,$line,$errstr));
            }
        }

        xml_parser_free($this->parser);
        return true;
    }

    function getLocaleData()
    {
        return $this->localeData;
    }

    function beginElement($parser, $tag, $attribs)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        $this->attribsStack[] = $attribs;
        if (isset($this->tmpVars['calledOnce'])) {
            $this->curPath .= '/'.$tag;
        } else {
            // Avoid to get prefixed the /description to path
            $this->tmpVars['calledOnce'] = true;
        }
    }

    function endElement($parser, $tag)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        $attribs = array_pop($this->attribsStack);
        $handler = $tag.'TagHandler';
        if (method_exists($this, $handler)) {
            list($new_path, $value) = $this->$handler($this->curPath, $attribs, $this->curData);
        } else {
            $value = $this->curData;
            $new_path = $this->curPath;
        }
        if (is_array($value)) {
            foreach ($value as $add_path => $real_value) {
                $this->localeData[$new_path.'/'.$add_path] = $real_value;
            }
        } else {
            $this->localeData[$new_path] = $value;
        }
        $this->curPath = substr($this->curPath, 0, (-1 * strlen($tag)) - 1);

        $this->curData = '';
    }

    function characterData($parser, $data)
    {
        // FIXME: <marco> consider to replace \n,\r with ''
        $this->curData .= trim($data);
    }

    function maximumTagHandler($path, $attribs, $content)
    {
        return array($path, (int) $content);
    }

    function minimumTagHandler($path, $attribs, $content)
    {
        return array($path, (int) $content);
    }
    /**
     * @return array
     */
    function groupingSizeTagHandler($path, $attribs, $content)
    {
        return array($path, (int) $content);
    }

    function isDecimalSeparatorAlwaysShownTagHandler($path, $attribs, $content)
    {
        if ($content == 'true') {
            $value = true;
        } else {
            $value = false;
        }
        return array($path, $value);
    }
    /**
     * @return array
     */
    function monthTagHandler($path, $attribs, $content)
    {
        if (isset($this->tmpVars['monthNum'])) {
            $monthNum = $this->tmpVars['monthNum'];
        } else {
            $monthNum = 1;
        }
        $this->tmpVars['monthNum'] = $monthNum + 1;
        $path = substr($path, 0, -6); // Strip the /month at the end
        $value = array($monthNum.'/full' => $attribs['full'],
                       $monthNum.'/short' => $attribs['short']);
        return array($path, $value);
    }
    /**
     * @return array
     */
    function weekdayTagHandler($path, $attribs, $content)
    {
        if (isset($this->tmpVars['weekdayNum'])) {
            $weekdayNum = $this->tmpVars['weekdayNum'];
        } else {
            $weekdayNum = 1;
        }
        $this->tmpVars['weekdayNum'] = $weekdayNum + 1;
        $path = substr($path, 0, -8); // Strip the /weekday at the end
        $value = array($weekdayNum.'/full' => $attribs['full'],
                       $weekdayNum.'/short' => $attribs['short']);
        return array($path, $value);
    }

}

?>
