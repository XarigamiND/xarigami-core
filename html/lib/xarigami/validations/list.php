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
 * Lists Validation Function
 * @param array subject. If this is not an array, the return is false
 * @return bool true if a list is found, false if not
 */
sys::import('xarigami.xarValidations');
class ListValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!is_array($subject)) {
            $msg = xarML('Value should be an array but is not');
            throw new VariableValidationException(null, $msg);
        }

        if (isset($parameters[0]) && trim($parameters[0]) != '') {
            $validation = implode(':', $parameters);
            foreach  ($subject as $key => $value) {
                $return = xarVarValidate($validation, $subject[$key]);
                //$return === null or $return === false => return
                if (!$return) {
                    return $return;
                }
            }
        }

        return true;
    }
}
?>
