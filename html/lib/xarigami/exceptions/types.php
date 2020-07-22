<?php
/**
 * Exception types
 *
 * @package exceptions
 * @copyright (C) 2006 by The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 *
 * @subpackage exceptions
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Interface for all Xarigami raised exceptions
 *
 */
interface IxarExceptions {
    /* Why can't i specify final here? */
    public function __construct($vars = null, $msg = null);
    public function getHint();
}

/**
 * Base class for all Xarigami exceptions
 *
 * Every part of Xarigami may derive their
 * own Exception class if they see fit to do so
 *
 */
abstract class xarExceptions extends Exception implements IxarExceptions
{
    // Variable parts in the message.
    protected $message   = '';
    protected $variables = array();
    protected $hint      = '';
    protected $hasValidVars = false;
    protected $expectedVarsCount = 1; // Number of vars expected

    protected function getDefaultMessage()
    {
        return $this->xarML("Missing Exception Info, please put the defaults for '\$message' and '\$variables' members in the derived exception class.");
    }

    protected function getDefaultHint()
    {
        return $this->xarML("No hint available");
    }

    /**
     All exceptions have the same interface from XAR point of view
     so we dont allow this to be overridden just now. The message parameter
     may be overridden though. If not supplied the default message
     for the class gets used.
     Throwing an exeception is done by:
         throw new WhateverException($vars);
     $vars is an array of values which are variable in the message.
     The message is normally not overridden but possible., example:
         throw new FileNotFoundException(array($file,$dir),'Go place the file #(1) in the #(2) location, i can not find it');
    */
    final public function __construct($vars = null, $msg = null, $hint = null)
    {
        // Make sure the construction creates the right values first

        // Validates and assigns the vars.
        $this->validateVars($vars);

        // Prepares the text part of the message
        if (!is_null($msg)) {
            $this->message = $msg;
        } else {
            $this->message = $this->getDefaultMessage(); // Use $this->getDefaultMessage here and not self::getDefaultMessage to benefit of the method overriding this base class.
        }

        // Prepares the hint
        if (!is_null($hint)) {
            $this->hint = $hint;
        } else {
            $this->hint = $this->getDefaultHint(); // Use $this->getDefaultHint here and not self::getDefaultHint to benefit of the method overriding this base class.
        }
        parent::__construct($this->message, $this->code);

        $this->formatMessage();
    }

    protected function validateVars($vars)
    {
        // To override eventually if the exception requires some vars to be passed in a certain way.
        //
        // By default we want all the vars to be f
        if (!is_null($vars)) $this->variables = $vars;
        if (!is_array($this->variables)) $this->variables = array($this->variables);
        //
        // Eventually validate and readjust/reformat the variables member.
        // if there is no vars, the array is empty
        //
        // After this the vars array should not be changed anymore
        //
        // Keep the information whether the vars can be eventually shown or not.
        $this->hasValidVars = count($this->variables) >= $this->expectedVarsCount; // Replace this by any validation test
        // You might prefer to override the initialization of the member expectedVarsCount with the suitable value, if you only need to adjust the expected number of vars passed.
    }

    protected function formatMessage()
    {
        if ($this->hasValidVars) {
            // Use vars
            if (preg_match('/\#\([0-9]+\)/', $this->message)) {
                // The message contains points of insertion for vars
                $rep = 1;
                foreach($this->variables as $var)
                    $this->message = str_replace("#(" . $rep++ . ")", (string)$var, $this->message);
            } else {
                // TODO: the message has no entry point for parameters. We might want to eventually display vars in developer mode.
            }
        }
    }

    public function getHint()
    {
        // preserve protected status if peeps call it by reference (i'd say this is a php bug)
        $ret = $this->hint;
        return $ret;
    }

    // Provides MLS support IF MLS is loaded
    protected function xarML($string)
    {
        if (function_exists('xarML')) {
            if (func_num_args() > 1) {
                $args = func_get_args();
                if (is_array($args[1])) {
                    $args = $args[1]; // Only the second argument is considered if it's an array
                } else {
                    array_shift($args); // Drop $string argument
                }
            } elseif ($this->hasValidVars) {
                $args = $this->variables; // @todo: review if doing this is appropriate in all situation.
            } else {
                $args = array();
            }
            return xarML($string, $args);
        }
        else {
            return $string;
        }
    }
}

/**
 * System types
 */
// PHP errors (including assertions)
class PHPException extends Exception
{

}

// Debugging
class DebugException extends xarExceptions
{
    // Derived exception class should minimally proved the following 2

    // TODO: we need to handle production and demo mode to hide any ugly debugging message.

    protected function ValidateVars($vars)
    {
        if ($vars != null) {
            // We don't want to create arrays of arrays here,
            // we keep the structure identical to dump it later in formatMessage method
            $this->variables = $vars;
            $this->hasValidVars = true;
        }
    }

    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('Default "$message" for "DebugException" with "$variables" member with value: ');
        else
            return $this->xarML('Default "$message" for "DebugException". You might want to pass a variable value to display.');
    }

    protected function formatMessage()
    {
        if ($this->hasValidVars) {
            $dump = print_r($this->variables, true);
            if (count($dump) > 30 || stripos($dump, "\n")) $this->message .= "\n";
            $this->message .= $dump;
        }
    }
}

/**
 * Xarigami exception types
 *
 * The ideal situation here is that we only have abstract classes
 * below to help the rest of the framework derive their exceptions
 * Since it is not ideal yet, some explicit exception types are
 * also defined here now. Over time, the explicit ones should move
 * to their respective subsystems or modules.
 *
 */

// Let's start with the abstract classes we ar reasonably sure of
// Registration failures
abstract class RegistrationExceptions extends xarExceptions
{}
// Validation failures
abstract class ValidationExceptions extends xarExceptions
{}
// Not finding stuff
abstract class NotFoundExceptions extends xarExceptions
{}
// Duplication failures
abstract class DuplicationExceptions extends xarExceptions
{}
// Configuration failures
abstract class ConfigurationExceptions extends xarExceptions
{}
// Deprecation exceptions
abstract class DeprecationExceptions extends xarExceptions
{}


/* ANYTHING BELOW THIS LINE IS UP FOR REVIEW AND SHOULD PROBABLY BE MOVED OR REWRITTEN */

// Anything going wrong with parameters in functions and method derives from this
// FIXME: this is weak
// FIXME: it's probably better to bring this under validation? In some cases even assertions.
abstract class ParameterExceptions extends xarExceptions
{}
// Empty required parameters
class EmptyParameterException extends ParameterExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML("The parameter '#(1)' was expected in a call to a function, but was not provided.");
        else
            return $this->xarML("Some parameter was expected in a call to function, but was not provided.");
    }
}
// Bad values in parameters
class BadParameterException extends ParameterExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML("The parameter '#(1)' provided during this operation could not be validated, or was not accepted for other reasons.");
        else
            return $this->xarML("Some parameter provided during this operation could not be validated, or was not accepted for other reasons.");
    }
}

// Functions
class FunctionNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The function "#(1)" could not be found or not be loaded.');
        else
            return $this->xarML('Some function could not be found or not be loaded.');
    }
}
// ID's
class IDNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('An item was requested based on a unique identifier (ID), however, the ID: "#(1)" could not be found.');
        else
            return $this->xarML('An item was requested based on a unique identifier (ID), however, its ID could not be found.');

    }
}
// Files
class FileNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The file "#(1) could not be found.');
        else
            return $this->xarML('Some file could not be found.');
    }
}
// Directories
class DirectoryNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The directory "#(1)" could not be found.');
        else
            return $this->xarML('Some directory could not be found.');
    }
}
// Generic data
// FIXME: this is too generic
class DataNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        return $this->xarML('The data requested could not be found.');
    }
}
// Variables
class VariableNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The variable "#(1)" could not be found.');
        else
            return $this->xarML('Some variable could not be found');
    }
}
// Classes
class ClassNotFoundException extends NotFoundExceptions
{
    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The class "#(1)" could not be found."');
        else
            return $this->xarML('Some class could not be found.');
    }
}

// Generic duplication exception
// TODO: go over the uses of this generic one and make them explicit for what was actually duplicated
class DuplicateException extends DuplicationExceptions
{
    // We want to 2 vars passed
    protected $expectedVarsCount = 2;

    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The #(1) "#(2)" already exists, no duplicates are allowed.');
        else
            return $this->xarML('An item already exists, no duplicates are allowed.');
    }
}

// Forbidden operation
class ForbiddenOperationException extends xarExceptions
{
    protected function getDefaultMessage()
    {
        return $this->xarML('The operation you are attempting is not allowed in the current circumstances.');
    }
}
// Service Unavailable
class ServiceUnavailableException extends xarExceptions
{
    protected function getDefaultMessage()
    {
        return $this->xarML('We are unable to service your request at this time due to unexpected load or database connection problems. Please try again later.');
    }
    protected function getDefaultHint()
    {
        if ($this->hasValidVars) {

            if ($this->variables[0]);
            $hint =$this->variables[0]->message;
            //$hint = $this->variables->getMessage();
            return $hint;

        }
    }

}
// Generic XML parse exception
// FIXME: this is isolated in MLS now, make those instance more specific and lose this one
class XMLParseException extends xarExceptions
{
    // We want to 3 vars passed
    protected $expectedVarsCount = 3;

    protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML('The XML file "#(1)" could not be parsed. At line #(2): #(3)');
        else
            return $this->xarML('Some XML file could not be parsed');
    }
}

class VariableValidationException extends ValidationExceptions
{
  protected function getDefaultMessage()
    {
        if ($this->hasValidVars)
            return $this->xarML("The variable '#(1)' [Value: '#(2)'] did not comply with the required validation: '#(3)");
        else
            return $this->xarML('The variable did not comply with the required validation.');
    }
}

?>