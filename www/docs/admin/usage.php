<?php

/**
 * @file
 * The busiest API keys and data.openaustralia.org.au downloads, for staff.
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\Admin\UsageController;

(new UsageController())->index();
