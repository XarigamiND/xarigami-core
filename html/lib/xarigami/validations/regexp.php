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
 * Regular Expression Validation Class
 * @return bool
 */
class RegExpValidation extends ValueValidations
{
    function validate (&$subject, Array $parameters)
    {
        if (!isset($parameters[0]) || trim($parameters[0]) == '') {
            $msg = xarML('There is no parameter to check agains the regular expression validation.');
            // CHECK: this is probably better a BadParameterException ?
            throw new VariableValidationException(null, $msg);

        } elseif (preg_match($parameters[0], $subject)) {
            return true;
        }
        $msg = xarML("'#(1) Does not match pattern '#(2)'",$subject,$parameters[0]);
        throw new VariableValidationException(null,$msg);
    }
}

?>