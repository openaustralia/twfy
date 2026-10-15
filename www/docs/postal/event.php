<?php

/**
 * @file
 * Receives Postal's signed delivery webhooks, and suppresses alert emails to
 * addresses that have hard-bounced. Served at /postal/event, the same path
 * Planning Alerts uses, which the web server maps to this file without a
 * trailing slash, so a POST isn't redirected and its body lost.
 */

use GuzzleHttp\Client;
use OpenAustralia\TWFY\PostalSigningKeys;
use OpenAustralia\TWFY\PostalWebhook;

include_once '../../includes/easyparliament/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}

// Postal publishes its signing key here. The default is the production Postal
// server; conf/general can override it.
$jwks_url = defined('POSTAL_JWKS_URL') ? POSTAL_JWKS_URL : 'https://postal.oaf.org.au/.well-known/jwks.json';

$uid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
$cache_path = PostalSigningKeys::cachePath(sys_get_temp_dir(), $uid);

$signing_keys = new PostalSigningKeys(new Client(), $jwks_url, $cache_path);
$webhook = new PostalWebhook([$signing_keys, 'pems']);

http_response_code($webhook->handle(
    file_get_contents('php://input'),
    $_SERVER['HTTP_X_POSTAL_SIGNATURE_256'] ?? null
));
