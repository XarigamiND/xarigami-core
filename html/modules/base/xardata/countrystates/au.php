<?php
//australian state list
//using iso 3166-2:au codes
function getOptions_au()
{
    $options[] = array('id' =>'AU-NSW', 'name' =>'New South Wales');
    $options[] = array('id' =>'AU-QLD', 'name' =>'Queensland');
    $options[] = array('id' =>'AU-SA', 'name'  =>'South Australia');
    $options[] = array('id' =>'AU-TAS', 'name' =>'Tasmania');
    $options[] = array('id' =>'AU-VIC', 'name' =>'Victoria');
    $options[] = array('id' =>'AU-WA ', 'name' =>'Western Australia');
    $options[] = array('id' =>'AU-ACT', 'name' =>'Australian Capital Territory');
    $options[] = array('id' =>'AU-NT', 'name'  =>'Northern Territory');

    return $options;
}
?>