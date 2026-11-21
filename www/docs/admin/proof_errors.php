<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\ProofReportView;

$this_page = "admin_proof";

// page_start() turns away anyone who is not an admin, so the errors are only read after it.
$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'proof', 'title' => 'Import errors for a day']);
print ProofReportView::errors((string) get_http_var('date'), (string) get_http_var('house'));
$PAGE->block_end();

$menu = $PAGE->admin_menu();
$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $menu,
    ],
]);

$PAGE->page_end();
