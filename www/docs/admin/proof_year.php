<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\ProofReportView;

$this_page = "admin_proof";

// page_start() turns away anyone who is not an admin, so the report is only built after it.
$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'proof', 'title' => 'Proof status by month']);
print ProofReportView::year((int) get_http_var('year'), (int) get_http_var('month'));
$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
