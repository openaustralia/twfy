<?php

/**
 * @file
 * Every user's role, and a form for Superusers to change them.
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\Admin\UsersController;

$controller = new UsersController();

// The change redirects back to the list, so it is handled before any of the page is drawn.
$redirect = $controller->update();
if ($redirect !== null) {
    header('Location: ' . $redirect);
    exit;
}

$controller->index();
