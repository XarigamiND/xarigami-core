<?php
/**
 * Get registered template tags
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Themes module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Get registered template tags
 *
 * @param string tagname
 * @return array of tags in the database
 * @author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_adminapi_gettpltag($args)
{
    extract($args);
    if (!isset($tagname)) return;

    $aData = array(
        'tagname'       => '',
        'module'        => '',
        'handler'       => '',
        'attributes'    => array(),
        'num_atributes' => 0
    );

    if (trim($tagname) != '') {
        $oTag = xarTplGetTagObjectFromName($tagname);
        $aData = array(
            'tagname'       => $oTag->getName(),
            'module'        => $oTag->getModule(),
            'handler'       => $oTag->getHandler(),
            'attributes'    => $oTag->getAttributes(),
            'num_atributes' => sizeOf($oTag->getAttributes())
        );

    }
    $aData['max_attrs'] = 10;

    return $aData;
}

?>