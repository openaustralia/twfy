<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/searchlog.php";

use OpenAustralia\TWFY\Admin\SearchLogsController;

(new SearchLogsController())->recent();
