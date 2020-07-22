<?php
/**
 * validate a parameter as a Canadian Postal Code
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/

/**
 * Validate a Canadian Postal Code
 * @return bool true if email, false if not
 */
class CAPostalCodeValidation extends ValueValidations
{
    function validate($subject, Array $parameters)
    {
         if (!preg_match('/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\ {0,1}(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/', $subject)) {
            $msg = xarML("Not a postal code type: '#(1)'",$subject);
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}

?>
