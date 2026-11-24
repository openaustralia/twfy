<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/searchengine.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\Admin\AlertsController;

(new AlertsController())->index();
