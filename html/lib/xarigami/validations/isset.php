<?php
/**
 * IsSet Validation Function
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/
/**
 * This function checks for the 'isset' status of a parameter
 *
 * It will return true when the parameter isset, false on !isset.
 * When not set, the function will also throw the BAD_PARAM exception
 * @param bool supress_soft_exc
 * @param parameters
 * @param subject The parameter to check for
 * @return bool true on isset, false on !isset
 * @throws BAD_DATA
 */
class IssetValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!isset($subject)) {
            $msg = xarML('The variable was not set but the validation requires it to be.');
            throw new VariableValidationException('subject', $msg);
        }
        return true;
    }
}
?>