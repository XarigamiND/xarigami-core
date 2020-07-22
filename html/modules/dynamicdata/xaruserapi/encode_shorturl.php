<?php
/**
 * Encode short urls
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
 * return the path for a short URL to xarModURL for this module
 * @param $args the function and arguments passed to xarModURL
 * @param  int $fieldforshorturl Use a field for short url
 * @param  string $shorturlfield Name of field to use for short url
 * @param  int shorturlduplicates Encode method: 1 - append id, 2 - use ID only, 3 - ignore duplicates use field only
 * @return string path to be added to index.php for a short URL, or empty if failed
 */
function dynamicdata_userapi_encode_shorturl($args)
{
    static $objectcache = array();
    static $fieldforshorturl =  0;
    static $shorturlfield = '';
    static $shorturlduplicates = 0;

    // Get arguments from argument array
    extract($args);
    // check if we have something to work with
    if (!isset($func)) {
        return;
    }

    // make sure you don't pass the following variables as arguments too

    // default path is empty -> no short URL
    $path = '';
    // if we want to add some common arguments as URL parameters below
    $join = '?';
    // we can't rely on xarMod::getName() here !
    $module = 'dynamicdata';

    // return immediately when we're dealing with the main function (don't load unnecessary stuff)
    if ($func == 'main') {
        return '/' . $module . '/';
    }

    // fill in default values
    if (empty($modid)) {
        $modid = xarMod::getId('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    if (count($objectcache) == 0) {
        $objects = xarMod::apiFunc('dynamicdata','user','getobjects');

        foreach ($objects as $object) {
            if (!empty($object['config']) && is_string($object['config']) && substr($object['config'],0,2) == 'a:') {
                $config = unserialize($object['config']);
                 $fieldforshorturl =  isset($config['fieldforshorturl'])?$config['fieldforshorturl']:FALSE;
                 $shorturlfield =  isset($config['shorturlfield'])?$config['shorturlfield']:'';
                 $shorturlduplicates =   isset($config['shorturlduplicates'])?$config['shorturlduplicates']:1;
            }
            $objectcache[$object['moduleid'].':'.$object['itemtype']] = array('objectname'=>$object['name'],
                                                                               'shorturlfield' => $shorturlfield,
                                                                               'fieldforshorturl'=>$fieldforshorturl,
                                                                               'shorturlduplicates' => $shorturlduplicates
                                                                                );
        }
    }
    $shorturlfield = $objectcache[$modid.':'.$itemtype]['shorturlfield'];
    $fieldforshorturl =$objectcache[$modid.':'.$itemtype]['fieldforshorturl'];
    $shorturlduplicates =$objectcache[$modid.':'.$itemtype]['shorturlduplicates'];

    // specify some short URLs relevant to your module
    if (!empty($table)) {
        // no short URLs for this one...

    } elseif ($func == 'view') {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype]['objectname'];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/';
            } else {
                $path = '/' . $module . '/' . $name . '/';
            }
            if (!empty($catid)) {
                $path .= 'c' . $catid . '/';
            }
        } else {
            // we don't know this one...
        }
    } elseif ($func == 'display' && isset($itemid)) {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype]['objectname'];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/' . $itemid;
            } else {
                $path = '/' . $module . '/' . $name . '/' . $itemid;
            }
              // Check to see if we want to encode using Field
            if ($fieldforshorturl==1 && !empty($shorturlfield))
            {
                $path = '/' . $name . '/'.dynamicdata_encodeUsingField($itemid,  $shorturlduplicates,$shorturlfield,  $itemtype);
            }

        } else {
            // we don't know this one...
        }
    }
    // anything else does not have a short URL equivalent

// TODO: add *any* extra args we didn't use yet here
    // add some other module arguments as standard URL parameters
    if (!empty($path)) {
        // search
        if (isset($q)) {
            $path .= $join . 'q=' . urlencode($q);
            $join = '&';
        }
        // sort
        if (isset($sort)) {
            $path .= $join . 'sort=' . $sort;
            $join = '&';
        }
        // pager
        if (isset($startnum) && $startnum != 1) {
            $path .= $join . 'startnum=' . $startnum;
            $join = '&';
        }
        // multi-page articles
        if (isset($page)) {
            $path .= $join . 'page=' . $page;
            $join = '&';
        }
        if (isset($layout)) {
            $path .= $join . 'layout=' . $layout;
            $join = '&';
        }
    }

    return $path;
}
function dynamicdata_encodeUsingField($itemid, $shorturlduplicates=1,$shorturlfield='', $itemtype = '', $modid = 182 )
{
    $searchArgs['itemid'] =$itemid;
    $searchArgs['modid'] =$modid;
    $searchArgs['itemtype'] =$itemtype;
    $item = xarModAPIFunc('dynamicdata','user','getitem', $searchArgs);
    if (empty($item)) {
        // default to just the item ID
        $path = $itemid;
        return $path;
    }
    $spacecode = xarModGetVar('base','urlspaces')?xarModGetVar('base','urlspaces'):'_';
    switch ($shorturlduplicates)
    {
        case 1:
            $dupeResolutionMethod = 'Append ItemId'; //append itemid for duplicates
            break;
        case 2:
            $dupeResolutionMethod = 'Use ItemId'; //always append itemid
            $nsearch =  array('?', ',', ';', ':', '.',"'", '"',' ');
            $nreplace = array('',  '',  '',  '',  '','','',$spacecode);
            $item[$shorturlfield] = str_replace($nsearch,$nreplace, strtolower(rtrim($item[$shorturlfield]))); //get rid of punctuation and trailing spaces
            //remove any double spacecodes here - not later as it may affect other encodings and quicker ot use str_replace
            $item[$shorturlfield] = str_replace($spacecode.$spacecode,$spacecode, $item[$shorturlfield]); //get rid of duplicate _ or -
            $item[$shorturlfield] = rtrim($item[$shorturlfield],$spacecode); //take of trailing - or _
            break;
        case 3:
        default:
            $dupeResolutionMethod = 'Ignore';
            break;
    }
    $decodedField = $item[$shorturlfield];
    $items = array();
    if (($dupeResolutionMethod != 'Ignore') && ($dupeResolutionMethod != 'Use ItemId')) {

        $searchArgs = array();
       // $searchArgs['moduleid'] = $modid;
        $searchArgs['where'] = "$shorturlfield eq '$decodedField'";

        // if $itemtype is set, it will be part of the URL so we can use it to refine the search
        if (!empty($itemtype)) {
            $searchArgs['itemtype'] = $itemtype;
        }
        $items = xarModAPIFunc('dynamicdata', 'user', 'getitems', $searchArgs);
    }

    if ( strpos($item[$shorturlfield],  $spacecode) === FALSE ) // we cannot replace with the - or _ if it exists as valid part of title
    {
        $item[$shorturlfield] = str_replace(' ',  $spacecode,$item[$shorturlfield]);
        $item[$shorturlfield] = strtolower($item[$shorturlfield]);
    }
    $encodedField = rawurlencode( $item[$shorturlfield]);

    //  the URL encoded / (%2F) is not accepted by Apache in PATH_INFO
    $encodedField = str_replace(array('%2F','%2B'),array('/','%252B'),$encodedField);

    if ($dupeResolutionMethod == 'Ignore') {
        // Ignore duplicates
        $path = $encodedField;

    }elseif  ($dupeResolutionMethod == 'Use ItemId') {
          //always use itemid with name
             $path = $encodedField .'/'.$itemid;
    // Check to find out how many articles come back from the search.
    } elseif (count($items) == 1 ) {

            $path = $encodedField;
    } elseif (count($items) == 0) {
        // Can't find article through search, won't be able to find it on decode
        // default to just the article ID
        $path = $itemid;

    } else {

        // Finding multiple articles through search, add a duplication resolution flag
        $path = $encodedField .'/'.$itemid;
    }

    return $path;
}
?>
