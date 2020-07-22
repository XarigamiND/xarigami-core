<?php
/**
 *  Modify meta block
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * modify block settings
 *
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }
    // Defaults
    $vars['metakeywords']       = !empty($vars['metakeywords']) ?  $vars['metakeywords'] : '';
    $vars['metadescription']    = !empty($vars['metadescription']) ?  $vars['metadescription'] : '';
    $vars['usegeo']             = isset($vars['usegeo']) ?  $vars['usegeo'] : false;    
    $vars['usedk']              = !empty($vars['usedk']) ?  $vars['usedk'] : ''; 
    $vars['usesummary']         = !empty($vars['usesummary']) ?  $vars['usesummary'] : false;     
    $vars['longitude']          = !empty($vars['longitude']) ?  $vars['longitude'] : ''; 
    $vars['latitude']           = !empty($vars['latitude']) ?  $vars['latitude'] : '';     
    $vars['copyright']          = !empty($vars['copyright']) ?  $vars['copyright'] : '';  
    $vars['helppage']           = !empty($vars['helppage']) ?  $vars['helppage'] : '';  
    $vars['glossary']           = !empty($vars['glossary']) ?  $vars['glossary'] : '';  
    $vars['defaultrss']         = isset($vars['defaultrss']) ?  $vars['defaultrss'] : false;    
    $vars['extrameta']          = !empty($vars['extrameta']) ?  $vars['extrameta'] : '';      
    $vars['rssurl']             = !empty($vars['rssurl']) ?  $vars['rssurl'] : '';  
           
    $vars['dkoptions'] = array( '0'=> xarML('None'),
                                '1'=> xarML('Articles Module'),
                                '2'=> xarML('Keywords Module'),
                                '3'=> xarML('Both')
                               );
    //TODO: finish exta meta tag input and output                           
    if (empty($vars['extrameta'])) {
        $extrameta = array('name'=>'name','content'=>'content'
                            );
        $vars['extrameta'] = $extrameta;
    } else {
       $vars['extrameta'] = unserialize($vars['extrameta']);
    }
    
    $vars['blockid'] = $blockinfo['bid'];
    
    return $vars;
}

/**
 * update block settings
 *
 * @author  John Cox
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_update($blockinfo)
{
    // TODO: remove this once all blocks can accept content arrays.
    if (!is_array($blockinfo['content'])) {
        $blockinfo['content'] = unserialize($blockinfo['content']);
    }

    $vars = array();
    
    if (!xarVarFetch('metakeywords',    'notempty', $vars['metakeywords'],    '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('metadescription', 'notempty', $vars['metadescription'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('usesummary',      'checkbox',  $vars['usesummary'],      false,  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('usegeo',          'checkbox',  $vars['usegeo'],          false,  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('longitude',       'notempty', $vars['longitude'],       '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('latitude',        'notempty', $vars['latitude'],        '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('usedk',           'notempty', $vars['usedk'],           '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('copyrightpage',   'notempty', $vars['copyrightpage'],   '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('helppage',        'notempty', $vars['helppage'],        '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('glossary',        'notempty', $vars['glossary'],        '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('defaultrss',      'checkbox',  $vars['defaultrss'],    false,  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('rssurl',          'str:0',  $vars['rssurl'],   '',  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('extrameta',       'array',  $vars['extrameta'],   '',  XARVAR_NOT_REQUIRED)) return;
    if (is_array($vars['extrameta']) && ($vars['extrameta'][0] !='name') )
    {
    $vars['extrameta'] = serialize($vars['extrameta']);
    } else {
    $vars['extrameta'] = '';
    }
    // Merge the submitted block info content into the existing block info.
    $blockinfo['content'] = $vars; //array_merge($blockinfo['content'], $vars);

    return $blockinfo;
}

?>