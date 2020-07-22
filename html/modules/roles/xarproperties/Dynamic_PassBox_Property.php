<?php
/**
 * Dynamic Passbox property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

/*
 * Dynamic Passbox property
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');
class Dynamic_PassBox_Property extends Dynamic_TextBox_Property
{
    public $id         = 46;
    public $name       = 'passwordbox';
    public $desc       = 'Password';
    public $password    = null;
    public $xv_display_size = 25;
    public $xv_min_length   = 4;
    public $xv_max_length   = 30;
    public $xv_password_confirm = 0;
    public $xv_hash_type    = 'md5';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template ='password';
        $this->filepath   = 'modules/roles/xarproperties';
    }


    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (is_array($value)) {
             if ($value[0] == $value[1]) {
                $value = $value[0];
            } else {
                $this->invalid = xarML('Invalid input: Passwords did not match');
               // $this->value = null;
                return false;
            }
        }
        if (!(empty($value) && !empty($this->value))) {
            if (!parent::validateValue($value)) return false;
            //$this->value = $value;
        }
        return true;
    }

    function showInput(Array $data = array())
    {
        extract($data);
        $data['confirm']  = isset($confirm) ? $confirm : $this->xv_password_confirm;
        $data['template'] = isset($template)?$template:$this->template;
        return parent::showInput($data);
    }

    function showOutput(Array $data = array())
    {
    //we don't really want to show the password, do we?

    $data['value']='';
    $data['template'] = isset($template)?$template:$this->template;
    return parent::showOutput($data);

    //return '';
    }

    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvalidation = parent::getBaseValidationInfo();
            $validations= array('xv_password_confirm'    =>  array('label'=>xarML('Confirm password?'),
                                                          'description'=>xarML('Display confirmation box for password'),
                                                          'propertyname'=>'checkbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'validation')
                                         );

            $validationarray = array_merge($validations ,$parentvalidation);
        }
        return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 46,
                            'name'       => 'password',
                            'label'      => 'Password text box',
                            'format'     => '46',
                            'validation' => serialize($validations),
                            'source'     => '',
                             'filepath'    => 'modules/roles/xarproperties',
                            'dependancies' => '',
                            'requiresmodule' => 'roles',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>
