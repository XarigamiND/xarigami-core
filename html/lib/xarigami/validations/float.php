<?php
/**
 * Validate a value as a floating point number
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/

/**
 * Float Validation Function
 *
 * This function will validate the input for it being a float number
 * It will return true when the value validated is a number in the format
                - 1.234
 * @return true on success (value is validated as a float number
 * @throws VariableValidationException, BadParameterException
**/

class FloatValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
            $value = (float)$subject;
            if ("$subject" != "$value") {
                $msg = xarML('Not a float type');
                throw new VariableValidationException(null, $msg);
            }

            if (isset($parameters[0]) && trim($parameters[0]) != '') {
                if (!is_numeric($parameters[0])) {
                    // We need a number for the minimum
                    $msg = xarML("The parameter specifying the minimum value should be numeric. It is: '#(1)'", $parameters[0]);
                    throw new BadParameterException(null,$msg);
                } elseif ($value < (float) $parameters[0]) {
                   $msg = xarML("Float Value #(1) is smaller than the specified minimum #(2)", $value, $parameters[0]);
                   throw new VariableValidationException(null,$msg);
                }
            }

            if (isset($parameters[1]) && trim($parameters[1]) != '') {
                if (!is_numeric($parameters[1])) {
                    // We need a number for the maximum
                    $msg = xarML("The parameter specifying the maximum value should be numeric. It is: '#(1)'", $parameters[1]);
                    throw new BadParameterException(null, $msg);
                } elseif ($value > (float) $parameters[1]) {
                    $msg = xarML("Float Value #(1) is larger than the specified maximum #(2)", $value, $parameters[1]);
                    throw new VariableValidationException(null,$msg);
                }
            }

            $subject = $value; //redundant
            return true;
    }
}
?>
