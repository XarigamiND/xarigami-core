<?php
/**
 * Handle Group list property
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
 * Handle Group list property
 * @package modules
 * @subpackage Roles module
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

class Dynamic_GroupList_Property extends Dynamic_Select_Property
{
    public $id         = 45;
    public $name       = 'grouplist';
    public $desc       = 'Group List';

    public $ancestorlist = array();
    public $parentlist = array();
    public $grouplist = array();
    //we don't initialize the parent class so let's also do the other vars
    public $xv_parentgrouplist = null;
    public $xv_ancestorgrouplist = null;
    public $xv_grouplist = null;
    public $xv_override = true;

    /*
    * Options available to user selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   ancestor:name[,name] - select only groups who are descendants of the given group(s)
    *   parent:name[,name] - select only groups who are members of the given group(s)
    *   group:name[,name] - select only the given group(s)
    */

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/roles/xarproperties';
        $this->tplmodule = 'roles';
        $this->template = 'grouplist';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;

        // Get the previous group from the form
        if (!xarVarFetch($name . '_previous_value', 'int', $previous_value, 0, XARVAR_NOT_REQUIRED)) return;
        $this->previous_groupid = $previous_value;

        return parent::checkInput();
    }

    function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            // check if this is a valid group id
            $group = xarMod::apiFunc('roles','user','get',
                                   array('uid' => $value,
                                         'type' => 1)); // we're looking for a group here
            if (!empty($group)) {
                $this->value = $value;
                return true;
            }
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection: #(1)', $this->name);
        $this->value = null;
        return false;
    }

    function showInput(Array $data = array())
    {
        extract($data);
        $select_options = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->getOptions();
        }

        $data['template'] = isset($template)?$template:$this->template;
        return parent::showInput($data);
    }

    function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $group = array();
            $groupname = '';
        } else {
            $group = xarMod::apiFunc('roles','user','get',
                                   array('uid' => $value,
                                         'type' => 1)); // we're looking for a group here
            if (empty($group) || empty($group['name'])) {
                $groupname = '';
            } else {
                $groupname = $group['name'];
            }
        }
        $data['value']=$value;
        $data['group']=$group;
        $data['template'] = isset($template)?$template:$this->template;
        $data['groupname']=xarVarPrepForDisplay($groupname);


        return xarTplProperty('roles', 'grouplist', 'showoutput', $data);
    }
    public function getOptions()
    {
        $select_options = array();
        if (!isset($this->options) || count($this->options) == 0 ) {
            if (!empty($this->xv_ancestorgrouplist) && is_array($this->xv_ancestorgrouplist)) {
                $select_options['ancestor'] = implode(',',$this->xv_ancestorgrouplist);
            }
            if (!empty($this->xv_parentgrouplist) && is_array($this->xv_parentgrouplist)) {
                $select_options['parent'] = implode(',',$this->xv_parentgrouplist);
            }
            if (!empty($this->xv_grouplist) &&  is_array($this->xv_grouplist)) {
                $select_options['group'] = implode(',',$this->xv_grouplist);
            }
            // TODO: handle large # of groups too (optional - less urgent than for users)
            $groups = xarMod::apiFunc('roles', 'user', 'getallgroups', $select_options);
            $options = array();
            foreach ($groups as $group) {
                //jojo - what privs here?
                 // if  (xarSecurityCheck('SubmitGroupRoles',0,'Group',$group['uid']) || xarSecurityCheck('ReadRole',0,'Roles',$group['uid'])) {
                    $options[] = array('id' => $group['uid'], 'name' => $group['name']);
                 //}
            }
        } else {
			$options = array();
		}
        return $options;
    }

    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

            $parentvalidation = parent::getBaseValidationInfo();

            $validations= array(
                                    'xv_ancestorgrouplist'    =>  array('label'=>xarML('Ancestor group list'),
                                                          'description'=>xarML('Select groups that are decendents of ancestors'),
                                                          'propertyname'=>'textbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
                                    'xv_parentgrouplist'    =>  array('label'=>xarML('Parent group list'),
                                                          'description'=>xarML('Select groups that are members of parents'),
                                                          'propertyname'=>'textbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
                                     'xv_grouplist'    =>  array('label'=>xarML('Group list'),
                                                          'description'=>xarML('Select only the given groups'),
                                                          'propertyname'=>'textbox',
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
         $args = array();
         $validations = $this->getBaseValidationInfo();
         $baseInfo = array(
                              'id'         =>45,
                              'name'       => 'grouplist',
                              'label'      => 'Group list',
                              'format'     => '45',
                              'validation' => serialize($validations),
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}

?>
