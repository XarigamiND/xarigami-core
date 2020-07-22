<?php
/**
 * String List Validation function
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/
sys::import('xarigami.xarValidations');

/**
 * list:{sep}:...
 * Split the string into separate items using 'sep' as an item
 * separator, then validate each individually.
 * Any separator characters can be used except for ':'.
 * Multiple separator characters can be used, and any will be
 * recognised, but all will be converted into the first character
 * on return. So a separator string of ';,' when applied to a
 * subject string 'hello,there;word' will return 'hello;there;word'.
 * Validation of each item in the list will be further passed on to
 * any required validation type.
 *
 * @return mixed
 */
 sys::import('xarigami.xarValidations');
class StrListValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        $return = true;

        if (!is_string($subject)) {
            $msg =  xarML('Not a string');
            throw new VariableValidationException(null, $msg);
        }

        if (!empty($parameters)) {
            // Get the separator characters.
            $sep = array_shift($parameters);

            // @todo error if no separator?
            if (empty($sep)) {
                $msg = xarML('No separator character(s) provided for validation type "strlist"');
                throw new BadParameterException($msg);
            }

            // Roll up the remaining validation parameters (noting there
            // may not be any - $parameters could be empty).
            $validation = implode(':', $parameters);

            // Split up the string into elements.
            $elements = preg_split('/[' . preg_quote($sep) . ']/', $subject);

            // Get count of elements.
            $count = count($elements);

            // Loop through each element if there are any elements, and if
            // there is further validation to apply.
            if ($count > 0 && !empty($validation)) {
                for($i = 0; $i < $count; $i++) {
                    // Validate each element in turn.
                    $return = $return & xarVarValidate($validation, $elements[$i]);
                    if (!$return) {
                        // This one failed validation - don't try and validate any more.
                        break;
                    }
                }
            }

            // Roll up the validated values. Use the first character
            // from the separator character list.
            $subject = implode(substr($sep, 0, 1), $elements);
        }
        return $return;
    }
}
?>