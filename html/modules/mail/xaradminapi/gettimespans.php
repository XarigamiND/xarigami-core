<?php

/**
* get the list of available scheduler intervals
*
* Adapted from scheduler_userapi_intervals().  We would have used that
* function directly, but we can't guarantee scheduler will be present.
*
* @author mikespub
* @returns array
* @return array of intervals
*/
function mail_adminapi_gettimespans()
{
    $intervals = array(
        '1n'  => xarML('#(1) minute',1),
        '5n'  => xarML('#(1) minutes',5),
        '10n' => xarML('#(1) minutes',10),
        '15n' => xarML('#(1) minutes',15),
        '30n' => xarML('#(1) minutes',30),

        '1h'  => xarML('#(1) hour', 1),
        '2h'  => xarML('#(1) hours',2),
        '3h'  => xarML('#(1) hours',3),
        '4h'  => xarML('#(1) hours',4),
        '5h'  => xarML('#(1) hours',5),
        '6h'  => xarML('#(1) hours',6),
        '6h'  => xarML('#(1) hours',6),
        '8h'  => xarML('#(1) hours',8),
        '9h'  => xarML('#(1) hours',9),
        '10h' => xarML('#(1) hours',10),
        '11h' => xarML('#(1) hours',11),
        '12h' => xarML('#(1) hours',12),

        '1d'  => xarML('#(1) day',1),
        '2d'  => xarML('#(1) days',2),
        '3d'  => xarML('#(1) days',3),
        '4d'  => xarML('#(1) days',4),
        '5d'  => xarML('#(1) days',5),
        '6d'  => xarML('#(1) days',6),

        '1w'  => xarML('#(1) week',1),
        '2w'  => xarML('#(1) weeks',2),
        '3w'  => xarML('#(1) weeks',3),

        '1m'  => xarML('#(1) month',1),
        '2m'  => xarML('#(1) months',2),
        '3m'  => xarML('#(1) months',3),
        '4m'  => xarML('#(1) months',4),
        '5m'  => xarML('#(1) months',5),
        '6m'  => xarML('#(1) months',6),
    );

    return $intervals;
}

?>
