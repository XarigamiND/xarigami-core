<?php
/**
 * View users
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare <jojodee@xaraya.com>
 * View a listing of users
 *
 * This function shows a page with tabbed for browsing online or offline users
 * You can search for users by entering their name, or browse by their display name
 *
 * @param int startnum
 * @param string phase
 * @param string name
 * @param string letter The first letter of the display name to show the roles
 * @param string search (max 100 characters) Search for a user with this string
 * @param string order What order to show the results in
 * @return array
 */
function roles_user_view($args)
{
    extract($args);

    // Get parameters
    if(!xarVarFetch('startnum', 'int:1', $startnum, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('phase', 'enum:active:viewall', $phase, 'viewall', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('name', 'notempty', $data['name'], '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('reset', 'int', $reset, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('letter', 'str:1', $letter, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('search', 'str:1:100', $search, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('order', 'str:1:', $order, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc',   $sort,    '', XARVAR_NOT_REQUIRED)) return;
    // Bug 3338: disable 'selection' since it allows a user to manipulate the query directly
    //if(!xarVarFetch('selection', 'str', $selection, '', XARVAR_DONT_SET)) {return;}
    if (!isset($selection)) {$selection = '';}

    $rolefields = array('name','uname','email','uid','state','date_reg');
    $ddsort = false; //track if ddsort
    $ddorder = NULL;
    if (!empty($order) && !in_array($order,$rolefields)) {
        $ddsort = true;
        $ddorder = $order;
    }

    if ($phase == 'active') {
        $ddsort = false;//for now messy to do this with active only
        $order = 'name'; //make sure we dont' try and sort by an unknown value
    }
    if (isset($reset) && $reset==1) {
        xarSession::setVar('viewusers.rolesquery','');
    }
    if (empty($order) && is_null($search) && is_null($letter) ) $order ='name';
    $data['items'] = array();
    $lastquery = xarSessionGetVar('viewusers.rolesquery');
    // Specify some labels for display
    $data['pager'] = '';
    //Memberlist state set in Roles modifyconfig defines whether to show the members list or not
    $memberliststate = xarModGetVar('roles', 'memberliststate');
    //Always allow admin to see the list
    if (($memberliststate<=0) && !xarSecurityCheck('AdminRole',0)) {
        $msg = xarML('You do not have a sufficient access level to view the members list.');
         return xarResponseForbidden($msg);
    }
    // Security Check
    if (!xarSecurityCheck('ViewRoles',0)) return;

    //initialise some required arrays
    $properties = array();
    $values= array();
    $propertieslist = array();
    $itemcount = 0;
    $uidkeys = array();

    // Need the database connection for quoting strings.
    $dbconn = xarDB::$dbconn;

    if ($letter && !$ddsort) {
        if ($letter == 'Other') {
            // TODO: check for syntax in other databases or use a different matching method.
            $selection = " AND ("
                .$dbconn->substr."(".$dbconn->upperCase."(xar_name),1,1) < 'A' OR "
                .$dbconn->substr."(".$dbconn->upperCase."(xar_name),1,1) > 'Z')";
            $data['msg'] = xarML(
                'Display name begins with a character not in the alphabetic listing'
            );
        } else {
            $selection = ' AND xar_name LIKE ' . $dbconn->qstr($letter.'%');
            if(strtolower($phase) == 'active') {
                $data['msg'] = xarML('Members Online whose Display name begins with "#(1)"', $letter);
            } else {
                $data['msg'] = xarML('Members whose Display name begins with "#(1)"', $letter);
            }
        }
    } elseif ($search && !$ddsort) {
        // Quote the search string
        $ddsort = false; //ensure we do not retain the ddsort options
        $qsearch = $dbconn->qstr('%'.$search.'%');
        $data['msg'] = xarML("Display name contains '#(1)', ", $search);
        $selection = ' AND (';
        $selection .= '(xar_name LIKE ' . $qsearch . ')';
        $selection .= ' OR (xar_uname LIKE ' . $qsearch . ')';
        if (xarModGetVar('roles', 'searchbyemail')) {
            $selection .= ' OR (xar_email LIKE ' . $qsearch . ')';
            $data['msg'] = xarML("Display name or Email Address contains '#(1)', ", $search);
        }
        $order = isset($order)?$order:'name';
        $data['msg'] .= xarML('Sorted by "#(1)"', $order);

        $selection .= ")";
    }elseif  ($ddsort && $phase == 'viewall' && xarMod::isHooked('dynamicdata','roles')) {
          $where = NULL;
          $data['msg'] = '';
         if (isset($letter) && !empty($letter)) {
            $search = NULL;
            $where= "$ddorder LIKE '".$letter."%'";
            $select = ucfirst($ddorder);
            $data['msg'] .= xarML("#(1) beginning with '#(2)', ",$select, $letter);
         } elseif (isset($search) && !empty($search)) {
             $letter = '';
            $where = "$ddorder LIKE '%".$search."%'";
            $select = ucfirst($ddorder);
            $data['msg'] .= xarML("#(1) contains '#(2)', ",$select,$search);
         }
         $itemcount= xarMod::apiFunc('dynamicdata','user','countitems',
                            array(  'module'=>'roles',
                                    'itemtype'=>0,
                                    'sort'=>$ddorder,
                                    'sortorder'=>strtoupper($sort),
                                    'where'=> $where));

         $objectinfo= xarMod::apiFunc('dynamicdata','user','getitems',
                            array(  'module'=>'roles',
                                    'itemtype'=>0,
                                    'sort'=>$ddorder,
                                    'sortorder'=>strtoupper($sort),
                                    'where'=> $where,
                                    'getobject'=>1,
                                    'startnum'=>$startnum,
                                    'numitems' => xarModGetVar('roles', 'itemsperpage')
                                    ));


        if ($objectinfo) {
            $propertieslist = $objectinfo->getProperties();
            foreach($propertieslist as $name=>$info) {
                //do not show hidden properties or display only - we need to do this ourselves here
                if ($info->status ==1) {
                    $properties[$name]= $propertieslist[$name];
                }
            }
            $values=$objectinfo->items;
        }

        $uidkeys = array();
        foreach ($values as $k=>$v) {
            $uidkeys[]=$k;
        }

        $selection = NULL;
        if (empty($search) && empty($letter)) {

            $data['msg'] .= xarML("All members sorted by '#(1)'", ucfirst($ddorder));
        } else {

            $data['msg'] .= xarML("Sorted by '#(1)'", ucfirst($ddorder));
        }
    } else {
        if(strtolower($phase) == 'active') {
            $data['msg'] = xarML("All members online");
        } else {
            $orderlabel = $order;
            $orderlabel = isset($orderlabel) && $orderlabel == 'name'?xarML('Display name'): (isset($order)?$order:'Display name');
            $data['msg'] = xarML("All members sorted by '#(1)'",ucfirst($orderlabel));

        }
    }

    $order= !empty($order) ?$order : (isset($lastquery['order']) ?$lastquery['order']:'name') ;
    $sort = !empty($sort) ? $sort : (isset($lastquery['sort']) ? $lastquery['sort']:'asc') ;


    $data['order'] = $order;
    $data['letter'] = $letter;
    $data['search'] = $search;
    $data['searchlabel'] = xarML('Go');
    $orderlabels = array('name' => xarML('Name'),
                         'display name' => xarML('Display Name'));
    $data['orderlabel'] = isset($orderlabels[$order])? $orderlabels[$order] : xarML('Undefined field in xaruser/view.php');


    $data['sort'] = $sort;
    $data['alphabet'] = array(
        'A', 'B', 'C', 'D', 'E', 'F',
        'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z'
    );

    switch(strtolower($phase)) {
        case 'active':
            $data['phase'] = 'active';
            //jojodee - time up until session inactivity time out? Surely it's InactivityTimeout(min) not Duration(days)
            $filter = time() - (xarConfigGetVar('Site.Session.InactivityTimeout') * 60);
            $data['title'] = xarML('Online Members');

            $data['total'] = xarMod::apiFunc('roles', 'user', 'countallactive',
                array(
                    'filter'   => $filter,
                    'selection'   => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false,
                    'unique' => false //we only have logged in users here
                )
            );
            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Active Members')));

            if (!$data['total']) {
                $data['message'] = xarML('There are no online members selected');
                $data['total'] = 0;
                return $data;
            }

            // Now get the actual records to be displayed
            //careful with dd ordering - we need to set a default here as dd not queried
            // with paging we need to get all of these and let dd handle the pager
            $items = xarMod::apiFunc('roles', 'user', 'getallactive',
                array(
                    'startnum' => $startnum,
                    'filter'   => $filter,
                    'order'   => $ddsort? NULL:$order,
                    'sort'  => $sort,
                    'selection'   => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false,
                    'unique' => false, //we only have logged in users here
                    'numitems' => xarModGetVar('roles', 'itemsperpage')
                )
            );

            break;

        case 'viewall':
            $data['phase'] = 'viewall';
            $data['title'] = xarML('All Members');

            $data['total'] = xarMod::apiFunc('roles', 'user', 'countall',
                array(
                    'selection' => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false
                )
            );

            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('All Members')));

            if (!$data['total']) {
                $data['message'] = xarML('There are no members selected');
                $data['total'] = 0;
                return $data;
            }

            // Now get the actual records to be displayed
            $items = xarMod::apiFunc(
                'roles', 'user', 'getall',
                array(
                    'startnum' => $startnum,
                    'order' => $ddsort? -1:$order,
                    'sort' =>  $ddsort? NULL:$sort,
                    'selection' => $selection,
                    'include_anonymous' => false,
                    'include_myself' => false,
                    'uidlist' => $uidkeys,
                    'numitems' => xarModGetVar('roles', 'itemsperpage')
                )
            );

            break;
    }

    // keep track of the selected uid's
    $data['uidlist'] = array();
    xarSession::setVar('viewusers.rolesquery',$data);
    // Check individual privileges for Edit / Delete / View
    // if memberliststate is 2 or 4 - restricted display to only ReadRole
    // we should decrement count here rather than adding new args to the get, count etc api functions
    $readonlycount = 0;
    $alloweditems = array();
    foreach ($items as $i=>$item) {

        $item = $items[$i];
        //restricted view - only include the member if there is read access
        if (($memberliststate ==2 || $memberliststate ==4)  &&  !xarSecurityCheck('ReadRole',0,'Roles',$item['uid'])) {
            //we don't include the member
        } else {
            //it is either not restricted view or the user has read level access for the member
            $data['uidlist'][] = $item['uid'];
            $readonlycount = $readonlycount+1;

            // Grab the list of groups this role belongs to
            $groups = xarMod::apiFunc('roles', 'user', 'getancestors', array('uid' => $item['uid']));
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $items[$i]['groups'][$group['uid']] = $group['name'];
                }

                // Change email to a human readible entry.  Anti-Spam protection.
                if (xarUserIsLoggedIn()) {
                    $items[$i]['emailurl'] = xarModURL('roles', 'user', 'email', array('uid' => $item['uid']));
                } else {
                    $items[$i]['emailurl'] = '';
                }

                if (empty($items[$i]['ipaddr'])) {
                    $items[$i]['ipaddr'] = '';
                }

                $items[$i]['emailicon'] = 'dsprite xs-mail';
                $items[$i]['infoicon'] = 'sprite xs-info';
            }
            $alloweditems[$i] = $items[$i];
        }
    }
    $data['dummyimage'] = xarTplGetImage('blank.gif', 'base');

    $items = $alloweditems;

  //  $numitems = $readonlycount;

    if (!$ddsort && xarMod::isHooked('dynamicdata','roles')) {
        $objectinfo= xarMod::apiFunc('dynamicdata','user','getitems',
                            array(  'module'=>'roles',
                                    'itemtype'=>0,
                                    'itemids'=> $data['uidlist'] ,
                                    'getobject'=>1
                                    ));
        if ($objectinfo) {
            $propertieslist = $objectinfo->getProperties();
            foreach($propertieslist as $name=>$info) {
                //do not show hidden properties or display only - we need to do this ourselves here
                if ($info->status ==1) {
                    $properties[$name]= $propertieslist[$name];
                }
            }
            $values=$objectinfo->items;
        }
    } elseif ($ddsort) {
        //put the items in correct order
        $temp = array();
        foreach ($uidkeys as $k=>$place) {
            if (isset($items[$place])) {
                $temp[] = $items[$place];
            }
        }
        $items = $temp;
        $data['total'] = $itemcount;
    }
    $data['values']= $values;
    //some items may not have any DD so be attentive to the sort on DD
    // we can't use some normal array functions
    $data['properties'] = $properties;
    $data['propertieslist'] = $propertieslist;
    $numitems = xarModGetVar('roles', 'itemsperpage');

    $data['pmicon'] = '';
    $data['items'] = $items;

    $pagerfilter['phase'] = $phase;
    $pagerfilter['order'] = $order;
    $pagerfilter['sort'] = $sort;
    $pagerfilter['letter'] = $letter;
    $pagerfilter['search'] = $search;
    $pagerfilter['startnum'] = '%%';

    $data['pager'] = xarTplGetPager(
            $startnum,
            $data['total'],
            xarModURL('roles', 'user', 'view', $pagerfilter),
            $numitems
        );
    $data['sortimgclass'] = '';
    $data['sortimglabel'] = '';
    if ($data['sort'] == 'asc') {
        $data['sortimgclass'] = 'esprite xs-sorted-asc';
        $data['sortimglabel'] = xarML('Ascending');
        $data['sortimg'] = xarTplGetImage('icons/sorted-asc.png','base');
    } else {
        $data['sortimgclass'] = 'esprite xs-sorted-desc';
         $data['sortimglabel'] = xarML('Descending');
        $data['sortimg'] = xarTplGetImage('icons/sorted-desc.png','base');
    }
    //decide what image goes where
    $sortimage = array();

    $headerarray= array('name','email','uname',$ddorder);
    foreach ($headerarray as $headername) {
        $sortimage[$headername] = false;
        if ($data['order'] == $headername) $sortimage[$headername] = true;
    }

    $data['sortimage'] = $sortimage;
    $data['dsort'] = ($data['sort'] == 'asc') ? 'desc' : 'asc';

    return $data;
}

function cmp($a,$b)
{
    return strcmp($a['sortorder'], $b['sortorder']);
}

?>
