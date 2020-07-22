<?php
//Canadian province list
//USING ISO3166-2:CA
function getOptions_ca()
{
    $options[] = array('id' =>'CA-AB', 'name' =>'Alberta');
    $options[] = array('id' =>'CA-BC', 'name' =>'British Columbia');
    $options[] = array('id' =>'CA-MB', 'name' =>'Manitoba');
    $options[] = array('id' =>'CA-NB', 'name' =>'New Brunswick');
    $options[] = array('id' =>'CA-NL', 'name' =>'Newfoundland and Labrador');
    $options[] = array('id' =>'CA-NT', 'name' =>'Northwest Territories');
    $options[] = array('id' =>'CA-NS', 'name' =>'Nova Scotia');
    $options[] = array('id' =>'CA-NU', 'name' =>'Nunavut');
    $options[] = array('id' =>'CA-ON', 'name' =>'Ontario');
    $options[] = array('id' =>'CA-PE', 'name' =>'Prince Edward Island');
    $options[] = array('id' =>'CA-QC', 'name' =>'Quebec');
    $options[] = array('id' =>'CA-SK', 'name' =>'Saskatchewan');
    $options[] = array('id' =>'CA-YT', 'name' =>'Yukon Territory');
    return $options;
}
?>