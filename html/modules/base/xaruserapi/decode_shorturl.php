<?php
/**
 * Decode shorturls for Base Module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * @author mikespub
 * @param $params array containing the different elements of the virtual path
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function base_userapi_decode_shorturl($params)
{
    // Initialise the argument list we will return
    $args = array();
    // Analyse the different parts of the virtual path
    // $params[1] contains the first part after index.php/example
    // In general, you should be strict in encoding URLs, but as liberal
    // as possible in trying to decode them...

    if (!empty($params[1]) && is_string($params[1])) {
        // this must be some page here
        // Note : make sure your encoding/decoding is consistent ! :-)
        $page = $params[1];
        $args['page'] = $page;
    
        if (!empty($params[2]) && is_string($params[2])) {
            $tab = $params[2];
            $args['tab'] = $tab;
        }
    }

    return array('main',$args);
}

?>