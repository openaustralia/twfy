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

// The key cache lives in a directory only this user can write. If another user
// could plant a file there they could plant a key, so without a directory we
// own and nobody else can touch, we skip the cache and fetch each time.
// The user running PHP, not getmyuid(), which is the owner of this script file
// and can differ.
$uid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
$cache_dir = sys_get_temp_dir() . '/twfy-postal-' . $uid;
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0700);
}
$cache_path = null;
if (is_dir($cache_dir) && !is_link($cache_dir) && fileowner($cache_dir) === $uid && (fileperms($cache_dir) & 0077) === 0) {
    $cache_path = $cache_dir . '/signing-keys.json';
}

$signing_keys = new PostalSigningKeys(new Client(), $jwks_url, $cache_path);
$webhook = new PostalWebhook([$signing_keys, 'pems']);

http_response_code($webhook->handle(
    file_get_contents('php://input'),
    $_SERVER['HTTP_X_POSTAL_SIGNATURE_256'] ?? null
));
