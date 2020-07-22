<?php
//German state list
//using iso 3166-2:de codes
function getOptions_de()
{
    $options[] = array('id' =>'DE-BW', 'name' =>xarML('Baden-Württemberg'));
    $options[] = array('id' =>'DE-BY', 'name' =>xarML('Bayern'));
    $options[] = array('id' =>'DE-BE', 'name' =>xarML('Berlin'));
    $options[] = array('id' =>'DE-BB', 'name' =>xarML('Brandenburg'));
    $options[] = array('id' =>'DE-HB', 'name' =>xarML('Bremen'));
    $options[] = array('id' =>'DE-HH', 'name' =>xarML('Hamburg'));
    $options[] = array('id' =>'DE-HE', 'name' =>xarML('Hessen'));
    $options[] = array('id' =>'DE-MV', 'name' =>xarML('Mecklenburg-Vorpommern'));
    $options[] = array('id' =>'DE-NI', 'name' =>xarML('Niedersachsen'));
    $options[] = array('id' =>'DE-NW', 'name' =>xarML('Nordrhein-Westfalen'));
    $options[] = array('id' =>'DE-RP', 'name' =>xarML('Rheinland-Pfalz'));
    $options[] = array('id' =>'DE-SL', 'name' =>xarML('Saarland'));
    $options[] = array('id' =>'DE-SN', 'name' =>xarML('Sachsen'));
    $options[] = array('id' =>'DE-ST', 'name' =>xarML('Sachsen-Anhalt'));
    $options[] = array('id' =>'DE-SH', 'name' =>xarML('Schleswig-Holstein'));
    $options[] = array('id' =>'DE-TH', 'name' =>xarML('Thüringen'));
    return $options;
}
?>