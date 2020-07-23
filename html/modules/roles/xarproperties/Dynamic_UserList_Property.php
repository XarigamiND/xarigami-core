<?php
/**
 * Dynamic userlist property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

/*
 * Dynamic userlist property
 * @package modules
 * @subpackage Roles module
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');
///sys::import('modules.roles.xarroles');
class Dynamic_UserList_Property extends Dynamic_Select_Property
{
    public $id         = 37;
    public $name       = 'userlist';
    public $desc       = 'User List';

    public $grouplist = array();
    public $userstate = -1;
    public $showlist = array();
    public $orderlist = array();
    public $showglue = '; ';

    public $xv_userstate = ROLES_STATE_ALL;
    public $xv_grouplist = '';
    public $xv_userlist = '';
    public $xv_showfields = '';
    public $xv_orderlist = '';
    public $xv_override = true;
    public $display_showglue = '';

    /*
    * Options available to user selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   group:name[,name] - select only users who are members of the given group(s)
    *   state:value - select only users of the given state
    *   show:field[,field] - show the specified field(s) in the select item
    *   showglue:string - string to join multiple fields together
    *   order:field[,field] - order the selection by the specified field
    * where
    *   field - name|uname|email|uid
    */

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'userlist';
        $this->filepath   = 'modules/roles/xarproperties';

        if (!isset($this->options) || count($this->options) == 0) {
            $select_options = array();
            if (($this->xv_userstate <> ROLES_STATE_ALL)) $select_options['state'] = $this->xv_userstate;
            if (!empty($this->xv_orderlist)) $select_options['order'] = implode(',', $this->xv_orderlist);
            if (!empty($this->xv_grouplist) && is_array($this->xv_grouplist)) $select_options['group'] = implode(',', $this->xv_grouplist);
            $users = array();
            // Loop for each user retrived and populate the options array.

            if (empty($this->xv_showfields)) {
                // Simple case (default) -
                foreach ($users as $user) {
                    $this->options[] = array('id' => $user['uid'], 'name' => $user['name']);
                }
            } else {
                $showfields = explode(',',$this->xv_showfields);
                // Complex case: allow specific fields to be selected.
                foreach ($users as $user) {
                    $namevalue = array();
                    foreach ($showfields as $showfield) {
                        $namevalue[] = $user[$showfield];
                    }
                    $this->options[] = array('id' => $user['uid'], 'name' => implode($this->showglue, $namevalue));
                }
            }

        }
    }

    function validateValue($value = null)
    {
       if (!parent::validateValue($value)) return false;
        $valid = true;
        if (!empty($value)) {
            // check if this is a valid user id
            try {
                $uname = xarUserGetVar('uname', $value);
                if (isset($uname)) {
                    $valid = true;
                }
            } catch (NotFoundExceptions $e) {
                $this->invalid = xarML('selection: #(1)', $this->name);
                $this->value = null;
                $valid = false;
            }
        } elseif (empty($value)) {
            $valid = true;
        }
        return $valid;
    }

      public function showInput(Array $data = array())
    {
        if (isset($data['group_list'])) $this->xv_grouplist = $data['group_list'];

        return parent::showInput($data);
    }
    function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;

        if (empty($value)) {
            $user = '';
        } else {
            try {
                $user = xarUserGetVar('name', $value);
                if (empty($user)) {
                    $user = xarUserGetVar('uname', $value);
                }
            } catch (NotFoundExceptions $e) {
                // Nothing to do?
            }
        }
        $data['value'] = $value;
        $data['user'] = $user;
        $data['template'] = isset($template)?$template:$this->template;
        return parent::showOutput($data);
    }

     public function getOptions()
    {
        $select_options = array();

        /*
        if (!empty($this->validation_ancestorgroup_list)) {
            $select_options['ancestor'] = $this->validation_ancestorgroup_list;
        }
        if (!empty($this->validation_parentgroup_list)) {
            $select_options['parent'] = $this->validation_parentgroup_list;
        }
        */
        if (!empty($this->xv_grouplist)) {
            $select_options['group'] = $this->xv_grouplist;
        }
        $users = xarMod::apiFunc('roles', 'user', 'getall', $select_options);
        $options = array();
        foreach($users as $key=>$user)
        {
            $options[] = array('id'=>$user['uid'],'name'=>$user['name']);

        }
        $firstline = $this->getFirstline();
        if (is_array($firstline) && !empty($firstline)) {
           $options = array_merge($firstline,$options);
        }
        return $options;
    }
   public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvalidation = parent::getBaseValidationInfo();

            $validations = array(
                                    'xv_grouplist'    =>  array('label'=>xarML('Group list'),
                                                          'description'=>xarML('List of groups of available users'),
                                                          'propertyname'=>'textbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
                                     'xv_orderlist'    =>  array('label'=>xarML('Order list'),
                                                          'description'=>xarML('Order by fields'),
                                                          'propertyname'=>'textbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
                                    'xv_showfields'    =>  array('label'=>xarML('Field list'),
                                                          'description'=>xarML('List of fields for display'),
                                                          'propertyname'=>'textbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
                                    'xv_userstate'    =>  array('label'=>xarML('User state'),
                                                          'description'=>xarML('Required user state for selection'),
                                                          'propertyname'=>'textbox',
                                                          'propargs' => array('maxsize'=>10),
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
        $validations  = $this->getBaseValidationInfo();
        $baseInfo = array(
                          'id'         => 37,
                          'name'       => 'userlist',
                          'label'      => 'User list',
                          'format'     => '37',
                          'validation' =>  serialize($validations),
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
