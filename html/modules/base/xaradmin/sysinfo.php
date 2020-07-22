<?php
/**
 * Display some system information
 *
 * @package Xaraya modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Display some system information
 *
 * This information can be used for support / debugging
 *
 * @return array of info from phpinfo()
 */
function base_admin_sysinfo()
{
    // Security Check
    if(!xarSecurityCheck('EditModules')) return;

    //number of releases to show
    $releasenumber=(int)xarModGetVar('base','releasenumber');

    if (!isset($releasenumber) || $releasenumber ==0) {
         $releasenumber=10;
    }

    // allow fopen
    $allowurlfopen = true;
    if (!xarFuncIsDisabled('ini_set')) ini_set('allow_url_fopen', 1);
    if (!ini_get('allow_url_fopen')) {
        //we don't want an exceptin
        //just capture the fact and show some message
        //throw new BadParameterException(null,xarML('Unable to use fopen to get RSS feeds.'));
         $allowurlfopen = false;
         $data['feederror'] = xarML("Resource feeds cannot be displayed. Your PHP setup for 'allow_url_fopen' is not enabled and is required for feed display.");
    }

    if ($allowurlfopen == TRUE) {
        // Require the xmlParser class
        sys::import('modules.base.xarclass.xmlParser');
        // Require the feedParser class
        sys::import('modules.base.xarclass.feedParser');
        // Check and see if a feed has been supplied to us.
        $feeddata =NULL;
        $devnewsdata = NULL;
        $devnotedata= NULL;
        $feeddata= NULL;

        if (xarModGetVar('base','showresources') == TRUE) {
        $feedfile = 'http://xarigami.com/index.php?module=articles&func=view&ptid=2&releaseno='.$releasenumber.'&theme=rss';
        // Get the feed file (from cache or from the remote site)
        $feeddata = xarMod::apiFunc('base', 'user', 'getfile',
                                  array('url' => $feedfile,
                                        'cached' => true,
                                        'cachedir' => 'cache/rss',
                                        'refresh' => 604800,
                                        'extension' => '.xml'));
        }
        if (xarModGetVar('base','showdevnews') == TRUE) {
            $devnewsfile = 'http://xarigami.com/index.php?module=articles&func=view&ptid=1&releaseno='.$releasenumber.'&theme=rss';
              // Get the feed file (from cache or from the remote site)
            $devnewsdata = xarMod::apiFunc('base', 'user', 'getfile',
                                  array('url' => $devnewsfile,
                                        'cached' => true,
                                        'cachedir' => 'cache/rss',
                                        'refresh' => 604800,
                                        'extension' => '.xml'));
        }
        if (xarModGetVar('base','showdevnotes') == TRUE) {
            $devnotefile = 'http://xarigami.com/index.php?module=articles&func=view&ptid=11&releaseno='.$releasenumber.'&theme=rss';
            // Get the feed file (from cache or from the remote site)
            $devnotedata = xarMod::apiFunc('base', 'user', 'getfile',
                                  array('url' => $devnotefile,
                                        'cached' => true,
                                        'cachedir' => 'cache/rss',
                                        'refresh' => 604800,
                                        'extension' => '.xml'));
        }
          $data['feederror'] = '';
        $feeds = array();
        $validfeeds = array('feeddata','devnewsdata','devnotedata');
        foreach ($validfeeds as $k) {
            if (!empty($$k)) {
            $feeds[$k] = $$k;
            }
        }
        if (!empty($feeds)) {
            // Create a need feedParser object
            $p = new feedParser();
            // Tell feedParser to parse the data
            foreach ($feeds as $feedname=>$feedinfo) {
                $info = $p->parseFeed($feedinfo);
                if (empty($info['warning'])){
                  foreach ($info as $content){
                    foreach ($content as $newline){
                      if(is_array($newline)) {
                        if (isset($newline['description'])){
                          $description = $newline['description'];
                        } else {
                          $description = '';
                        }
                        if (isset($newline['title'])){
                          $title = $newline['title'];
                        } else {
                          $title = '';
                        }
                        if (isset($newline['link'])){
                          $link = $newline['link'];
                        } else {
                          $link = '';
                        }
                        $feedcontent[$feedname][$title] = array('title' => $title, 'link' => $link, 'description' => $description);
                      }
                    }
                  }
                  $data['feedinfo'][$feedname]['warning'] = isset($info['warning'])?$info['warning']:'';
                  $data['feedinfo'][$feedname]['chantitle']  =   $info['channel']['title'];
                  $data['feedinfo'][$feedname]['chanlink']   =   $info['channel']['link'];
                  $data['feedinfo'][$feedname]['chandesc']   =   $info['channel']['description'];
                } else {
                    throw new DataNotFoundException($feedname,'There is a problem with a feed - #(1).');
                }
            }
        }
    }
    $data['releasenumber']=isset($releasenumber)?$releasenumber:'';
    $data['feedcontent'] = isset($feedcontent)?$feedcontent:array();
    $data['feedinfo']= isset($data['feedinfo'])?$data['feedinfo']:array();
    //PHP Info
     $opmode = xarSystemVars::get(sys::CONFIG, 'Operation.Mode',true);
     $data['opmode'] = $opmode;
    if ($opmode && $opmode == 'demo') {
        $data['what'] = 0;
        $data['phpinfo'] ='';
        $data['demomsg'] = xarML('PHP Info has been disabled in this operation mode.');
    } else {
        xarVarFetch('what','int:-1:127',$what,0, XARVAR_NOT_REQUIRED);
        $data['what'] = $what;
        $disabled = ini_get('disable_functions');
        $checkphpinfo = str_replace('phpinfo','',$disabled,$count);
        if ($count >0) {
            $data['phpinfo'] = "<h3>".xarMl('phpinfo() has been disabled on this site for security reasons.')."</h3>";
            return $data;
        }
        ob_start();
        // FIXME: can we split this up in more manageable parts?
        phpinfo($what);
        $val_phpinfo = ob_get_contents();
        ob_end_clean();

        // get a substring of the php info to get rid of the html, head, title, etc.
        // Credit to Jason Judge.
        // Remove the header and footer.
        $val_phpinfo = preg_replace(
            array('/^.*<body[^>]*>/is', '/<\/body[^>]*>.*$/is'), '', $val_phpinfo, 1
        );
        // Remove pixel table widths.*/
        $val_phpinfo = preg_replace(
            '/width="[0-9]+"/i', 'width="80%"', $val_phpinfo
         );
        // Replace ampersands by entities
       // $val_phpinfo = str_replace('&', '&#38;', $val_phpinfo); //preg_replace('/&([^#])/', '&#38;$1', $val_phpinfo);
        $data['phpinfo'] = $val_phpinfo;
    }
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');
    $data['XARCORE_VERSION_NUM'] = XARCORE_VERSION_NUM;
    $data['XARCORE_VERSION_ID'] =  XARCORE_VERSION_ID;
    $data['XARCORE_VERSION_SUB'] = XARCORE_VERSION_SUB;
    $data['XARCORE_VERSION_REV'] = XARCORE_VERSION_REV;


    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('base','admin','getmenulinks');


    return $data;
}
?>