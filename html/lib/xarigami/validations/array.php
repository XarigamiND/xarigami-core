<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Array Validation Function
 *
 * @throws VariableValidationException, BadParameterException
**/
class ArrayValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        // If the subject is not array, we can bail out, cos that's what it is all about
        if (!is_array($subject)) {
            $msg = 'Not an array';
            return false;
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            if (!is_numeric($parameters[0])) {
                // We need a number for the minimum nr of elements
                $msg = xarML('The parameter specifying the minimum number of elements should be numeric. It is: "#(1)"',$parameters[0]);
                throw new BadParameterException(null,$msg);
            } elseif (count($subject) < (int) $parameters[0]) {
                // The subject has too little values
                $msg = xarML('Array variable has less elements "#(1)" than the specified minimum "#(2)"',array(count($subject), $parameters[0])) ;
                throw new VariableValidationException(null, $msg);
            }
        }

        if (isset($parameters[1]) && trim($parameters[1]) != '') {
            if (!is_numeric($parameters[1])) {
                // We need a number for the maximum nr of elements
                $msg = xarML('The parameter specifying the maximum number of elements should be numeric. It is: "#(1)"', $parameters[1]);
                throw new BadParameterException(null,$msg);
            } elseif (count($subject) > (int) $parameters[1]) {
                // The subject has too many values
                $msg = xarML('Array variable has more elements "#(1)" than the specified maximum "#(2)"', count($subject), $parameters[1]);
                throw new VariableValidationException(array(null, $msg));
            }
        }
        return true;
    }
}
?>
