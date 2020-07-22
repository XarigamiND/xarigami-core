<?php
/**
 * Return the favicon for a given url
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Gets a file from the Internet
 * Returns the favicon (if any) from a given url
 * When no icon is found, an empty one is returned, defined in this function
 *
 * @author Panayotis Vryonis (lilina package)
 * @access public
 * @param $args['url'] string the absolute URL for the file
 * @return string content of the file
 */
function base_userapi_getfavicon($args)
{
    extract($args);
    if (!isset($url)) {
        $msg = xarML('Invalid Parameter Count');
        throw new EmptyParameterException(null,$msg);
    }
    $empty_ico_data = base64_decode(
    'AAABAAEAEBAAAAEACABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAQAEAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAA////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' .
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP//AAD//wAA//8AAP//AAD//wAA//8AAP//' .
    'AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAA=');

    $ico_url = __getFaviconURL($url) ;
    if(!$ico_url) {
        return false ;
    }

    $cached_ico = sys::varpath().'/cache/rss/' . md5($ico_url) . ".ico" ;
    $cachetime = 30 * 24 * 60 * 60; // 30 days

    // Serve from the cache if it is younger than $cachetime
    if (file_exists($cached_ico) && (time() - $cachetime < filemtime($cached_ico))) return $cached_ico;

    //can't use curl, ensure we remain compatible
    $HTTPRequest = @fopen($ico_url, 'r');
    if ($HTTPRequest) {
        stream_set_timeout($HTTPRequest, 0.1);
        $favicon = fread($HTTPRequest, 1024); //make is quick
        $HTTPRequestData = stream_get_meta_data($HTTPRequest);
        fclose($HTTPRequest);
        if ((!$HTTPRequestData['timed_out'])  && strlen($favicon) < 8192) {
            if (!$data = @file_get_contents($ico_url)) $data=$empty_ico_data;
            if (stristr($data,'html')) $data=$empty_ico_data;
            $fp = fopen($cached_ico,'w');
            fputs($fp,$data);
            fclose($fp);
            return $cached_ico;
        }
    }
    return false;


}
//this only gets favicon in root
//Time consuming to source others
function __getFaviconURL($url)
{
    if(empty($url) || $url == 'http://'){
        return false;
    }
    $url_parts = @parse_url($url);
    if (empty($url_parts)) {
        return false;
    }
    $full_url = "http://{$url_parts['host']}";
    if(isset($url_parts['port'])){
        $full_url .= ":{$url_parts['port']}";
    }
    $favicon_url = $full_url . "/favicon.ico" ;

    return $favicon_url;
}
?>
