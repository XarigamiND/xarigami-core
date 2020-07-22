<?php
//Nethderlands province list
//USING ISO3166-2:NL
function getOptions_nl()
{
    $options[] = array('id' =>'NL-DR', 'name' =>'Drenthe');
    $options[] = array('id' =>'NL-FL', 'name' =>'Flevoland');
    $options[] = array('id' =>'NL-FR', 'name' =>'Friesland');
    $options[] = array('id' =>'NL-GE', 'name' =>'Gelderland');
    $options[] = array('id' =>'NL-GR', 'name' =>'Groningen');
    $options[] = array('id' =>'NL-LI', 'name' =>'Limburg');
    $options[] = array('id' =>'NL-NB', 'name' =>'Noord-Brabant');
    $options[] = array('id' =>'NL-NH', 'name' =>'Noord-Holland');
    $options[] = array('id' =>'NL-OV', 'name' =>'Overijssel');
    $options[] = array('id' =>'NL-UT', 'name' =>'Utrecht');
    $options[] = array('id' =>'NL-ZE', 'name' =>'Zeeland');
    $options[] = array('id' =>'NL-ZH', 'name' =>'Zuid-Holland');

    return $options;
}
?>