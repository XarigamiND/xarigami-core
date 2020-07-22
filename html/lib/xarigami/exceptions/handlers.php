<?php
/**
 * Exception handlers class
 *
 * @package exceptions
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 *
 * @subpackage exceptions
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Marcel van der Boom <marcel@hsdev.com>
 * @author Xarigami Team
**/

sys::import('xarigami.variables.system');

interface IExceptionHandlers
{
    public static function defaulthandler(Exception $e);
    public static function phperrors($errorType, $errorString, $file, $line, array $errorContext=array());
}

class ExceptionHandlers extends xarObject implements IExceptionHandlers
{
    private static $data = array();
    static $debuggroup=0;

    /**
     * Default Exception handler for unhandled exceptions
     *
     * This handler is called when an exception is raised and otherwise unhandled
     * Execution stops directly after this handler runs. (or any exception handler for that matter)
     * The base exception object is documented here: http://www.php.net/manual/en/language.exceptions.php
     * but we dont want to instantiate that directly, but rather one of our derived classes.
     * We define this handler here, because it needs to be defined before set_exception_handler
     *
     * @param  Exception $exception The exception object
     * @throws Exception
     * @todo Get rid of the redundant parts
     * @return void
     */
    public static function defaulthandler(Exception $e)
    {
        $isdev = TRUE;
        $opmode = 'developer';
        if (!sys::isInstall()) {
            try {
                if (method_exists('xarModVars','get') && class_exists('xarRoles')) {
                    $debuggroup = xarModVars::get('privileges','debuggroup');
                    $userrole = new xarRoles();
                    $user =$userrole->getRole(xarSessionGetVar('uid'));
                    $role= new xarRoles();
                    $parent = $role->getRole($debuggroup);
                    $isdev = xarIsParent($parent->uname,$user->uname);
                }
                $opmode = xarSystemVars::get(null,'Operation.Mode',true)?xarSystemVars::get(null,'Operation.Mode',true):'developer';

            } catch (Exception $e) {
                //no exception, just we use the original operation mode defined
            }
        }
        // Make an attempt to render the page, hoping we have everything in place still
        try {

            // Try to get the full path location out of the trace
            self::$data = array_merge( self::$data,
                                  array(
                                  'major'     => 'MAJOR TBD (Code was: '. $e->getCode().')',
                                  'type'      => get_class($e), // consider stripping of 'Exception'
                                  'title'     => get_class($e) . ' ['.$e->getCode().'] was raised (native)',
                                  'short'     => htmlspecialchars($e->getMessage(),ENT_COMPAT,'UTF-8',FALSE),
                                  'line'      => $e->getLine(),
                                  'file'      => $e->getFile(),
                                  'trace'     => $e->getTraceAsString()
                                ));
            // If we have em, use em
            $template = isset($template)? $template:'';

            $typename = strtolower(str_replace('Exception','',get_class($e)));

            if(function_exists('xarTplGetThemeDir') && function_exists('xarTplFile')) {

                $theme_dir = xarTplGetThemeDir();

                //we will check for custom exception message templates

                //check for forbidden operation exception
                if ($typename =='forbiddenoperation') {
                    if (!headers_sent()) {
                        header('HTTP/1.0 403 Forbidden',TRUE);
                    }
                    xarTplSetPageTitle('403 Forbidden');
                    $template=!empty($template) ? $template :'forbiddenoperation';

                //check for service unavailable
                }elseif ($typename == 'serviceunavailable' || $typename == 'adodb_') {
                    if (!headers_sent()) {
                        header('HTTP/1.0 503 Service Unavailable', TRUE);
                    }
                    //is it a purposeful site disabled message
                    $lockdata = @unserialize(xarModGetVar('roles','lockdata'));
                    if ($lockdata && isset($lockdata['disabled']) && $lockdata['disabled'] ==1)
                    {
                        xarTplSetPageTitle('503 Service Unavailable');
                        $template=!empty($template) ? $template :'serviceunavailable';
                    }
                }
                //template order
                // 1. Passed in template
                // 2. Specific error template
                // 3. Generic template
                // Also check for theme dir


                if(file_exists($theme_dir . '/modules/base/message-' . $template. '.xt')) {
                    $templatefile = $theme_dir . '/modules/base/message-' . $template . '.xt';
                }elseif(file_exists(sys::code().'modules/base/xartemplates/message-' . $template . '.xd')) {
                    $templatefile = sys::code().'modules/base/xartemplates/message-' . $template . '.xd';
                }elseif(file_exists($theme_dir .'/modules/base/message-systemerror.xt')) {
                     $templatefile = $theme_dir .'/modules/base/message-systemerror.xt';
                }else {
                    $templatefile = sys::code().'modules/base/xartemplates/message-systemerror.xd';
                }

               $msg = xarTplFile($templatefile, self::$data);
                echo xarTpl_renderPage($msg);

            //we do not have certain functions available to provide a nice layout
            }elseif ($typename == 'serviceunavailable' || $typename == 'adodb_') {
                        // it could be a real service error due to connection probs or overload
                $page503 = xarSystemVars::get(sys::CONFIG, 'Exception.503');
                $default503  = sys::varpath().'/messaging/errors/503.html';
                $cfunction = '';
                if (file_exists($page503)) {
                    $includefile = $page503;
                } elseif (file_exists($default503)) {
                    $includefile = $default503;
                } else {
                    $includefile = '';
                }

                ob_start();
                if (!empty($includefile)) {
                    include($includefile);
                }
                //prepare a message for developers
                $hint = '';
                if (method_exists($e,'getHint')) {
                    $hint = $e->getHint();
                }

                if ($opmode == 'developer' || $isdev) {
                    echo "Mode     : ".$opmode."<br />";
                    if (!empty($hint)) {
                     echo 'Hint: '.$hint."\n";
                    }
                    echo ExceptionHandlers::bone($e);
                }
                $output = ob_get_clean();
                echo $output;
            } else {
                if (($opmode == 'developer') || $isdev) {
                    // Rethrow it, we cant handle it.
                    throw $e;
                } else {
                    return;
                }
            }
        } catch( Exception $e_internal) {
            // Oh well, pick up the bones, but pick them up from the original exception
            // otherwise the message can be rather confusing
            // @todo: do we care about what $e_internal is?
            if (($opmode == 'developer') || $isdev) {
                ExceptionHandlers::bone($e);
            }
        }
    }

    // Handler with more information, for instance for the designated site admin
    public static function debughandler(Exception $e)
    {
        $trace = str_replace(sys::root(),'',$e->getTraceAsString());
        self::$data = array_merge( self::$data,
                              array(
                              'long'      => 'LONG msg TBD',
                              'hint'      => (method_exists($e,'getHint'))? htmlspecialchars($e->getHint(), ENT_COMPAT,'UTF-8',FALSE) : 'No hint available',
                              'stack'     => htmlspecialchars($trace),
                              'product'   => 'Product TBD',
                              'component' => 'Component TBD')
                            );
        self::defaulthandler($e);
    }

    // Lowest level handler, should always work, no assumptions whatsoever
    public static function bone(Exception $e)
    {
        echo ExceptionHandlers::RenderRaw($e);
    }

    /**
     * PHP error handler bridge to Xarigami exceptions
     *
     * @param  integer $errorType level of the error raised by PHP
     * @param  string  $errorString errormessage issued
     * @param  string  $file file is which the error occurred
     * @param  integer $line linenumber on which the error occurred
     * @param  array   $errorContext information on the context of the error
     * @author Marco Canini <marco@xaraya.com>
     * @access private
     * @throws PHPException
     * @return void
     */
    final public static function phperrors($errorRaised, $errorString, $file, $line, array $errorContext = array())
    {
        //if something goes wrong and we cannot load the checks for isdev and opmode,
        // we probaby want to know everything so set these at the outset
        $isdev = TRUE;
        $opmode = 'developer';
        $oldLevel = error_reporting();

        if (!sys::isInstall()) {
            try {
                if (method_exists('xarModVars','get') && class_exists('xarRoles')) {
                    $debuggroup = xarModVars::get('privileges','debuggroup');
                    $userrole = new xarRoles();
                    $user =$userrole->getRole(xarSessionGetVar('uid'));
                    $role= new xarRoles();
                    $parent = $role->getRole($debuggroup);
                    $isdev = xarIsParent($parent->uname,$user->uname);
                }
                $opmode = xarSystemVars::get(null,'Operation.Mode',true)?xarSystemVars::get(null,'Operation.Mode',true):'developer';

            } catch (Exception $e) {
                //no exception, just we use the original operation mode defined
            }

            try {
                // We'll try to get the configured threshold
                $errThreshold = xarSystemVars::get(sys::CONFIG,'Exception.ErrorLevel',TRUE);
            } catch(Exception $e) {
                // Oh well, show everything so construct the maximum bitmask
                // Note that E_ALL is already a summed bitmask value (2047) while E_STRICT is *NOT* (2048)
                // MrB: if there are actually E_STRICT errors, this is known to break *some* installs ( mine ;-) )
                $errThreshold = E_STRICT + E_ALL;
            }

            try {
                  $logLevel= xarSystemVars::get(sys::CONFIG,'Exception.ErrorLogLevel',TRUE);
            } catch(Exception $e) {
                $logLevel = E_ALL;
            }
        }
        // Only continue rendering if:
        // 1. the level was not 0 (either explicitly set or due to an @ on the line causing the error)
        // 2. the raised Errorlevel is included in the threshold bitmask
        if ( ($oldLevel==0) ||  (!$oldLevel) || ($errorRaised & $errThreshold !=$errorRaised )) {//  or ($errThreshold & $logLevel)) {
            // Log the message so it is not lost.
            // TODO: make this message available to calling functions that suppress errors through '@'.
            $msg = "PHP error code $errorRaised at line $line of $file: $errorString";
            try {
                // We'll try to log it.
                xarLogMessage($msg);
            } catch(Exception $e) {
                // Oh well, forget it then
            }
            return true; // no need to raise exception
        }

        // Make cached files also display their source file if it's a template
        // This is just for convenience when giving support, as people will probably
        // not look in the CACHEKEYS file to mention the template.
        $key = basename(strval($file),'.php');
        sys::import('xarigami.caching.template');
        $sourceFile = xarTemplateCache::sourceFile($key);

        $spacer= str_repeat(' ',11);
        $msg = "Mode     : $opmode\n";
        $msg .= "File     : $file\n";
        if(isset($sourcefile)) {
             $msg.= $spacer."[$sourceFile]\n";
        }
        $msg.= "Line     : $line\n";
        $msg.= "Code     : ".$errorRaised."\n";
        $msg.= "Message  : ".str_replace("\n","\n$spacer",wordwrap($errorString,75,"\n"))."\n";
        // @todo: it might not always be smart to show content of variables
        // jojo - catered for db var problems - make it configurable later
        $msg.= "Variables: ";
        foreach($errorContext as $varName => $varValue) {
            //htmlspecialchars removed
            if ($opmode && ($opmode !='developer') && $varName == 'dbconn') {
            continue;
            }
            if ($varName == 'argPassword'){
                 $varValue = "******";
            }
            $msg .= "\$$varName:\n$spacer  ". str_replace("\n","\n$spacer  ",htmlspecialchars(print_r($varValue,true), ENT_COMPAT,'UTF-8',FALSE))."\n$spacer";
        }
        $rawmsg = '';
        if (!function_exists('xarModURL')) {
            if (!strstr($msg,'Mode')) { //don't repeat error info if error already thrown and passed back eg adodb uncaught exception
                $rawmsg = "Normal Xarigami error processing has stopped because of an error encountered.\n\n";
                $rawmsg .= "The last registered error message is:\n\n";
            }
            $rawmsg .= $msg;
            $msg = $rawmsg;
        } else {
            $module = '';
            $shortUrlVariables = xarRequest::getShortUrlVariables();
            if (xarRequest::getAllowShortUrls() && isset($shortUrlVariables['module'])) {
                $module = $shortUrlVariables['module'];
            } elseif (isset($_GET['module'])) {
                // Then check in $_GET
                $module = $_GET['module'];
            }

            // @todo consider removing this, it doesnt add much and causes quite a maintenance task
            $product = ''; $component = '';
            if ($module != '') {
                // load relative to the current file (e.g. for shutdown functions)
                sys::import('xarigami.exceptions.xarayacomponents');
                foreach (xarComponents::$core as $corecomponent) {
                    if ($corecomponent['name'] == $module) {
                        $component = $corecomponent['fullname'];
                        $product = "App - Core";
                        break;
                    }
                }
                if ($component != '') {
                    foreach (xarComponents::$apps as $appscomponent) {
                        if ($appscomponent['name'] == $module) {
                            $component = $appscomponent['fullname'];
                            $product = "App - Modules";
                        }
                    }
                }
            }

        }
        if (($opmode == 'developer') || $isdev) {
            // Throw an exception to let the default handler handle the rest.
            throw new PHPException($msg,$errorRaised);
        } else {
            return;
        }
    }

    // Private methods
    private static function RenderRaw(Exception $e)
    {
        // @todo how many assumptions can we make about the rendering capabilities of the client here?
        $out="<pre>";
        $out.= 'Error: '.$e->getCode().": ".get_class($e)."\n";
        $out.= $e->getMessage()."\n";
        $out.= "Backtrace: ".str_replace("\n","\n           ",$e->getTraceAsString());
        $out.= "</pre>";
        return $out;
    }
}
?>
