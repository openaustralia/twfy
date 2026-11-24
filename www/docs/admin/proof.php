<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\Admin\ProofController;

(new ProofController())->overview();
