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
    array('2010-06-25', '2010-09-27'),
    array('2010-10-01', '2010-10-17'),
    array('2010-10-29', '2010-11-14'),
    array('2010-11-26', '2010-12-31'),
    array('2011-01-01', '2011-02-07'),
    array('2011-02-11', '2011-02-20'),
    array('2011-03-04', '2011-03-20'),
    array('2011-03-25', '2011-05-09'),
    array('2011-05-13', '2011-05-22'),
    array('2011-06-03', '2011-06-13'),
    array('2011-06-24', '2011-07-03'),
    array('2011-07-08', '2011-08-15'),
    array('2011-08-26', '2011-09-11'),
    array('2011-09-23', '2011-10-10'),
    array('2011-10-21', '2011-10-30'),
    array('2011-11-04', '2011-11-20'),
    array('2011-12-01', '2011-12-31'),
    array('2012-01-01', '2012-02-06'),
    array('2012-02-17', '2012-02-26'),
    array('2012-03-02', '2012-03-12'),
    array('2012-03-23', '2012-05-07'),
    array('2012-05-11', '2012-05-20'),
    array('2012-06-01', '2012-06-17'),
    array('2012-06-29', '2012-08-13'),
    array('2012-08-24', '2012-09-09'),
    array('2012-09-21', '2012-10-08'),
    array('2012-10-19', '2012-10-28'),
    array('2012-11-02', '2012-11-18'),
    array('2012-11-30', '2013-02-04'),
    array('2013-02-15', '2013-02-24'),
    array('2013-03-01', '2013-03-11'),
    array('2013-03-22', '2013-05-13'),
    array('2013-05-17', '2013-05-26'),
    array('2013-06-07', '2013-06-16'),
    array('2013-06-28', '2013-11-11'),
    array('2013-11-22', '2013-12-01'),
    array('2013-12-13', '2014-02-10'),
    array('2014-02-14', '2014-02-23'),
    array('2014-03-07', '2014-03-16'),
    array('2014-03-28', '2014-05-12'),
    array('2014-05-16', '2014-05-25'),
    array('2014-06-06', '2014-06-15'),
    array('2014-06-27', '2014-07-06'),
    array('2014-07-18', '2014-08-25'),
    array('2014-09-05', '2014-09-21'),
    array('2014-10-03', '2014-10-19'),
    array('2014-10-31', '2014-11-23'),
    array('2014-12-05', '2014-12-31')
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
