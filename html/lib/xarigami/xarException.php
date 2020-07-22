<?php
/**
 * Exception Handling System
 *
 * @package xarigami core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage exceptions
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
define('E_XAR_ASSERT', 1);
define('E_XAR_PHPERR', 2);
/**#@-*/

// Import all our exception types and the core exception handlers
sys::import('xarigami.exceptions.types');
sys::import('xarigami.exceptions.handlers');

// Send all exceptions to the default exception handler, no excuses
//set_exception_handler(array('ExceptionHandlers','defaulthandler'));
set_exception_handler(array('ExceptionHandlers','defaulthandler'));
// Send all error the the default error handler (which basically just throws a specific exception)
if (!sys::isInstall()) {
    if (xarSystemVars::get(null,'Exception.EnablePHPErrorHandler',true)) {
        set_error_handler(array('ExceptionHandlers','phperrors'));
    }
}
/**
 * General exception to cater for situation where the called function should
 * really raise one and the callee should catch it, instead of the callee
 * raising the exception. To prevent hub-hopping* all over the code
 *
 * @todo we need a way to determine the usage of this, because each use
 *       signals a 'code out of place' error
**/
class GeneralException extends xarExceptions
{
    protected $message = "An unknown error occurred.";
    protected $hint    = "The code raised an exception, but the nature of the error could not be determind";
}


/**
 * Debug function, artificially throws an exception
 *
 * @access public
 * @return void
 * @throws DebugException
**/
function debug($anything)
{
    throw new DebugException('DEBUGGING',var_export($anything,true));
}

?>
