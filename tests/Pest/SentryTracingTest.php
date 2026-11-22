<?php

/**
 * @file
 */

use Sentry\SentrySdk;
use Sentry\State\Hub;

require_once __DIR__ . '/../../www/includes/SentryTracing.php';

beforeEach(fn () => SentrySdk::setCurrentHub(new Hub()));
afterEach(fn () => SentrySdk::setCurrentHub(new Hub()));

describe('sentry_start_request_transaction()', function () {
    dataset('requests', [
        'a web page' => ['apache2handler', ['SCRIPT_NAME' => '/mp/index.php', 'REQUEST_URI' => '/mp/?m=1'], '/mp/index.php', 'http.server'],
        'a web page without SCRIPT_NAME' => ['apache2handler', [], 'unknown', 'http.server'],
        'a CLI script' => ['cli', ['argv' => ['scripts/alertmailer.php']], 'scripts/alertmailer.php', 'cli.script'],
        'a CLI script without argv' => ['cli', [], 'cli', 'cli.script'],
    ]);

    it('names the transaction', function (string $sapi, array $server, string $name) {
        expect(sentry_start_request_transaction($sapi, $server)->getName())->toBe($name);
    })->with('requests');

    it('sets the operation', function (string $sapi, array $server, string $name, string $op) {
        expect(sentry_start_request_transaction($sapi, $server)->getOp())->toBe($op);
    })->with('requests');

    it('makes it the current span', function () {
        $transaction = sentry_start_request_transaction('cli', []);

        expect(SentrySdk::getCurrentHub()->getSpan())->toBe($transaction);
    });
});
