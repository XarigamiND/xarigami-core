<?php
/**
 * Roles tree renderer
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Roles tree renderer
 *
 * @package modules
 * @subpackage Roles module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */


sys::import('modules.roles.xarclass.xarroles');
class xarTreeRenderer
{
    public $roles;
    public $tree;
    public $treenode;
    public $treeitems;
    public $levels;

    // some variables we'll need to hold drawing info
    public $html;
    public $nodeindex;
    public $indent;
    public $level;
    public $isbranch;
    public $drawchildren;
    public $alreadydone;

    public $icon_users;
    public $icon_delete;
    public $icon_mail;
    public $icon_privileges;
    public $icon_test;
    public $dummyimage;

    public $authid;

    /**
     * Constructor
     */
    function __construct($allowtoggle=0)
    {
        $data['onclick'] = $allowtoggle ? "toggleBranch(this,this.parentNode.lastChild)" : "";

        $this->roles = new xarRoles();
        $this->setitem(1, "deleteitem");
        $this->setitem(2, "leafitem");
        $this->setitem(3, "emailitem");
        $this->setitem(4, "privilegesitem");
        $this->setitem(5, "testitem");
        $this->setitem(6, "treeitem");
        $this->setitem(7, "descriptionitem");
        $this->icon_users = 'sprite xs-system-users';
        $this->icon_delete = 'esprite xs-delete';
        $this->icon_mail = 'sprite xs-mail-message-new';
        $this->icon_privileges = 'sprite xs-privileges';
        $this->icon_test = 'esprite xs-test';
        $this->dummyimage = xarTplGetImage('blank.gif','base');
        $this->authid = xarSecGenAuthKey();
    }

    /**
     * maketree: make a tree of the roles that are groups
     *
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function maketree($topuid='',$levels=0)
    {
        $this->levels = $levels;
        if ($topuid == '') $topuid = xarModGetVar('roles', 'everybody');
        $initialnode = array(
                    'parent' => $this->roles->getgroup($topuid),
                    'level' => 1,
                    'display'=> TRUE
                    );
//        $this->tree = $this->addbranches($initialnode);
        return $this->addbranches($initialnode);
    }

    /**
     * addbranches: given an initial tree node, add on the branches that are groups
     *
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param tree $ node
     * @return tree node
     * @throws none
     * @todo none
     */
    function addbranches($node)
    {
        $object = $node['parent'];
        $level = $node['level'];
        $node['children'] = array();
        if ($level == $this->levels) return $node;
        $subgroups = $this->roles->getsubgroups($object['uid']) ;
        foreach($subgroups as $subnode) {
            $displaybranch = FALSE;
            if (xarSecurityCheck('ModerateRole',0,'Roles',$subnode['uid']) || xarSecurityCheck('ModerateGroupRoles',0,'Group',$subnode['uid'])) {
                $displaybranch = TRUE;
            }

            if (!$displaybranch) {
                $subnode['name'] = 'FROZENITEM';
            }
            $nextnode = array(
                        'parent' => $subnode,
                        'level' => $level + 1,
                        'display' =>$displaybranch
                        );
            $node['children'][] = $this->addbranches($nextnode);

        }
        return $node;
    }

    /**
     * drawtree: create a crude html drawing of the role tree
     *
     * We use the data from maketree to create a tree layout
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param array $ representing an initial node
     * @return none
     * @throws none
     * @todo none
     */

    /**
     * drawtree: draws the role tree
     * sets everything up and draws the first node
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param nested array representing a tree
     * @return none
     * @throws none
     * @todo none
     */

    function drawtree($tree='')
    {
        if ($tree == '') $tree = $this->tree;
        if ($tree == '') {
            throw new EmptyParameterException(null,'A tree must be defined before attempting to display.');
        }
        $this->nodeindex = 0;
        $this->indent = array();
        $this->level = 0;
        $this->alreadydone = array();
        $data['content'] = $this->drawbranch($tree);
        return xarTplObject('roles', 'tree', 'drawing',$data);
    }

    /**
     * drawbranch: draw a branch of the role tree
     *
     * This is a recursive function
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param array $ representing a tree node
     * @return none
     * @throws none
     * @todo none
     */

    function drawbranch($node)
    {
        $this->level ++;
        $this->nodeindex = $this->nodeindex + 1;
        $object = $node['parent'];
        $this->treenode = $object;
        // check if we've aleady processed this entry
        if (in_array($object['uid'], $this->alreadydone)) {
            $this->drawchildren = false;
            $node['children'] = array();
        } else {
            $this->drawchildren = true;
            $this->alreadydone[] = $object['uid'];
            $this->display = $node['display'];
        }
        // is this a branch?
        $isbranch = count($node['children']) > 0 ? true : false;
        // now begin adding rows to the string


//-------------------- Assemble the data for a single row
        $html = "<span class=\"xar-roletree-icons\">";
        for ($i=1,$max = count($this->treeitems); $i <= $max; $i++) {
            $func = $this->treeitems[$i];
            $html .= $this->{$func}();
            if ($func == 'testitem') {
                $html .= "</span>";
            }
        }

//-------------------- We've finished this row; now do the children of this role
        $ind = 0;
        if ($isbranch) {
            $html .= "<ul>";
        }
        foreach($node['children'] as $subnode) {
            $ind = $ind + 1;
            // draw this child
            $html .= $this->drawbranch($subnode);
        }
        if ($isbranch) {
            $html .= "</ul>";
        }


        $this->level --;

//-------------------- Put everything in the container
            $data['nodeindex'] = $this->nodeindex;
            $data['idindex'] = $data['nodeindex'] +rand($data['nodeindex'],500);
            $data['content'] = $html;
            if ($isbranch) {
                $data['type'] = "branch";
            } else {
                $data['type'] = "leaf";
            }
        return xarTplObject('roles', 'container', 'drawing',$data);
    }

    /**
     * drawindent: draws the graphic part of the tree
     *
     * A helper funtion to output a HTML string containing the pictures for
     * a line of the tree
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return string
     * @throws none
     * @todo none
     */

    function drawindent()
    {
        $html = '';
        return $html;
    }


    /**
     * Functions that define the items in each row of the display
     */

    function leafitem()
    {
        if ((!xarSecurityCheck('EditRole',0,'Roles',$this->treenode['uid']) && !xarSecurityCheck('ModerateGroupRoles',0,'Group',$this->treenode['uid']) ) || $this->treenode['users'] == 0 || (!$this->drawchildren)) {
             $data['permitted'] = false;
        } else {
             $data['permitted'] = true;

        }
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'showusers',
                            array('uid' => $this->treenode['uid'], 'reload' => 1));
            $data['leafitemtitle'] = xarML('Show the Users in this Group');
            $data['leafitemimage'] = $this->icon_users;
            $data['dummyimage'] = $this->dummyimage;
            return xarTplObject('roles', 'leaf', 'showuser', $data);

    }

    function deleteitem()
    {

        if ((!xarSecurityCheck('DeleteRole',0,'Roles',$this->treenode['uid']) && !xarSecurityCheck('DeleteGroupRoles',0,'Groups',$this->treenode['uid']) ) || ($this->treenode['users'] > 0) || (!$this->drawchildren)) {
            $data['permitted'] = false;
        } else {
            $data['permitted'] = true;
        }
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'deleterole',
                            array('uid' => $this->treenode['uid'], 'authid' => $this->authid));
            $data['leafitemtitle'] = xarML('Delete this Group');
            $data['leafitemimage'] = $this->icon_delete;
            $data['leafitemid'] = $this->treenode['uid'];
            $data['leafitemhash'] = md5($this->treenode['uid'] . ':' . microtime());
            return xarTplObject('roles', 'leaf', 'deleteuser', $data);

    }

    function emailitem()
    {
        if (!xarSecurityCheck('MailRoles',0,'Mail',$this->treenode['uid']) && !xarSecurityCheck('AdminRole',0,'Roles',$this->treenode['uid']) || $this->treenode['users'] == 0 || (!$this->drawchildren)) {
           $data['permitted'] = false;
        } else {
           $data['permitted'] = true;
        }
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'createmail',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML('Email the Users in this Group');
            $data['leafitemimage'] = $this->icon_mail;
            $data['dummyimage'] = $this->dummyimage;
            return xarTplObject('roles', 'leaf', 'email', $data);

    }

    function privilegesitem()
    {
        if ((!xarSecurityCheck('AddRole',0,'Roles',$this->treenode['uid']) && !xarSecurityCheck('AddGroupRoles',0,'Group',$this->treenode['uid'])) ||  (!$this->drawchildren) || !xarSecurityCheck('ReadPrivilege',0)) {

            $data['permitted'] = false;
        } else {
            $data['permitted'] = true;
        }
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'showprivileges',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML('Show the Privileges assigned to this Group');
            $data['leafitemimage'] = $this->icon_privileges;

            return xarTplObject('roles', 'leaf', 'showprivileges', $data);

    }

    function testitem()
    {
        if ((!xarSecurityCheck('AddRole',0,'Roles',$this->treenode['uid']) && !xarSecurityCheck('AddGroupRoles',0,'Group',$this->treenode['uid'])) ||  (!$this->drawchildren) || !xarSecurityCheck('ReadPrivilege',0)) {
             $data['permitted'] = false;
        } else {
             $data['permitted'] = true;

        }
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'testprivileges',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML("Test the Privileges of this group");
            $data['leafitemimage'] = $this->icon_test;

            return xarTplObject('roles', 'leaf', 'testprivileges', $data);

    }

    function descriptionitem()
    {
        // if we've already done this entry skip the links and just tell the user
        if (!$this->drawchildren) {
           $data['leafitemurl'] = xarModURL('roles', 'admin', 'modifyrole',
                            array('uid' => $this->treenode['uid']));
            $data['leafitemtext'] = $this->treenode['name'];
            $data['leafitemtitle'] = xarML("Modify this Group");
            $data['leafitemtext'] = $this->treenode['name'];
            $data['uid'] = $this->treenode['uid'];
            return xarTplObject('roles', 'leaf', 'placeholder', $data);
        } else {
            $numofsubgroups = count($this->roles->getsubgroups($this->treenode['uid']));
            $subgroups = $numofsubgroups == 1 ? xarML('subgroup') : xarML('subgroups');
            $users = $this->treenode['users'] == 1 ? xarML('user') : xarML('users');
            $data['uid'] = $this->treenode['uid'];
            $data['leafitemurl'] = xarModURL('roles', 'admin', 'modifyrole',
                        array('uid' => $this->treenode['uid']));
            $data['leafitemtitle'] = xarML("Modify this Group");
            $data['leafitemtext'] = $this->treenode['name'];

            $data['leafitemdescription'] = $numofsubgroups . " " . $subgroups . ' | ' . $this->treenode['users'] . " " . $users;
            //remove data for frozen items
            if ($this->treenode['name'] == 'FROZENITEM') {
                $data['leafitemurl'] = '';
                $data['leafitemtext'] = xarML('No Access');
            }

            return xarTplObject('roles', 'leaf', 'modifyuser', $data);
        }
    }
 function treeitem()
    {
        $html = '';
        if ($this->isbranch) {
            if ($this->nodeindex != 1) {
            }
        } else {
            if ($this->nodeindex != 1) {
            }
        }
        return $html;
    }

//-----------------------------------------------------------------------

    function setitem($pos=1,$item ='')
    {
        $this->treeitems[$pos] =& $item;
    }

    function clearitems()
    {
        $this->treeitems = array();
    }
}

?>
