<?php
/**
 * validate a parameter as an email address
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2009-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/
/**
 * Validate an email address
 * @return bool true if email, false if not
 */
class EmailValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        //better validation of email address
        if (!preg_match('/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui',$subject)) {
           //if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$/', $subject)) {
            $msg = xarML('Not a valid email format');
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}
?>