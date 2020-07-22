<?php
/**
 * Search  Dynamic Data
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * search dynamicdata (called as hook from search module, or directly with pager)
 * @param string q the query. The query is used in an SQL LIKE query
 * @param int startnum
 * @param array dd_check
 * @param int numitems The number of items to get
 * @return array output of the items found
 */
function dynamicdata_user_search($args)
{
// Security Check
    if(!xarSecurityCheck('ViewDynamicData',0)) return xarResponseForbidden();
    $data = array();
    if (!xarVarFetch('q',        'isset',  $q,        NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('dd_check', 'isset',  $dd_check, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('startnum', 'int:0',  $startnum, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('numitems', 'int:0',  $numitems, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('match',    'isset',  $match,    NULL, XARVAR_DONT_SET)){return;}
    if (!xarVarFetch('sort',    'isset',  $sort,   '', XARVAR_DONT_SET)){return;}
     if(!xarVarFetch('sortorder', 'pre:trim:alpha:lower:enum:asc:desc',   $sortorder, '', XARVAR_DONT_SET)) {return;}
    if (empty($dd_check)) {
        $dd_check = array();
    }
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','user','getmenulinks');

    // see if we're coming from the search hook or not
    if (isset($args['objectid'])) {
        $data['ishooked'] = 1;
    } else {
        $data['ishooked'] = 0;
        $data['q'] = isset($q) ? xarVarPrepForDisplay($q) : null;
        if(!xarVarFetch('modid',    'int',   $modid,     NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('itemtype', 'int',   $itemtype,  NULL, XARVAR_DONT_SET)) {return;}

        if (empty($modid) && empty($itemtype)) {
            $data['gotobject'] = 0;
        } else {
            $data['gotobject'] = 1;
        }
        if (empty($modid)) {
            $modid = xarMod::getId('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
    }
    if (!isset($startnum)) {
        $startnum = 1;
    }
    $itemsperpage = xarModVars::get('dynamicdata','itemsperpage');
    if (!isset($numitems)) {
        $numitems = $itemsperpage;
    }
    if (empty($data['ishooked']) && !empty($data['gotobject'])) {
        // get the selected object
        $objects = array();
        $object = xarMod::apiFunc('dynamicdata','user','getobjectinfo',
                                array('moduleid' => $modid,
                                      'itemtype' => $itemtype));
        if (!empty($object)) {
            $objects[$object['objectid']] = $object;

        }
    } else {
        // get items from the objects table
        $objects = xarMod::apiFunc('dynamicdata','user','getobjects');
    }


    //items used to return as result to the template
    $data['items'] = array();

    $mymodid = xarMod::getId('dynamicdata');
    if ($data['ishooked']) {
        $myfunc = 'view';
    } else {
        $myfunc = 'search';
    }

    $sysobs= xarModGetVar('dynamicdata', 'systemobjects');
    try {
            $sysobs = @unserialize($sysobs);
        } catch (Exception $e) {
            $sysobs = array();
        }
    foreach ($objects as $itemid => $object) {
                $objectid = $object['objectid'];
        // skip the internal objects
        if ($itemid < 3) continue; //jojo - need to do something about these hard coded object ids
        $checkid = $itemid;
        if (in_array($checkid,$sysobs) && !xarSecurityCheck('AdminDynamicDataItem',0,'Item',"$mymodid:$itemid:All"))  continue;
        $modid = $object['moduleid'];
        // don't show data "belonging" to other modules for now
        if ($modid != $mymodid) {
            continue;
        }
        $label      = $object['label'];
        $itemtype   = $object['itemtype'];
        //get all the property fields for the given item
        $fields     = xarMod::apiFunc('dynamicdata','user','getprop',
                                        array('modid'    => $modid,
                                              'itemtype' => $itemtype));

        //only use searchable tagged dd props
        foreach ($fields as $field=>$value) {
            if (isset($value['validation']) && (substr($value['validation'],0,2) == 'a:')) {
                $config = unserialize($value['validation']);
            } else {
                $config = array();
            }
            if (!isset($config['xv_cansearch'])  ||$config['xv_cansearch'] == FALSE) unset($fields[$field]); //we don't want to search it
        }
        //array for holding specific search criteria
        $search = array();
        $criteria = array('q', 'dd_check','match');

        foreach ($criteria as $key) {
            if (isset($$key)) {
                $search[$key] = $$key;
            } else {
                $search[$key] = null;
            }
            unset($key);
        }
       //default to 'like'
        if (empty($match)) {
            $match = 'like';
        }
        $search['field'] = array();
        foreach ($fields as $name => $field) {
            if (!empty($dd_check[$field['name']])) {
                //add the field to the list of searchable fields
                array_push($search['field'],$name);
                //data to send back to form for query
                $fields[$name]['checked'] = 1;
/*                if (!empty($q)) {
                    //jojo: bug 6552 - requires adjustment in dd object master as well
                    //cannot use qstr here
                    //we must escape wildcards and single quotes to pass it to DD whereis
                    $text = str_replace('%','\%',$q);
                    $text = str_replace('_','\_',$text);
                    $text = str_replace("'","\'",$text);
                    $wherelist[] = $name . " LIKE '%" . $text."%'" ;
                }
  */
            }
        }

        //build the wherelist
        $wherelist = array();
        if (isset($q) && $q !== '' && !empty($search['field'])) {
            $clause = getwhereclause($q, $match);
            $wherelist = array();
            if (!empty($clause)) {
                foreach ($search['field'] as $field) {
                    $wherelist[$field] = $clause;
                }
            }
        }

        if (empty($wherelist)) {
            $result = null;
        }elseif (!empty($q) && count($wherelist) > 0) {
             $itemresult = Dynamic_Object_Master::getObjectList(array(
                        'moduleid'=>$modid,
                        'itemtype'=>$itemtype,
                        'objectid'=>$objectid,
                        'sort'        => $sort,
                        'sortorder'   => $sortorder

                        ));
             //jojo - when is this not null
             if (!empty($itemresult->where)) {
                $join = 'and';
             } else {
                 $join = '';
             }
            foreach ($wherelist as $name => $clause) {
                $itemresult->addWhere($name, $clause, $join);
                // CHECKME: use OR by default here !
                $join = 'or';
            }
            // count the items
           $itemcount = $itemresult->countItems();
            // get the items
           $founditems= $itemresult->getItems();
           $status = 1;
           //get the pageurl info


           $pagerurl = xarModURL('dynamicdata','user','search',
                                      array('modid' => ($modid == $mymodid) ? null : $modid,
                                            'itemtype' => empty($itemtype) ? null : $itemtype,
                                            'q' => $q,
                                            'dd_check' => $dd_check));

            //jojo - work out other requirements and put them all in args list
            $layout ='default';
            $result = $itemresult->showView(array(    'layout'      => $layout,
                                                      'pagerurl'    => $pagerurl,
                                                      'itemcount'   => $itemcount,
                                                      'sort'        => $sort,
                                                      'sortorder'   => $sortorder
                                                    ));
        }


        //query info
        $data['match'] = $match;

       // $data['search'] = $search;
        // nice(r) URLs
        if ($modid == $mymodid) {
            $modid = null;
        }
        if ($itemtype == 0) {
            $itemtype = null;
        }
        if (isset($data['q']) && $data['q'] !== '') {
            $data['q'] = xarVarPrepForDisplay($data['q']);
        }
        $data['items'][] = array('link'     => xarModURL('dynamicdata','user',$myfunc,
                                                         array('modid' => $modid,
                                                               'itemtype' => $itemtype)),
                                 'label'    => $label,
                                 'modid'    => $modid,
                                 'itemtype' => $itemtype,
                                 'fields'   => $fields,
                                 'result'   => $result,
                                );

       $data['options'] = array(  'like'  => 'like',
                                   'start' => 'starts with',
                                   'end'   => 'ends with',
                                   'eq'    => 'exact match',
                                   'in'    => 'in list a,b,c',
                                   'gt'    => 'greater than',
                                   'lt'    => 'less than',
                                   'ne'    => 'not equal to');

    $data['searchlink'] = xarModURL('dynamicdata','user','search',
                                      array('modid' => ($modid == $mymodid) ? null : $modid,
                                            'itemtype' => empty($itemtype) ? null : $itemtype));
    }

    return $data;
}
function getwhereclause($value, $match = 'like')
{
    // default match type is 'like' here
    if (empty($match)) {
        $match = 'like';
    }
    // escape single quotes
    $value = str_replace("'", "\\'", $value);
    $clause = '';
    switch ($match)
    {
        case 'start':
            // escape LIKE wildcards
            $value = str_replace('%', '\%', $value);
            $value = str_replace('_', '\_', $value);
            $clause = " LIKE '" . $value . "%'";
            break;

        case 'end':
            // escape LIKE wildcards
            $value = str_replace('%', '\%', $value);
            $value = str_replace('_', '\_', $value);
            $clause = " LIKE '%" . $value . "'";
            break;

        case 'eq':
            if (is_numeric($value)) {
                $clause = ' = ' . $value;
            } elseif (is_string($value)) {
                $clause = " = '" . $value . "'";
            }
            break;

        case 'gt':
            if (is_numeric($value)) {
                $clause = ' > ' . $value;
            } elseif (is_string($value)) {
                $clause = " > '" . $value . "'";
            }
            break;

        case 'ge':
            if (is_numeric($value)) {
                $clause = ' >= ' . $value;
            } elseif (is_string($value)) {
                $clause = " >= '" . $value . "'";
            }
            break;

        case 'lt':
            if (is_numeric($value)) {
                $clause = ' < ' . $value;
            } elseif (is_string($value)) {
                $clause = " < '" . $value . "'";
            }
            break;

        case 'le':
            if (is_numeric($value)) {
                $clause = ' <= ' . $value;
            } elseif (is_string($value)) {
                $clause = " <= '" . $value . "'";
            }
            break;

        case 'ne':
            if (is_numeric($value)) {
                $clause = ' != ' . $value;
            } elseif (is_string($value)) {
                $clause = " != '" . $value . "'";
            }
            break;

        case 'in':
            if (is_string($value)) {
                $value = explode(',', $value);
            }
            if (count($value) > 0) {
                if (is_numeric($value[0])) {
                    $clause = ' IN (' . implode(', ', $value) . ')';
                } elseif (is_string($value[0])) {
                    $clause = " IN ('" . implode("', '", $value) . "')";
                }
            }
            break;

        case 'like':
        default:
            // escape LIKE wildcards
            $value = str_replace('%', '\%', $value);
            $value = str_replace('_', '\_', $value);
            $clause = " LIKE '%" . $value . "%'";
            break;
    }
    return $clause;
}
/**
 * Check the range for two values and return the WHERE clause(s)
 *
 * @param string $value1 first value
 * @param string $value2 second value
 * @return array where clause(s)
 */
function checkrange($value1,$value2)
{
    $clauses = array();
    if (isset($value1) && $value1 !== '' && isset($value2) && $value2 !== '') {
        if ($value1 !== $value2) {
            // greater than or equal to the first value
            $clause = $this->getwhereclause($value1, 'ge');
            if (!empty($clause)) {
                $clauses[] = $clause;
            }
            // less than or equal to the second value
            $clause = $this->getwhereclause($value2, 'le');
            if (!empty($clause)) {
                $clauses[] = $clause;
            }
        } else {
            // equal to the value
            $clause = $this->getwhereclause($value1, 'eq');
            if (!empty($clause)) {
                $clauses[] = $clause;
            }
        }
    } elseif (isset($value1) && $value1 !== '') {
        // greater than or equal to the first value
        $clause = $this->getwhereclause($value1, 'ge');
        if (!empty($clause)) {
            $clauses[] = $clause;
        }
    } elseif (isset($value2) && $value2 !== '') {
        // less than or equal to the second value
        $clause = $this->getwhereclause($value2, 'le');
        if (!empty($clause)) {
            $clauses[] = $clause;
        }
    } else {
    }
    return $clauses;
}
?>
