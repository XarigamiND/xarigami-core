<?php
/**
 * Privileges tree renderer
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/* Purpose of file:  Privileges tree renderer
 *
 * @package modules
 * @subpackage Privileges module
*/

sys::import('modules.privileges.xarclass.xarprivileges');

class xarTreeRenderer
{

    public $privs;

    // some variables we'll need to hold drawing info
    public $html;
    public $nodeindex;
    public $level;
    public $authid;
    // convenience variables to hold strings referring to pictures
    // moved to constructor to make img paths dynamic

    public $icon_delete;
    public $icon_groups;
    public $icon_remove;
    public $icon_toggle;

    // we'll use this to check whether a group has already been processed
    public $alreadydone;

    /**
     * Constructor
     *
    */
        function __construct()
        {
            $this->privs = new xarPrivileges();
            $this->icon_toggle = 'sprite xs-toggle';
            $this->icon_delete = 'esprite xs-delete';
            $this->icon_groups = 'sprite xs-system-user-groups';
            $this->icon_remove = 'esprite xs-remove';

            $this->authid = xarSecGenAuthKey();
         }

    /**
     * maketrees: create an array of all the privilege trees
     *
     * Makes a tree representation of each privileges tree
     * Returns an array of the trees
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   string $arg indicates what types of elements to get
     * @return  array of trees
     * @throws  none
     * @todo    none
    */
        function maketrees($arg)
        {
            $trees = array();
            foreach ($this->privs->gettoplevelprivileges($arg) as $entry) {
                array_push($trees,$this->maketree($this->privs->getPrivilege($entry['pid'])));
            }

            return $trees;
        }

    /**
     * maketree: make a tree of privileges
     *
     * Makes a tree representation of a privileges hierarchy
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   none
     * @return  boolean
     * @throws  none
     * @todo    none
    */
        function maketree($privilege)
        {
            return $this->addbranches(array('parent'=>$this->privs->getprivilegefast($privilege->getID())));
        }

    /**
     * addbranches: given an initial tree node, add on the branches
     *
     * Adds branches to a tree representation of privileges
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   tree node
     * @return  tree node
     * @throws  none
     * @todo    none
    */
        function addbranches($node)
        {
            $object = $node['parent'];
            $node['expanded'] = false;
            $node['selected'] = false;
            $node['children'] = array();
            foreach($this->privs->getChildren($object['pid']) as $subnode){
                $node['children'][] = $this->addbranches(array('parent'=>$subnode));
            }
            return $node;
        }

    /**
     * drawtrees: create an array of tree drawings
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   string $arg indicates what types of elements to get
     * @return  array of tree drawings
     * @throws  none
     * @todo    none
    */
        function drawtrees($arg)
        {
            $drawntrees = array();
            foreach($this->maketrees($arg) as $tree){
                $drawntrees[] = array('tree'=>$this->drawtree($tree));
            }
            return $drawntrees;
        }

    /**
     * drawtree: create a crude html drawing of the privileges tree
     *
     * We use the data from maketree to create a tree layout
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   array representing an initial node
     * @return  none
     * @throws  none
     * @todo    none
    */

    function drawtree($node)
    {

        $this->html = "\n".'<ul>';
        $this->nodeindex = 0;
        $this->indent = array();
        $this->level = 0;
        $this->alreadydone = array();

        $this->drawbranch($node);
        $this->html .= "\n".'</ul>'."\n";
        return $this->html;
    }

    /**
     * drawbranch: draw a branch of the privileges tree
     *
     * This is a recursive function
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   array representing a tree node
     * @return  none
     * @throws  none
     * @todo    none
    */

    function drawbranch($node)
    {
        $this->level = $this->level + 1;
        $this->nodeindex = $this->nodeindex + 1;
        $object = $node['parent'];

    // check if we've aleady processed this entry
        if (in_array($object['pid'],$this->alreadydone)) {
            $drawchildren = false;
            $node['children'] = array();
        }
        else {
            $drawchildren = true;
            array_push($this->alreadydone,$object['pid']);
        }

    // is this a branch?
        $isbranch = count($node['children'])>0 ? true : false;

    // now begin adding rows to the string
        $this->html .= "\n\t".'<li>'."\n\t\t";

    // this table holds the index, the tree drawing gifs and the info about the privilege

    // this next part holds the icon links
        $itemhash = md5($object['name'] . ':' . microtime());
        $this->html .= "<span class=\"xar-privtree-icons\">";
    // don't allow deletion of certain privileges
        if(!xarSecurityCheck('DeletePrivilege',0,'Privileges',$object['name'])) {
            $this->html .= '<p title="" style="padding-right:0.3em;"
                    id="deletetree_' . $object['pid'] . '_' . $itemhash . '"
                    class="'. $this->icon_delete .' xar-icon-disabled xar-displayinline"><span><xar:mlstring>'.xarML('Disabled').'</xar:mlstring></span></p>';
        } else {
            $this->html .= '<a id="deletetree_' . $object['pid'] . '_' . $itemhash . '"

                    href="' .xarModURL('privileges','admin','deleteprivilege',array('pid'=>$object['pid'], 'authid' => $this->authid, 'returnurl'=>xarModURL('privileges','admin','showprivileges',array(),true))) .'"
                    title="'.xarML('Delete this Privilege').'"
                    class="'. $this->icon_delete .' xar-displayinline"><span>'.xarML('Delete').'</span></a>&#160;';
        }

    // offer to show the users/groups this privilege is assigned to
        $this->html .= '<a
                        href="' .xarModURL('privileges','admin','viewroles', array('pid'=>$object['pid'])) .'"
                        class="'.$this->icon_groups.' xar-displayinline"
                        title="'.xarML('Show the Groups/Users this Privilege is assigned to').'" ><span>'.xarML('Groups').'</span></a>&#160;';

    // offer to remove this privilege from its parent
        if($object['parentid'] == 0) {
            $this->html .= '<p title="' . xarML('Remove this privilege from its parent') . '"
                        style="padding-right:0.3em;"
                        class="'. $this->icon_remove .' xar-displayinline xar-icon-disabled"><span><xar:mlstring>'.xarML('Remove').'</xar:mlstring></span></p>';

        } else {
            $this->html .= '<a
                        href="' .xarModURL('privileges','admin','removebranch', array('childid'=> $object['pid'], 'parentid' => $object['parentid'])) .'"
                        class="'. $this->icon_remove .' xar-displayinline"
                        title="'.xarML('Remove this privilege from its parent').'" ><span>'.xarML('Remove').'</span></a>&#160;';
        }

        $this->html .= "</span>";

    // draw the name of the object and make a link
            $this->html .= '<a
                        href="' .xarModURL('privileges', 'admin','modifyprivilege', array('pid'=>$object['pid'])) .'"
                        title="'.$object['description'].'">' .$object['name'] . '</a>';
        $componentcount = count($this->privs->getChildren($object['pid']));
        $this->html .= $componentcount > 0 ? "&#160;&#160;" .$componentcount . '&#160;'.xarML('components') : "";
        $this->html .= "\n\t\t";

    // we've finished this row; now do the children of this privilege
        $this->html .= $isbranch ? '<ul>' : '';
        $ind=0;
        foreach($node['children'] as $subnode){
            $ind = $ind + 1;

      // draw this child
            $this->drawbranch($subnode);

    // we're done; remove the indent string
        }
        $this->level = $this->level - 1;

    // write the closing tags
        $this->html .= $isbranch ? '</ul>' : '';
    // close the html row
        $this->html .= "</li>\n";


    }

}
?>