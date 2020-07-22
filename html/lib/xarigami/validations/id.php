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
sys::import('xarigami.validations.int');


/**
 * Id Validation Class
 * That validation redirects to int:1 validation
 * @return bool
 */
class IdValidation extends IntValidation
{
    function validate(&$subject, Array $parameters)
    {
        return parent::validate($subject,array(1));
    }
}

?>