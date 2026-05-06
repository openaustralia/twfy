<?php

/**
 * @file
 * Request functions.
 */

/**
 * Return remote ip address of the client
 * Will handle Cloudflare CDN when we start using it
 */
function ip_address(): string {
    // TODO: when behind Cloudflare, validate source IP is a known Cloudflare range or allowed IP before trusting these headers:
    // return $_SERVER['HTTP_CF_CONNECTING_IP']  // Cloudflare
    // ?? $_SERVER['HTTP_X_FORWARDED_FOR']   // other proxies (may be comma-list)
    return $_SERVER['REMOTE_ADDR']; // phpcs:ignore Drupal.Semantics.RemoteAddress
}

/**
 * Set the mobile flag
 * FIXME: roll out to all pages that care so we have one place to fix when/if we have a less hacky way
 */
function set_mobile(): void {
    $_SERVER['DEVICE_TYPE'] = 'mobile';
}

/**
 * Is the request from a mobile device?
 * FIXME: roll out to all pages that care so we have one place to fix when/if we have a less hacky way
 * eg assume mobile unless told otherwise from obviously mobile devices
 */
function is_mobile(): bool {
    return ($_SERVER['DEVICE_TYPE'] ?? '') === 'mobile';
}
