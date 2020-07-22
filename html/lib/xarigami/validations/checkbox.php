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
 * Checkbox Validation Class
 * @return bool
 * @throws VariableValidationException
 */
class CheckBoxValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        //jojo - this is not going to work for return from a form
        //if ($subject === false || $subject == 'false' || $subject == 'FALSE') {
            //ok
        //} elseif (is_string($subject)) {
            //ok
        if (empty($subject) || is_null($subject)) {
            $subject = FALSE;
        } elseif (is_string($subject)) {
            $subject = TRUE;
        } else {
            $msg = xarML('Not a checkbox value');
            throw new VariableValidationException(null,$msg);
        }
    return true;
    }
}

?>