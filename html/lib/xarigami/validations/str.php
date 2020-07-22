<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/

/**
 * Strings Validation Class
 * @throws VariableValidationException, BadParameterException
 */
class StrValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!is_string($subject)) {
            $msg = xarML('Not a string');
            throw new VariableValidationException(null, $msg);
        }

        $length = strlen($subject);

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                // We need a number for the minimum length
                $msg = xarML("The parameter specifying the minimum length of the string should be numeric. It is: '#(1)'",$parameters[0]);
                throw new BadParameterException(null,$msg);
            } elseif ($length < (int) $parameters[0]) {
                $msg = xarML("Size of the string '#(1)' is smaller than the specified minimum '#(2)'",$subject,$parameters[0]);
                throw new VariableValidationException(null,$msg);
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                // We need a number for the maximum length
                 $msg = xarML("The parameter specifying the maximum length of the string should be numeric. It is: '#(1)'",$parameters[1]);
                throw new BadParameterException(null,$msg);
            } elseif ($length > (int) $parameters[1]) {
                $msg = xarML("Size of the string '#(1)' is larger than the specified maximum '#(2)'",$subject,$parameters[1]);
                throw new VariableValidationException(null,$msg);
            }
        }

        $subject = (string) $subject; //Is this useless?
        return true;
    }
}

?>