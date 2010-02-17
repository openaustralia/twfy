<?php

/*
recess.php 2004-06-05
francis@flourish.org
*/

/* Australian Parliament */
$GLOBALS['recessdates'][1] = array(
    array('2007-12-07', '2008-02-11'),
    array('2008-02-23', '2008-03-10'),
    array('2008-03-21', '2008-05-12'),
    array('2008-05-17', '2008-05-25'),
    array('2008-06-07', '2008-06-15'),
    array('2008-06-27', '2008-08-25'),
    array('2008-09-06', '2008-09-14'),
    array('2008-09-27', '2008-10-12'),
    array('2008-10-18', '2008-11-09'),
    array('2008-11-15', '2008-11-23'),
    array('2008-12-05', '2009-02-02'),
    array('2009-02-13', '2009-02-22'),
    array('2009-02-27', '2009-03-09'),
    array('2009-03-20', '2009-05-11'),
    array('2009-05-15', '2009-05-24'),
    array('2009-06-05', '2009-06-14'),
    array('2009-06-26', '2009-08-10'),
    array('2009-08-21', '2009-09-06'),
    array('2009-09-18', '2009-10-18'),
    array('2009-10-30', '2009-11-15'),
    array('2009-12-03', '2010-02-01'),
    array('2010-02-12', '2010-02-21'),
    array('2010-02-26', '2010-03-08'),
    array('2010-03-19', '2010-05-10'),
    array('2010-05-14', '2010-05-23'),
    array('2010-06-04', '2010-06-14'),
    array('2010-06-25', '2010-08-23'),
    array('2010-09-03', '2010-09-19'),
    array('2010-10-01', '2010-10-17'),
    array('2010-10-29', '2010-11-14'),
    array('2010-11-26', '2010-12-31'),
);

/*
function currently_in_recess() {
    // Main file which recesswatcher.py overwrites each day
    $h = fopen(RECESSFILE, "r");
    $today = date("Y-m-d");
    while ($line = fgets($h)){
        list($name, $from, $to) = split(",", $line);
        if ($from <= $today and $today <= $to) {
            return array($name, trim($from), trim($to));
        }
    }
    // Second manual override file
    $h = fopen(RECESSFILE.".extra", "r");
    while ($line = fgets($h)){
        list($name, $from, $to) = split(",", $line);
        if ($from <= $today and $today <= $to) {
            return array($name, trim($from), trim($to));
        }
    }
    return false;
}
*/

function recess_prettify($day, $month, $year, $body) {
    global $recessdates;
    $dates = $recessdates[$body];
    foreach ($dates as $range) {
        $from = strptime($range[0], '%Y-%m-%d');
        $to = strptime($range[1], '%Y-%m-%d');
        $from_time = mktime(0, 0, 0, $from['tm_mon'] + 1, $from['tm_mday'], $from['tm_year'] + 1900);
        $to_time = mktime(0, 0, 0, $to['tm_mon'] + 1, $to['tm_mday'], $to['tm_year'] + 1900);
        $time = mktime(0, 0, 0, $month, $day, $year);
        if ($time >= $from_time && $time <= $to_time)
            return array('recess', $range[0], $range[1]);
    }
}
?>
