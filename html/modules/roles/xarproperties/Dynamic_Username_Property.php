<?php
/**
 * Handle Username Property
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
 * Handle Username Property
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');
class Dynamic_Username_Property extends Dynamic_TextBox_Property
{
    public $id         = 7;
    public $name       = 'username';
    public $desc       = 'Username';
    public $xv_display_link  = false;
    public $xv_allow_input  = false;

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'username';
        $this->filepath   = 'modules/roles/xarproperties';

        if (isset($value) && (strtolower($value) == 'myself')) $this->value = xarUserGetVar('uid');
    }
    function validateValue($value = null)
    {
        $existingvalue = $this->value;

        //value should be text if input - [jojo] $this->value is an int value

         // We set an empty value to the id of the current user
        if (!isset($value) || empty($value) || ($value == 'myself')) {
                $value = xarUserGetVar('uname');

        }
        if (!parent::validateValue($value)) return false;
        if (isset($value) && !empty($value)) {
            $role =  xarUFindRole($value);
            if (!isset($role) || empty($role)) {
                //check displayname
                $role = xarFindRole($value);
            }

            if (isset($role) && !empty($role)) {
                 $this->value = $role->getID();
            } else {
                if ($this->xv_allowempty == 1) {
                    $this->value = null;
                } else {
                 $this->invalid = xarML("user, '#(1)' does not exist", $value);
                 $this->value = null;
                 return false;
                }
            }
        }
        return true;
    }

    function showInput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = (int)$this->value;
        // The user param is a uname
        if (isset($user) && !empty($user)) {
            if ($user== 'myself') {
                $this->value = xarUserGetVar('uid');
                $user = xarUserGetVar('uname',$this->value);
            }
        } else {
            if (isset($value)) $this->value = $value;
            $user = xarUserGetVar('uname',$value);
        }
        //we have value as an id, get the username for display
        $data['value'] = $value;
        $data['user'] = $user;
         $data['displayname'] = '';
         $displayname = xarModGetVar('roles','requiredisplayname');
        if ($displayname == TRUE) {
            $data['displayname']= xarUserGetVar('name',$value);
        } else {
             $data['displayname']= $user;
        }
        $data['allowinput']=$this->xv_allow_input;

        return parent::showInput($data);
    }


    function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value) || empty($value)) {
            $value =$this->value;
        }
        $data['displayname'] = '';
        $displayname = xarModGetVar('roles','requiredisplayname');
        if (isset($value) && !empty($value) && is_numeric($value)) {
            if ($displayname == TRUE) {
                 $user = xarUserGetVar('name',$value);
            } else {
                $user = xarUserGetVar('uname',$value);
            }
        } else {
            $user = '';
        }

        $data['value'] = $value;
        $data['user']  = xarVarPrepForDisplay($user);

        if ($this->xv_display_link && !empty($value)) {
            $data['linkurl']=xarModURL('roles','user','display',array('uid' => $value));
        } else {
            $data['linkurl']='';
        }
         return parent::showOutput($data);
    }

  /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvalidation = parent::getBaseValidationInfo();
            //TODO - jojo - apply these options
            $existingarray = array('options' =>array(
                                                    array('id'=>0, 'name'=>xarML('No requirement')),
                                                    array('id'=>1, 'name'=>xarML('User must not exist')),
                                                    array('id'=>2, 'name' =>xarML('Users must already exist'))
                                                    ),
                                    'required'=>0,
                                    'default' => 1
                                    );
            $validations = array(
                                    'xv_display_link'    =>  array('label'=>xarML('Display username link?'),
                                                          'description'=>xarML('Display link to user name?'),
                                                          'propertyname'=>'checkbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'display'
                                                           ),
                                     'xv_allow_input'    =>  array('label'=>xarML('Allow input?'),
                                                          'description'=>xarML('Allow input of username'),
                                                          'propertyname'=>'checkbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
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
        $validation = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 7,
                              'name'       => 'username',
                              'label'      => 'Username',
                              'format'     => '7',
                              'validation' => serialize($validation),
                            'source'     => '',
                             'filepath'    => 'modules/roles/xarproperties',
                            'dependancies' => '',
                            'requiresmodule' => 'roles',
                            'aliases' => '',
                            'args'         => '',
                            // ...
                           );
        return $baseInfo;
     }

}

?>
