<?php
/**
 * Reorder properties
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * @description move a property as part of reordering
 * @author jojodee
 * @param $relorder the current relative order of the property
 * @param $moveaction movement up or down
 */

function dynamicdata_adminapi_reorderprops($args)
{
    extract($args);

    if (!isset($modid) || !isset($itemtype) || !isset($pname) || !isset($relorder) || !isset($moveaction)) {
        return;
    }
    //get the array of all props in correct order
    $properties = xarMod::apiFunc('dynamicdata','user','getprop',
                                   array('objectid'=>isset($objectid) ? $objectid : null,
                                         'modid' => $modid,
                                         'itemtype' => $itemtype,
                                         'allprops' => true));

    if (empty($properties) ) {
        throw new EmptyParameterException($properties,'Variable is not set  "#(1)" ');
    }

    $currentorder=$properties[$pname]['order'];
    $currentrelorder=$relorder;
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $propertiestable = $xartable['dynamic_properties'];
    
    //first give all the properties a relative order for easy reference rather than name key
    $i=0;
    $orderedprops = array();
    foreach ($properties as $propname=>$prop) {
        $properties[$propname]['relorder'] = $i;
        $orderedprops[$i] = $properties[$propname];
        $i++;
    }
    // don't assume the value of the order field is a contiguous number sequence.
    foreach ($orderedprops as $relorder=>$prop) {
        if (($relorder == $currentrelorder) && strtolower($moveaction) == 'up') {
            // We need to find the position order before (less)
            $swaporder = $orderedprops[$relorder-1]['order']; //real order of prior prop
            $swappropid = $orderedprops[$relorder-1]['id'];            
            $currentorder = $orderedprops[$relorder]['order'];
            $currentpropid =  $orderedprops[$relorder]['id'];        
        } elseif (($relorder == $currentrelorder) && strtolower($moveaction) == 'down') {
            // We need to find the position  after (more)
            $swaporder =  $orderedprops[$relorder+1]['order'];
            $swappropid = $orderedprops[$relorder+1]['id'];               
            $currentorder =  $orderedprops[$relorder]['order'];
            $currentpropid =  $orderedprops[$relorder]['id'];
        }
    }
    reset($orderedprops);

    // Update the current object property
    $query = 'UPDATE ' . $propertiestable
        . ' SET xar_prop_order = ?'
        . ' WHERE xar_prop_id = ?';

    $result = $dbconn->execute($query, array($swaporder, $currentpropid));
    if (!$result) return;

    // Update the swapped property
    $query = 'UPDATE ' .  $propertiestable
        . ' SET xar_prop_order = ?'
        . ' WHERE xar_prop_id = ?';

    $result = $dbconn->execute($query, array($currentorder, $swappropid));
    if (!$result) return;

    $result->close();

    return true;
}

?>