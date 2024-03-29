<?php
/**
 * Variable utilities
 *
 * @package Xarigami core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Exceptions for this subsystem
 *
 */

/**
 * Interface declaration for classes dealing with sets of variables
 *
 * @todo this interface is simplistic, it probably needs more
 */
interface IxarVars
{
    static function get       ($scope, $name, $except=false);
    static function set       ($scope, $name, $value);
    static function delete    ($scope, $name);
}

/**
 * Base class for variable handling in core
 *
 * @package variables
 * @author Marcel van der Boom <mrb@hsdev.com>
 **/
class xarVars extends xarObject
{
    protected static $_cache = NULL;
    protected static $_iscached = NULL;

    /**
     * Initialise the variable handling options
     *
     * Sets up allowable html and htmlentities options
     *
     *
     * @param args array
     * @return boolean
     * @todo <mrb> remove the two settings allowablehtml and fixhtmlentities
     */
    public static function init($args = NULL, $whattoload = NULL)
    {
        return TRUE;
    }

    public static function setCache(&$collection, &$iscached)
    {
        if (!isset(self::$_cache) && !array(self::$_cache)) self::$_cache = &$collection;
        if (!isset(self::$_iscached) && !array(self::$_iscached)) self::$_iscached = &$iscached;
    }
}

/**
 * Variables package defines
 */
define('XARVAR_ALLOW_NO_ATTRIBS', 1);
define('XARVAR_ALLOW', 2);

define('XARVAR_GET_OR_POST',  0);
define('XARVAR_GET_ONLY',     2);
define('XARVAR_POST_ONLY',    4);

define('XARVAR_NOT_REQUIRED', 64);
define('XARVAR_DONT_SET',     128);
define('XARVAR_DONT_REUSE',   256);

define('XARVAR_PREP_FOR_NOTHING', 0);
define('XARVAR_PREP_FOR_DISPLAY', 1);
define('XARVAR_PREP_FOR_HTML',    2);
define('XARVAR_PREP_FOR_STORE',   4);
define('XARVAR_PREP_TRIM',        8);
define('XARVAR_VAL_RESULT',      16);


/**
 * Fetches and validates in a Batch.
 *
 *   if (!xarVarFetch('reassign', 'checkbox',  $reassign, false, XARVAR_NOT_REQUIRED)) return;
 *   if (!xarVarFetch('repeat',   'int:1:100', $repeat,   1,     XARVAR_NOT_REQUIRED)) return;
 *
 *  Can be done thru xarVarBatchFetch with:
 *
 *  $result = xarVarBatchFetch(array('reassign','checkbox', 'reassign', false, XARVAR_NOT_REQUIRED),
 *                             array('repeat', 'int:1:100', 'repeat'));
 *
 * Notice that i didnt use XARVAR_NOT_REQUIRED because xarVarBatchFetch will trap the
 * thrown exceptions for me in the result array, thus allowing me to get this easily
 * back to the GUI warning the user that the variable didn't validate and for what reason
 *
 * if ($result['no_errors']) {
 *     //No Errors!
 *     $results[variable name]['value'] holds the inputs with the apropriate types
 * } else {
 *     //Errors Found, go back to the GUI and use the $result to display the errors
 *     // in the right place
 *     $results[variable name]['value'] holds the input values
 *     $results[variable name]['error'] holds the Error Message ('' in case of none)
 *  }
 *
 *
 * @access public
 * @param arrays The arrays storing information equivalent to the xarVarFetch interface
 * @return array With the respective exceptions in case of failure
 */
function xarVarBatchFetch()
{

    $batch = func_get_args();

    $result_array = array();
    $no_errors    = true;

    foreach ($batch as $line) {
        $result_array[$line[2]] = array();
        try {
            $result = xarVarFetch($line[0], $line[1], $result_array[$line[2]]['value'], isset($line[3])?$line[3]:NULL, isset($line[4])?$line[4]:XARVAR_GET_OR_POST);
            $result_array[$line[2]]['error'] = '';
        } catch (ValidationExceptions $e) { // Only catch validation exceptions, the rest should be thrown
            //Records the error presented in the given input variable
            $result_array[$line[2]]['error'] = $e->getMessage();
            //Mark that we've got an error
            $no_errors = false;
        }
    }

    //Chose this key name to avoid clashes and make it easy to go on if there is no
    //errors present in the Fetched variables.
    $result_array['no_errors'] = $no_errors;

    return $result_array;
}

/**
 * Fetches the $name variable from input variables and validates it by applying the $validation rules.
 *
 * 1st try to use the variable provided, if this is not set (Or the XARVAR_DONT_REUSE flag is used)
 * then try to get the variable from the input (POST/GET methods for now)
 *
 * Then tries to validate the variable thru xarVarValidate.
 *
 * See xarVarValidate for details about nature of $validation.
 * After the call the $value parameter passed by reference is set to the variable value converted to the proper type
 * according to the validation applied.
 *
 * The $defaultValue provides a default value that is returned when the variable is not present or doesn't validate
 * correctly.
 *
 * The $flag parameter is a bitmask between the following constants:
 * XARVAR_GET_OR_POST  - fetch from GET or POST variables
 * XARVAR_GET_ONLY     - fetch from GET variables only
 * XARVAR_POST_ONLY    - fetch from POST variables only
 * XARVAR_NOT_REQUIRED - allow the variable to be empty/not set, dont raise exception if it is
 * XARVAR_DONT_REUSE   - if there is an existing value, do not reuse it
 * XARVAR_DONT_SET     - if there is an existing value, use it
 *
 * You can force to get the variable only from GET parameters or POST parameters by setting the $flag parameter
 * to one of XARVAR_GET_ONLY or XARVAR_POST_ONLY.
 *
 * You can force xarVarFetch not to reuse the variable by setting
 * the $flag parameter to XARVAR_DON_REUSE.
 *
 * By default $flag is XARVAR_GET_OR_POST which means tha xarVarFetch will lookup both GET and POST parameters and
 * that if the variable is not present or doesn't validate correctly an exception will be raised.
 *
 * If XARVAR_NOT_REQUIRED is set the function will always return TRUE, even if the
 * data is invalid. Using XARVAR_NOT_REQUIRED | XARVAR_VAL_RESULT
 * gives back the validation result as return value.
 *
 * The $prep flag will prepare $value by passing it to one of the following:
 *   XARVAR_PREP_FOR_NOTHING:    no prep (default)
 *   XARVAR_PREP_FOR_DISPLAY:    xarVarPrepForDisplay($value)
 *   XARVAR_PREP_FOR_HTML:       xarVarPrepHTMLDisplay($value)
 *
 * @access public
 * @param name string the variable name
 * @param validation string the validation to be performed
 * @param value mixed contains the converted value of fetched variable
 * @param defaultValue mixed the default value
 * @param flags integer bitmask which modify the behaviour of function
 * @param prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
 * @return mixed
 * @todo  get rid of the explicit value of XARVAR_GET_OR_POST, use the bitmas (i.e. GET_OR_POST = GET + POST)
 * @todo  make dont_set and dont_reuse are too similar (conceptually) which make the code below confusing [phpdoc above implies REUSE is the default]
 * @todo  re-evaluate the prepping, prepforstore is deprecated for example, prep for display and prep for html are partially exclusive
**/
function xarVarFetch($name, $validation, &$value, $defaultValue = NULL, $flags = XARVAR_GET_OR_POST, $prep = XARVAR_PREP_FOR_NOTHING)
{
    assert(is_int($flags), new BadParameterException('flags', 'Flags passed to xarVarFetch need to be numeric'));
    assert(empty($name) || preg_match("/^[a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_-\x7f-\xff]*$/", $name), new BadParameterException('name', 'Variable name is empty or invalid'));

    $allowOnlyMethod = null;
    if ($flags & XARVAR_GET_ONLY) $allowOnlyMethod = 'GET';
    if ($flags & XARVAR_POST_ONLY) $allowOnlyMethod = 'POST';

    // XARVAR_DONT_SET does not set $value, if there already is one
    // This allows us to have a extract($args) before the xarVarFetch and still run
    // the variables thru the tests here.
    $oldValue = null;
    if (isset($value) && $flags & XARVAR_DONT_SET) $oldValue = $value;

    // XARVAR_DONT_REUSE fetches the variable, regardless
    // FIXME: this flag doesn't seem to work !?
    // mrb: what doesn't work then? seems ok within the given workings
    // --------v  this is kinda confusing though, especially when dont_set is used as flag.
    if (!isset($value) || $value === NULL || ($flags & XARVAR_DONT_REUSE)) {
        $value = xarRequest::getVar($name, $allowOnlyMethod);
    }

    // Suppress validation warnings when dont_set, not_required or a default value is specified
    $suppress = (($flags & XARVAR_DONT_SET) || ($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue));
    // Validate the $value given
    $validated = xarVarValidate($validation, $value, $suppress, $name);

    if (!$validated) {
        // The value does not validate
        $value = null; // we first make sure that this is what we expect to return

        // Perhaps the default or old can be returned?
        if (($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue)) {
            // CHECKME:  even for the XARVAR_DONT_SET flag !?
            // if you set a non-null default value, assume you want to use it here
            $value = $defaultValue;
        } elseif (($flags & XARVAR_DONT_SET) && isset($oldValue) && xarVarValidate($validation, $oldValue, $suppress)) {
            // with XARVAR_DONT_SET, make sure we don't pass invalid old values back either
            $value = $oldValue;
            // XARVAR_VAL_RESULT returns the validation result
            if ($flags & XARVAR_VAL_RESULT) {
                return false;
            }
        }
    } else {
        // Value is ok, handle preparation of that value
        if ($prep & XARVAR_PREP_FOR_DISPLAY) $value = xarVarPrepForDisplay($value);
        if ($prep & XARVAR_PREP_FOR_HTML)    $value = xarVarPrepHTMLDisplay($value);

        // TODO: this is used nowhere, plus it introduces a db connection here which is of no use
        if ($prep & XARVAR_PREP_FOR_STORE) {
            $dbconn = xarDB::$dbconn;
            $value = $dbconn->qstr($value);
        }

        if ($prep & XARVAR_PREP_TRIM) $value = trim($value);
    }
    return true;
}

/**
 * Validates a variable performing the $validation test type on $subject.
 *
 * The $validation parameter could be a string, in this case the
 * supported validation types are very basilar, they are the following:
 *
 * 'id' matches a positive integer (0 excluded)
 *
 * 'int:<min val>:<max val>' matches an integer between <min val> and <max val> (included), if <min val>
 *                           is not present no lower bound check is performed, the same applies to <max val>
 *
 * 'float:<min val>:<max val>' matches a floating point number between <min val> and <max val> (included), if <min val>
 *                             is not present no lower bound check is performed, the same applies to <max val>
 *
 * 'bool' matches a string that can be 'true' or 'false'
 *
 * 'str:<min len>:<max len>' matches a string which has a lenght between <min len> and <max len>, if <min len>
 *                           is omitted no control is done on mininum lenght, the same applies to <max len>
 *
 * 'html:<level>' validates the subject by searching unallowed html tags, allowed tags are defined by specifying <level>
 *                that could be one of restricted, basic, enhanced, admin. This last level is not configurable and allows
 *                every tag
 *
 * 'array:<min elements>:<max elements>' validates if the subject is an array with the minimum and maximum
 *                                       of elements specified
 *
 * 'list' validates if the subject is a list
 * 'list: *other validation*' validates if the subject is an array, and if every element of the array
 *                            validate in the *other validation*
 *                          Example: xarVarValidate('list:str:1:20', $strings_array);
 *
 * 'enum' validates if the subject is any of the parameters
 *                  Example: xarVarValidate('enum:apple:orange:strawberry', $options);
 *
 * After the validation is performed, $convValue (passed by reference) is assigned to $subject converted the proper type.
 * Please note that conversions from string to integer or float are done by using the PHP built-in cast conversions,
 * refer to this page for the details:
 * http://www.php.net/manual/en/language.types.string.html#language.types.string.conversion
 *
 * The $validation parameter can be any of the implemented functions in html/modules/variable/validations/
 *
 * @access public
 * @param validation mixed the validation to be performed
 * @param subject string the subject on which the validation must be performed, will be where the validated value will be returned
 * @throws EmptyParameterException
 * @return boolean true if the $subject validates correctly, false otherwise
 */
function xarVarValidate($validation, &$subject, $suppress = false, $name='')
{
    $valParams = explode(':', $validation);
    $valtype = strtolower(array_shift($valParams));

    if (empty($valtype)) throw new EmptyParameterException('validation type');

    if (!class_exists('ValueValidations')) sys::import("xarigami.xarValidations");

    $val = ValueValidations::get($valtype);

    try {
            $result = $val->validate($subject, $valParams);
            return $result;
    } catch (ValidationExceptions $e) {
        // If a validation exception occurred, we can optionally suppress it
        if(!$suppress) {
            if($name == '') $name = xarML('Unknown');
            throw new VariableValidationException(array($name,$subject,$e->getMessage()));
        }
    } catch(Exception $e) {
        // But not the others (this part is redundant)
        throw $e;
    }
}

/*
 * Functions providing variable caching (within a single page request)
 * Example :
 * if (xarCoreCache::isCached('MyCache', 'myvar')) {
 *     $var = xarCoreCache::getCached('MyCache', 'myvar');
 * }
 * xarCoreCache::setCached('MyCache', 'myvar', 'this value');
 * xarCoreCache::delCached('MyCache', 'myvar');
 * xarCoreCache::flushCached('MyCache');
 */

/**@+
 * Wrapper functions for var caching as in Xaraya 1 API
 * See the documentation of protected xarCoreCache::*Cached for details
 * @see xarCore
 */
function xarVarIsCached($scope,  $name)         { return xarCoreCache::isCached($scope, $name);         }
function xarVarGetCached($scope, $name)         { return xarCoreCache::getCached($scope, $name);        }
function xarVarSetCached($scope, $name, $value) { return xarCoreCache::setCached($scope, $name, $value);}
function xarVarDelCached($scope, $name)         { return xarCoreCache::delCached($scope, $name);        }
function xarVarFlushCached($scope)              { return xarCoreCache::flushCached($scope);             }

/**@-*/


/**
 * Stripslashes on multidimensional arrays.
 *
 * Used in conjunction with xarVarCleanFromInput
 *
 * @access protected
 * @param &var any variables or arrays to be stripslashed
 */
function xarVar_stripSlashes(&$var)
{
    if(!is_array($var)) {
        $var = stripslashes($var);
    } else {
        array_walk($var,'xarVar_stripSlashes');
    }
}

function xarVar_addSlashes($var)
{
    return str_replace(array("\\",'"'), array("\\\\",'\"'), $var);
}

/**
 * Get allowed tags based on $level
 *
 * @access private
 * @static restricted array
 * @static basic array
 * @static enhanced array
 * @param level string
 * @return array
 */

function xarVar__getAllowedTags($level)
{
    // Get the allowed HTML from the config var.  At some
    // point this will be replaced by retrieving the
    // allowed HTML from the HTML module.
    $allowedHTML = array();
    foreach (xarConfigGetVar('Site.Core.AllowableHTML') as $k=>$v) {
        if ($v) {
            $allowedHTML[] = $k;
        }
    }

    return $allowedHTML;
}


/**
 * Changes one variable from one context to another
 *
 * @access public
 * @param string The string to be Converted
 * @param sourceContext The name of the module
 * @param targetContext The name of the module
 * @return string the string in the new context
 * @throws EMPTY_PARAM
 */

function xarVarTransform ($string, $sourceContext, $targetContext)
{

    if (empty($sourceContext)) throw new EmptyParameterException('sourceContext');
    if (empty($targetContext)) throw new EmptyParameterException('targetContext');
    $transform_type = $sourceContext.'_to_'.$targetContext;
    $function_name = xarVarLoad ('transforms', $transform_type);

    if (!$function_name) {return;}

    return $function_name ($string);
}

/**
 * Loads variable's drivers. Should be changed to module space latter on.
 *
 * @access private
 * @param string The drivers directory
 * @param filename The name file to be used
 * @return string the function anme
 * @throws BAD_PARAM
 */

function xarVarLoad ($includes_type, $filename)
{

    $filename = xarVarPrepForOS($filename);

    //$function_file = dirname(__FILE__).'/'.$includes_type.'/'.$filename.'.php';
    $function_name = 'variable_'.$includes_type.'_'.$filename;

    if (!function_exists($function_name)) {
        sys::import("xarigami.$includes_type.$filename");
    }

    if (!function_exists($function_name)) {
        // Raise an exception
        $msg = 'The #(1) type \'#(2)\' could not be found.';
        $params = array($includes_type, $filename);
        throw new BadParameterException($params,$msg);
    }

    return $function_name;
}


/**
 * Ready user output
 *
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as entered. This function uses the PHP function htmlspecialchars.
 * Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepForDisplay()
{
    $resarray = array();
    foreach (func_get_args() as $var) {
        // Prepare var
        $var = htmlspecialchars($var);
        // Add to array
        array_push($resarray, $var);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

/**
 * Prepare string for use within a single quoted JS string
 *
 * When called with one string, it prepares that. With more
 * parameters it returns an array each parameter prepared.
 * Escapes single quote and backslash, so the value can be used
 * within JS code string.
 *
 * @access public
 * @return mixed prepared string if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepForJS()
{
    $resarray = array();
    foreach (func_get_args() as $var) {
        // Prepare var
        $var = str_replace("\\", "\\\\", $var);
        $var = str_replace("'", "\\'", $var);
        // Add to array
        array_push($resarray, $var);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

/**
 * Ready HTML output
 *
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as expected, except for allowed HTML tags which
 * are allowed through. Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarVarPrepHTMLDisplay()
{
    static $allowedtags = NULL;

    if (!isset($allowedtags)) {
        $allowedHTML = array();
        foreach(xarConfigVars::get(null, 'Site.Core.AllowableHTML', true)  as $k=>$v) {
            if ($k == '!--') {
                if ($v <> 0) {
                    $allowedHTML[] = "$k.*?--";
                }
            } else {
                switch($v) {
                    case 0:
                        break;
                    case 1:
                        $allowedHTML[] = "/?$k\s*/?";
                        break;
                    case 2:
                        $allowedHTML[] = "/?$k(\s+[^>]*)?/?";
                        break;
                }
            }
        }
        if (count($allowedHTML) > 0) {
            $allowedtags = '~<(' . join('|',$allowedHTML) . ')>~is';
        } else {
            $allowedtags = '';
        }
    }

    $resarray = array();
    foreach (func_get_args() as $var) {
        // Preparse var to mark the HTML that we want
        if (!empty($allowedtags))
            $var = preg_replace($allowedtags, "\022\\1\024", $var);

        // Prepare var
        $var = htmlspecialchars($var);

        // Fix the HTML that we want
/*
        $var = preg_replace('/\022([^\024]*)\024/e',
                               "'<' . strtr('\\1',
                                            array('&gt;' => '>',
                                                  '&lt;' => '<',
                                                  '&quot;' => '\"',
                                                  '&amp;' => '&'))
                               . '>';", $var);
*/
        $var = preg_replace_callback('/\022([^\024]*)\024/',
                                     'xarVarPrepHTMLDisplay__callback',
                                     $var);

        // Fix entities if required
        if (xarConfigVars::get(null, 'Site.Core.FixHTMLEntities', true)) {
            $var = preg_replace('/&amp;([a-z#0-9]+);/i', "&\\1;", $var);
        }

        // Add to array
        array_push($resarray, $var);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

function xarVarPrepHTMLDisplay__callback($matches)
{
    return '<' . strtr($matches[1],
                       array('&gt;' => '>',
                             '&lt;' => '<',
                             '&quot;' => '"',
                             '&amp;' => '&'))
           . '>';
}

/**
 * Ready obfuscated e-mail output
 *
 * Gets a variable, cleaning it up such that e-mail addresses are
 * slightly obfuscated against e-mail harvesters.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepEmailDisplay()
{
/*
    // This search and replace finds the text 'x@y' and replaces
    // it with HTML entities, this provides protection against
    // email harvesters
    //
    // Note that the use of \024 and \022 are needed to ensure that
    // this does not break HTML tags that might be around either
    // the username or the domain name
    static $search = array('/([^\024])@([^\022])/se');

    static $replace = array('"&#" .
                            sprintf("%03d", ord("\\1")) .
                            ";&#064;&#" .
                            sprintf("%03d", ord("\\2")) . ";";');

*/
    $resarray = array();
    foreach (func_get_args() as $var) {
        // Prepare var
//        $var = preg_replace($search, $replace, $var);
        $var = strtr($var,array('@' => '&#064;'));
        // Add to array
        array_push($resarray, $var);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

/**
 * Ready operating system output
 *
 * Gets a variable, cleaning it up such that any attempts
 * to access files outside of the scope of the Xarigami
 * system is not allowed. Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @todo This makes no sense to me anymore (mrb)
 */
function xarVarPrepForOS()
{
    static $special_characters = array(':'  => ' ',   // for things like c:/file.txt?
                                       //'/'  => ' ', // this makes vars with relative paths unusable (cfr. bug 5559 )
                                       '\\' => ' ',   // for unc paths?
                                       '..' => ' ',
                                       '?'  => ' ',   // why?
                                       '*'  => ' ');

    $args = func_get_args();

    foreach ($args as $key => $var) {
        // Remove out bad characters
        $args[$key] = strtr($var, $special_characters);
    }


    // Return vars
    if (func_num_args() == 1) {
        return $args[0];
    } else {
        return $args;
    }
}
?>
