<?php
//signapore state list
//using iso 3166-2:au codes
function getOptions_sg()
{
    $options[] = array('id' =>'SG-01', 'name' =>'Central Singapore');
    $options[] = array('id' =>'SG-02', 'name' =>'North East');
    $options[] = array('id' =>'SG-03', 'name' =>'North West');
    $options[] = array('id' =>'SG-04', 'name' =>'South East');
    $options[] = array('id' =>'SG-05', 'name' =>'South West');

    return $options;
}
?>