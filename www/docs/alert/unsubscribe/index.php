<?php

/**
 * @file
 * Name: /alert/unsubscribe/index.php.
 *
 * One-click unsubscribe (RFC 8058) for alert emails. The List-Unsubscribe
 * header of every alert email points here, with t= the registration token of
 * one of the address's alerts.
 *
 * A POST, which is what a mail app's Unsubscribe button sends, removes every
 * alert at that address. A GET removes nothing and shows a page with a button
 * to do the same, because mail scanners and browsers prefetch links and a
 * link followed by a machine must not unsubscribe anyone.
 *
 * The link at the foot of each alert section, /D/, is separate and still
 * removes one alert.
 */

use OpenAustralia\TWFY\AlertUnsubscribePage;

include_once __DIR__ . "/../../../includes/easyparliament/init.php";

$response = AlertUnsubscribePage::handle($_SERVER['REQUEST_METHOD'], get_http_var('t'), new ALERT());

http_response_code($response['status']);
$this_page = $response['page'];
$PAGE->page_start();
$PAGE->stripe_start();
print get_plates_engine()->render($response['template'], $response['data']);
$PAGE->stripe_end();
$PAGE->page_end();
