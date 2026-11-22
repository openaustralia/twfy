<?php

/**
 * @file
 * Sentry performance tracing for each request. Lives in its own file, like
 * SentryScrubber.php, so tests can require it without loading utility.php.
 */

use Sentry\SentrySdk;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;

/**
 * Starts this request's Sentry transaction and makes it the current span.
 *
 * Web pages and CLI scripts both get one, so MySQL::query() has a parent for
 * its spans. Web transactions are named by SCRIPT_NAME, not REQUEST_URI, so
 * requests for the same page group together whatever their query string.
 *
 * @param string $sapi
 *   PHP_SAPI.
 * @param array<string, mixed> $server
 *   $_SERVER.
 */
function sentry_start_request_transaction(string $sapi, array $server): Transaction {
    $cli = $sapi === 'cli';
    $context = new TransactionContext($cli ? ($server['argv'][0] ?? 'cli') : ($server['SCRIPT_NAME'] ?? 'unknown'));
    $context->setOp($cli ? 'cli.script' : 'http.server');
    $transaction = \Sentry\startTransaction($context);
    SentrySdk::getCurrentHub()->setSpan($transaction);
    return $transaction;
}
