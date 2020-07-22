<?php
/**
 * Validations
 *
 * @package Xarigami core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Base class Validations
 *
**/
interface IValidation
{
    function validate(&$subject, Array $parameters);
}

class ValueValidations extends xarObject implements IValidation
{

    static public function &get($type)
    {
        switch($type) {
            case 'array'        : $valclass  = 'ArrayValidation';       break;
            case 'bool'         : $valclass  = 'BoolValidation';        break;
            case 'capostalcode' : $valclass  = 'CAPostalCodeValidation';break;
            case 'checkbox'     : $valclass  = 'CheckBoxValidation';    break;
            case 'date'         : $valclass  = 'DateValidation';        break;
            case 'email'        : $valclass  = 'EmailValidation';       break;
            case 'enum'         : $valclass  = 'EnumValidation';        break;
            case 'filename'     : $valclass  = 'FilenameValidation';    break;
            case 'float'        : $valclass  = 'FloatValidation';       break;
            case 'fullemail'    : $valclass  = 'FullEmailValidation';   break;
            case 'html'         : $valclass  = 'HtmlValidation';        break;
            case 'id'           : $valclass  = 'IdValidation';          break;
            case 'int'          : $valclass  = 'IntValidation';         break;
            case 'isset'        : $valclass  = 'IssetValidation';       break;
            case 'keylist'      : $valclass  = 'KeyListValidation';     break;
            case 'list'         : $valclass  = 'ListValidation';        break;
            case 'mxcheck'      : $valclass  = 'MxCheckValidation';     break;
            case 'nanphone'     : $valclass  = 'NanPhoneValidation';    break;
            case 'notempty'     : $valclass  = 'NotEmptyValidation';    break;
            case 'pre'          : $valclass  = 'PreValidation';         break;
            case 'regexp'       : $valclass  = 'RegExpValidation';      break;
            case 'str'          : $valclass  = 'StrValidation';         break;
            case 'strlist'      : $valclass  = 'StrListValidation';     break;
            case 'uszip'        : $valclass  = 'USZipValidation';       break;
        }
         if (!empty($valclass)) {
            if (!class_exists($valclass)) sys::import("xarigami.validations.$type");
            $obj = new $valclass();
            return $obj;
        } else {
             throw new Exception('Unknown Implementation');
        }
    }

    public function validate(&$subject, Array $parameters)
    {
        throw new Exception('Unknown Implementation');
    }
}
?>