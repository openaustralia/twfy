<?php

/**
 * @file
 */

if (!function_exists('strptime')) {

    /**
     * Implementation of strptime() for PHP on Windows.
     * Modified from http://au.php.net/manual/en/function.strptime.php#82004
     *
     * @param string $date
     * @param string $format
     *
     * @return array
     */
    function strptime($date, $format) {
        if (!($date = strToDate($date, $format))) {
            return;
        }
        $dateTime = ['sec' => 0, 'min' => 0, 'hour' => 0, 'day' => 0, 'mon' => 0, 'year' => 0, 'timestamp' => 0];
        foreach ($date as $key => $val) {
            switch ($key) {
                case 'd':
                case 'j':
                    $dateTime['tm_mday'] = intval($val);

                  break;

                case 'D':
                    $dateTime['tm_mday'] = intval(date('j', $val));

                  break;

                case 'm':
                case 'n':
                    $dateTime['tm_mon'] = intval($val);

                  break;

                case 'M':
                    $dateTime['tm_mon'] = intval(date('n', $val));

                  break;

                case 'Y':
                    $dateTime['tm_year'] = intval($val);

                  break;

                case 'y':
                    $dateTime['tm_year'] = intval($val) + 2000;

                  break;

                case 'G':
                case 'g':
                case 'H':
                case 'h':
                    $dateTime['tm_hour'] = intval($val);

                  break;

                case 'i':
                    $dateTime['tm_min'] = intval($val);

                  break;

                case 's':
                    $dateTime['tm_sec'] = intval($val);

                  break;
            }
        }
        $dateTime['timestamp'] = mktime($dateTime['hour'], $dateTime['min'], $dateTime['sec'], $dateTime['mon'], $dateTime['day'], $dateTime['year']);

        /*echo '<pre>';
        print_r($dateTime);
        echo '</pre>';*/

        return $dateTime;
    };

    /**
     * Called by strptime().
     * Modified from http://au.php.net/manual/en/function.strptime.php#81611
     *
     * @param string $date
     * @param string $format
     *
     * @return array
     */
    function strToDate($date, $format) {
        // Day.
        $search = [
          '%d',
          '%D',
          '%j',
          // Month.
          '%m',
          '%M',
          '%n',
          // Year.
          '%Y',
          '%y',
          // Hour.
          '%G',
          '%g',
          '%H',
          '%h',
          '%i',
          '%s'
        ];
        // Day.
        $replace = [
          '(\d{2})',
          '(\w{3})',
          '(\d{1,2})',
          // Month.
          '(\d{2})',
          '(\w{3})',
          '(\d{1,2})',
          // Year.
          '(\d{4})',
          '(\d{2})',
          // Hour.
          '(\d{1,2})',
          '(\d{1,2})',
          '\d{2}',
          '\d{2}',
          '(\d{2})',
          '(\d{2})'
        ];

        $pattern = str_replace($search, $replace, $format);

        if (!preg_match("#$pattern#", $date, $matches)) {
            return FALSE;
        }
        $dp = $matches;

        if (!preg_match_all('#%(\w)#', $format, $matches)) {
            return FALSE;
        }
        $id = $matches['1'];

        if (count($dp) != count($id) + 1) {
            return FALSE;
        }

        $ret = [];
        for ($i = 0, $j = count($id); $i < $j; $i++) {
            $ret[$id[$i]] = $dp[$i + 1];
        }

        // Echo '<pre>';
        // print_r($ret);
        // echo '</pre>';.
        return $ret;
    };

}
