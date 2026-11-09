<?php

/**
 * @file
 * The canonical OAF Sentry event scrubber (see docs/monitoring.md in the
 * infrastructure repo; change it there first, then update every copy).
 * Lives in its own file, like SentryBrowserScript.php, so tests can require
 * it without loading utility.php.
 */

use Sentry\Event;

/**
 * Removes email-address-shaped strings from a Sentry event.
 *
 * Scrubs breadcrumb messages and metadata, plus request query strings,
 * cookies, headers and bodies, in line with the Australian Privacy
 * Principles. Used as the before_send and before_send_transaction callback.
 */
function sentry_scrub_event(Event $event): Event {
    $pattern = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/';
    $scrub = function ($value) use (&$scrub, $pattern) {
        if (is_string($value)) {
            return preg_replace($pattern, '[FILTERED]', $value);
        }
        if (is_array($value)) {
            return array_map($scrub, $value);
        }
        return $value;
    };

    $breadcrumbs = [];
    foreach ($event->getBreadcrumbs() as $crumb) {
        if ($crumb->getMessage() !== null) {
            $crumb = $crumb->withMessage($scrub($crumb->getMessage()));
        }
        foreach ($crumb->getMetadata() as $name => $value) {
            $crumb = $crumb->withMetadata($name, $scrub($value));
        }
        $breadcrumbs[] = $crumb;
    }
    $event->setBreadcrumb($breadcrumbs);

    $request = $event->getRequest();
    foreach (['query_string', 'cookies', 'headers', 'data'] as $field) {
        if (array_key_exists($field, $request)) {
            $request[$field] = $scrub($request[$field]);
        }
    }
    $event->setRequest($request);

    return $event;
}
