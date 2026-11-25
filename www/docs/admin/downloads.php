<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\Admin\UsageController;

(new UsageController())->downloads();
