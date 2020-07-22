<?php
/**
 * Decode short URLS
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 * @param array $params containing the elements of PATH_INFO
 * @param  int $fieldforshorturl Use a field for short url
 * @param  string $shorturlfield Name of field to use for short url
 * @param  int shorturlduplicates Encode method: 1 - append id, 2 - use ID as well, always, 3 - ignore duplicates use field only
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function dynamicdata_userapi_decode_shorturl($params)
{
    static $objectcache = array();
    static $fieldforshorturl =  0;
    static $shorturlfield = '';
    static $shorturlduplicates = 2;

    if (count($objectcache) == 0) {
        $objects = xarMod::apiFunc('dynamicdata','user','getobjects');
        foreach ($objects as $object) {
            if (!empty($object['config']) && is_string($object['config']) && substr($object['config'],0,2) == 'a:') {
                $config = unserialize($object['config']);
                 $fieldforshorturl =  isset($config['fieldforshorturl'])?$config['fieldforshorturl']:FALSE;
                 $shorturlfield =  isset($config['shorturlfield'])?$config['shorturlfield']:'';
                 $shorturlduplicates = isset($config['shorturlduplicates'])?$config['shorturlduplicates']:1;
            }
            $objectcache[$object['name']] = array('modid'              => $object['moduleid'],
                                                  'itemtype'            => $object['itemtype'],
                                                  'fieldforshorturl'    => $fieldforshorturl,
                                                  'shorturlduplicates'  => $shorturlduplicates,
                                                  'shorturlfield'      => $shorturlfield);
        }
    }
    $args = array();

    $module = 'dynamicdata';
    $foundalias = 0;
    // Check if we're dealing with an alias here
    if ($params[0] != $module) {
        $alias = xarModGetAlias($params[0]);
        // yup, looks like it
        if ($module == $alias) {
            if (isset($objectcache[$params[0]])) {
                $args['modid'] = $objectcache[$params[0]]['modid'];
                $args['itemtype'] = $objectcache[$params[0]]['itemtype'];
                $fieldforshorturl =  $objectcache[$params[0]]['fieldforshorturl'];
                $shorturlfield = $objectcache[$params[0]]['shorturlfield'];
                $shorturlduplicates = $objectcache[$params[0]]['shorturlduplicates'];
                $foundalias = 1;
            } else {
                // we don't know this one...
                return;
            }
        } else {
            // we don't know this one...
            return;
        }
    }

    //TODO - setting to determine if title or other field should be used as a short URL

    if (empty($params[1]) || preg_match('/^index/i',$params[1])) {
        if (count($args) > 0) {
            return array('view', $args);
        } else {
            return array('main', $args);
        }

    } elseif (preg_match('/^c(_?[0-9 +-]+)/',$params[1],$matches)) {
        $catid = $matches[1];
        $args['catid'] = $catid;
        return array('view', $args);

    } elseif (preg_match('/^(\d+)/',$params[1],$matches)) {
        $itemid = $matches[1];
        $args['itemid'] = $itemid;
        return array('display', $args);

    } elseif (isset($objectcache[$params[1]])) {
        $args['modid'] = $objectcache[$params[1]]['modid'];
        $args['itemtype'] = $objectcache[$params[1]]['itemtype'];
        if (empty($params[2]) || preg_match('/^index/i',$params[2])) {
            return array('view', $args);
        } elseif (preg_match('/^c(_?[0-9 +-]+)/',$params[2],$matches)) {
            $catid = $matches[1];
            $args['catid'] = $catid;
            return array('view', $args);
        } elseif (preg_match('/^(\d+)/',$params[2],$matches)) {
            $itemid = $matches[1];
            $args['itemid'] = $itemid;
            return array('display', $args);
        } else {
            // we don't know this one...
        }

    } else {
        // normalize $params to articles/pubtype/... for title decoding
        if ($foundalias) {
            array_unshift($params, $module);
        }
        if (!empty($params[2])) {
            if (preg_match('/^(\d+)$/',$params[2],$matches)) {

                $itemid = $matches[1];
                $args['itemid'] = $itemid;
                return array('display', $args);
            } elseif (empty($params[3]) && preg_match('/^c(_?[0-9 +-]+)/',$params[2],$matches)) {
                $catid = $matches[1];
                $args['catid'] = $catid;
                // Decode should return the same array of arguments that was passed to encode
                if( strpos($catid,'+') === FALSE )
                {
                    $args['cids'] = explode('-',$catid);
                } else {
                    $args['cids'] = explode('+',$catid);
                    $args['andcids'] = TRUE;
                }
                return array('view', $args);
            } elseif (preg_match('/^search\&|^search\?|^search$/i',$params[2])) {
                return array('search', $args);
            } elseif ($params[2] == 'redirect') {
                if (!empty($params[3]) && preg_match('/^(\d+)/',$params[3],$matches)) {
                    $args['itemid'] = $matches[1];
                    return array('redirect', $args);
                }
            } else {

                // We may also pass (xart-000596)
                // catid providing the category of origin
                // /[modules]/[pubtype]/c[cid]/[aid or article title]
                if (preg_match('/^c(_?[0-9 +-]+)/', $params[2], $matches)) {
                    $args['catid'] = $catid = $matches[1];
                    if (strpos($catid,'+') === FALSE ) {
                        $args['cids'] = explode('-',$catid);
                    } else {
                        $args['cids'] = explode('+',$catid);
                        $args['andcids'] = TRUE;
                    }

                    // Keep compability with what is following
                    unset($params[2]);
                    $params = array_values($params);
                }

                // Now that we are in a specific itemtype
                // check if we want to decode URLs using their titles rather then their ID
                //TODO have some setting for this
                // Decode using title
                if ($fieldforshorturl == 1 && !empty( $shorturlfield)) {
                    if (($shorturlduplicates == 2) && !empty($params[3]) && preg_match('/^(\d+)/',$params[3],$matches))
                    {
                        $args['itemid'] = $params[3];
                    } else {
                        $args['itemid'] = dynamicdata_decodeItemIDUsingField( $params, $args['itemtype'], $shorturlduplicates, $shorturlfield);
                    }
                    return array('display', $args);
                } else if (is_numeric($params[2])) {
                    $args['itemid'] = $params[2];
                    return array('display', $args);
                }

                return array('view', $args);
            }
        } else {
            return array('view', $args);
        }

        // Decode using title
        if ($fieldforshortur == 1 && !empty( $shorturlfield)) {
            if (($shorturlduplicates == 2) && !empty($params[3]) && preg_match('/^(\d+)/',$params[3],$matches))
            {
                $args['itemid'] = $params[3];
            } else {
                $args['itemid'] = dynamicdata_decodeItemIDUsingField( $params, '', $shorturlduplicates, $shorturlfield);
            }


            return array('display', $args);
        }
    }


    // default : return nothing -> no short URL

}
/**
 * Find theitem ID by its title.
 * @access private
 * @return int itemid
 * @todo bug 5878 Why does a title need higher privileges than the usual itemid in a short title?
 */
function dynamicdata_decodeItemIdUsingField($params, $itemtype= '', $shorturlduplicates = 2,  $shorturlfield='',$modid = 182 )
{
    switch ($shorturlduplicates)
    {
        case 1:
            $dupeResolutionMethod = 'Append ItemId';
            break;
        case 2:
            $dupeResolutionMethod = 'Use ItemId'; //always use ItemID - we should never get here
            break;
        case 3:
        default:
            $dupeResolutionMethod = 'Ignore';
            break;
    }

    // The $params passed in does not match on all legal URL characters and
    // so some urls get cut off -- my test cases included parents and commands "this(here)" and "that,+there"
    // So lets parse the path info manually here.
    //
    // DONE: fix xarServer.php, line 421 to properly deal with this
    // xarServer.php[421] :: preg_match_all('|/([a-z0-9_ .+-]+)|i', $path, $matches);
    //
    // I've moved the following code into xarServer to fix this problem.
    //
    //     $pathInfo = xarServerGetVar('PATH_INFO');
    //     preg_match_all('|/([^/]+)|i', $pathInfo, $matches);
    //     $params = $matches[1];

    if( isset($itemtype) && !empty($itemtype) ) {
        $searchArgs['itemtype'] = $itemtype;
        $paramidx = 2;
    } else {
        $paramidx = 1;
    }
    $decodedField = urldecode($params[$paramidx]);

    // see if we need to append anything else to the title (= when it contains a /)
    if (count($params) > $paramidx + 1) {
        for ($i = $paramidx + 1; $i < count($params); $i++) {
            if ($dupeResolutionMethod == 'Append ItemId' && preg_match('/^\d+$/',$params[$i])) {
                break;
            } elseif ($dupeResolutionMethod == 'ALL' && preg_match('/^\d+(|-\d+-\d+ \d+:\d+)$/',$params[$i])) {
                break;
            }
            $decodedField .= '/' . urldecode($params[$i]);
            $paramidx = $i;
        }
    }
    $paramidx++;
    $decodedField = str_replace('%','\%', $decodedField);
    $decodedField2 = str_replace('_',' ', $decodedField);
    $decodedField = str_replace('_','\_', $decodedField);
    $decodedField = str_replace("'","\'", $decodedField);
    $decodedField2 = str_replace("'","\'", $decodedField2);

    $spacecode= xarModGetVar('base','urlspaces')?xarModGetVar('base','urlspaces'):'_';
    //$decodedField = str_replace("\\'","'", $decodedField);
    if (strpos($decodedField, $spacecode) !== false)  {
        $decodedField = str_replace( $spacecode,' ',$decodedField);
    }
    $searchArgs['search'] = $decodedField;
    $searchArgs['where'] = "$shorturlfield eq '$decodedField' or $shorturlfield eq '$decodedField2'";
    $searchArgs['modid'] = $modid;
    // Get the items via a search

    $items = xarModAPIFunc('dynamicdata', 'user', 'getitems', $searchArgs);

    if( (count($items) == 0)) {
        $searchArgs['search'] = $decodedField;
        $searchArgs['where'] = "$shorturlfield eq '$decodedField'";
        $items = xarModAPIFunc('dynamicdata', 'user', 'getall', $searchArgs);
    }
    if( count($items) == 1 ) {
        $theItem = current($items);
    } else {
        // NOTE: We could probably just loop through the various dupe detection methods rather then
        // pulling from a config variable.  This would allow old URLs encoded using one system
        // to keep working even if the configuration changes.
        switch( $dupeResolutionMethod )
        {
            case 'Append ItemId':
                // Look for itemid appended after title
                if( !empty($params[$paramidx]) )
                {
                    foreach ($items as $item)
                    {

                        if ((isset($item['id']) &&  ($item['id'] == $params[$paramidx])) || (isset($item['itemid']) &&  ($item['itemid'] == $params[$paramidx])) )
                        {
                            $theItem = $item;
                            break;
                        }
                    }
                }
                break;
            case 'Ignore':
            default:
                // Just use the first one that came back
                if (!empty($items)) {
                    $theItem =  current($items);
                }
        }
    }

    if( !empty($theItem) )
    {
        if (isset($theItem['id'])) {
            $itemid = $theItem['id'];
        }elseif (isset($theItem['itemid'])) {
             $itemid = $theItem['itemid'];
        }
        return $itemid;
    }
}
?>