<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/glossary.php";

use OpenAustralia\TWFY\Pages\GlossaryController;

(new GlossaryController())->index();
