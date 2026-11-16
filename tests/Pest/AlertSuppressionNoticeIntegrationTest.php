<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\EmailSuppressions;

uses(TransactionalTestCase::class);

defined('WEBPATH') || define('WEBPATH', '/');

// The notice a signed-in person sees on their alerts page when their address
// is suppressed. The page itself gets a manual check, not an automated one.
describe('alert_suppression_notice()', function () {
    describe('for an address that is not suppressed', function () {
        it('shows no notice', function () {
            expect(alert_suppression_notice('alice@example.invalid'))->toBe('');
        });
    });

    describe('for a suppressed address', function () {
        beforeEach(function () {
            EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce', null, null, '2026-03-04 10:00:00');
            $this->notice = alert_suppression_notice('Alice@example.invalid');
        });

        it('says what it found', function (string $fragment) {
            expect($this->notice)->toContain($fragment);
        })->with([
            'since when' => 'returning as undeliverable since 4 March 2026',
            'that alerts are paused' => 'your alerts are paused',
            'how to resume' => 'change the email address on your account',
            'where to get help' => 'href="/contact/"',
        ]);

        // Signing up again would send a signed-in person no confirmation and
        // lift nothing, so the notice must not point there.
        it('does not point to sign-up', function () {
            expect($this->notice)->not->toContain('href="/alert/"');
        });
    });

    // The notice stays neutral and plain, and never shows the reply from the
    // receiving server, which can hold detail about someone else's system.
    describe('when the receiving server replied', function () {
        beforeEach(function () {
            EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce', null, '550 5.1.1 <script>alert(1)</script>');
            $this->notice = alert_suppression_notice('alice@example.invalid');
        });

        it('leaves out the reply', function (string $fragment) {
            expect($this->notice)->not->toContain($fragment);
        })->with(['the status code' => '550', 'any markup in it' => '<script>']);
    });

    describe('once the suppression is lifted', function () {
        it('shows no notice', function () {
            EmailSuppressions::suppress('alice@example.invalid', 'hard_bounce');
            EmailSuppressions::lift('alice@example.invalid');

            expect(alert_suppression_notice('alice@example.invalid'))->toBe('');
        });
    });

    // Only the person's own address is ever looked at.
    describe('when only another address is suppressed', function () {
        it('shows no notice', function () {
            EmailSuppressions::suppress('bob@example.invalid', 'hard_bounce');

            expect(alert_suppression_notice('alice@example.invalid'))->toBe('');
        });
    });
});
