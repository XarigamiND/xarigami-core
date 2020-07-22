<?php
//ireland state list
//using iso 3166-2:ie codes
//Note at the time of writing, both Cork county and Connaught Province have the same IE-C abbr
//We have used 'IE-C-' to distinguish Cork. This can be upgraded if 3166-2 is changed.
function getOptions_ie()
{
    $options[] = array('id' =>'-', 'name' =>xarML('--PROVINCES--'));
    $options[] = array('id' =>'IE-C', 'name' =>xarML('Connaught'));
    $options[] = array('id' =>'IE-L', 'name' =>xarML('Leinster'));
    $options[] = array('id' =>'IE-M', 'name' =>xarML('Munster'));
    $options[] = array('id' =>'IE-U', 'name' =>xarML('Ulster'));
    $options[] = array('id' =>'--', 'name' =>xarML('--COUNTIES--'));
    $options[] = array('id' =>'IE-CW', 'name' =>xarML('Carlow'));
    $options[] = array('id' =>'IE-CN', 'name' =>xarML('Cavan'));
    $options[] = array('id' =>'IE-CE', 'name' =>xarML('Clare'));
    $options[] = array('id' =>'IE-C-', 'name' =>xarML('Cork'));
    $options[] = array('id' =>'IE-D', 'name' =>xarML('Donegal'));
    $options[] = array('id' =>'IE-D', 'name' =>xarML('Dublin'));
    $options[] = array('id' =>'IE-G', 'name' =>xarML('Galway'));
    $options[] = array('id' =>'IE-KY', 'name' =>xarML('Kerry'));
    $options[] = array('id' =>'IE-KE', 'name' =>xarML('Kildare'));
    $options[] = array('id' =>'IE-KK', 'name' =>xarML('Kilkenny'));
    $options[] = array('id' =>'IE-LS', 'name' =>xarML('Laois'));
    $options[] = array('id' =>'IE-LM', 'name' =>xarML('Leitrim'));
    $options[] = array('id' =>'IE-LK', 'name' =>xarML('Limerick'));
    $options[] = array('id' =>'IE-LD', 'name' =>xarML('Longford'));
    $options[] = array('id' =>'IE-LH', 'name' =>xarML('Louth'));
    $options[] = array('id' =>'IE-MO', 'name' =>xarML('Mayo'));
    $options[] = array('id' =>'IE-MH', 'name' =>xarML('Meath'));
    $options[] = array('id' =>'IE-MN', 'name' =>xarML('Monaghan'));
    $options[] = array('id' =>'IE-OY', 'name' =>xarML('Offaly'));
    $options[] = array('id' =>'IE-RN', 'name' =>xarML('Roscommon'));
    $options[] = array('id' =>'IE-SO', 'name' =>xarML('Sligo'));
    $options[] = array('id' =>'IE-TA', 'name' =>xarML('Tipperary'));
    $options[] = array('id' =>'IE-WD', 'name' =>xarML('Waterford'));
    $options[] = array('id' =>'IE-WH', 'name' =>xarML('Westmeath'));
    $options[] = array('id' =>'IE-WX', 'name' =>xarML('Wexford'));
    $options[] = array('id' =>'IE-WW', 'name' =>xarML('Wicklow'));
    return $options;
}
?>