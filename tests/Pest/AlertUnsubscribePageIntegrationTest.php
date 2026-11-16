<?php

/**
 * @file
 * Page-level specs for /alert/unsubscribe/. The helper is specified in
 * AlertUnsubscribeIntegrationTest. These run index.php itself, because the
 * promise that matters is that a GET, which mail scanners and link previews
 * send, never removes an alert.
 */

require_once __DIR__ . '/../PageRenderingIntegrationTestCase.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use OpenAustralia\TWFY\Models\Alert as AlertModel;

// The page runs in another process against the committed database, so the
// rows are real and have to be removed afterwards.
uses(PageRenderingIntegrationTestCase::class);

const UNSUBSCRIBE_PAGE = __DIR__ . '/../../www/docs/alert/unsubscribe/index.php';
const UNSUBSCRIBE_PAGE_EMAIL = 'unsubscribe.page.test@example.invalid';

/**
 * Whether the page removed an alert.
 *
 * @param int $id
 *   The alert's id.
 *
 * @return bool
 *   True if the alert is deleted.
 */
function page_removed_alert(int $id): bool {
    return AlertModel::findOrFail($id)->deleted;
}

afterEach(function () {
    AlertModel::where('email', UNSUBSCRIBE_PAGE_EMAIL)->delete();
});

describe('/alert/unsubscribe/', function () {
    beforeEach(function () {
        $this->a = AlertFixtures::alert(UNSUBSCRIBE_PAGE_EMAIL, 'speaker:1', true, false, 'pagetokena')->alert_id;
        $this->b = AlertFixtures::alert(UNSUBSCRIBE_PAGE_EMAIL, 'climate', true, false, 'pagetokenb')->alert_id;
    });

    // What a link preview or mail scanner does.
    describe('with a GET', function () {
        beforeEach(function () {
            $this->page = $this->renderPage(UNSUBSCRIBE_PAGE, 'GET', ['t' => $this->a . '-pagetokena']);
        });

        it('runs without error', function () {
            expect($this->page['exit'])->toBe(0);
        });

        it('answers 200', function () {
            expect($this->page['status'])->toBe(200);
        });

        it('asks first', function () {
            expect($this->page['output'])->toContain('Unsubscribe from all alerts');
        });

        it('leaves every alert active', function () {
            expect([page_removed_alert($this->a), page_removed_alert($this->b)])->toBe([false, false]);
        });
    });

    // What a mail app's Unsubscribe button sends.
    describe('with a POST', function () {
        beforeEach(function () {
            $this->page = $this->renderPage(UNSUBSCRIBE_PAGE, 'POST', ['t' => $this->a . '-pagetokena']);
        });

        it('runs without error', function () {
            expect($this->page['exit'])->toBe(0);
        });

        it('answers 200', function () {
            expect($this->page['status'])->toBe(200);
        });

        it('says the alerts are gone', function () {
            expect($this->page['output'])->toContain('Your alerts have been unsubscribed');
        });

        it('removes every alert at the address', function () {
            expect([page_removed_alert($this->a), page_removed_alert($this->b)])->toBe([true, true]);
        });
    });

    describe('with an invalid token', function () {
        it('answers 400', function (string $method) {
            expect($this->renderPage(UNSUBSCRIBE_PAGE, $method, ['t' => $this->a . '-wrongtoken'])['status'])->toBe(400);
        })->with(['GET', 'POST']);

        it('says the link looks incomplete', function (string $method) {
            expect($this->renderPage(UNSUBSCRIBE_PAGE, $method, ['t' => $this->a . '-wrongtoken'])['output'])->toContain('appears to be incomplete');
        })->with(['GET', 'POST']);

        it('removes nothing', function () {
            $this->renderPage(UNSUBSCRIBE_PAGE, 'POST', ['t' => $this->a . '-wrongtoken']);

            expect(page_removed_alert($this->a))->toBeFalse();
        });
    });

    // /alert/unsubscribe/?t[]=bad puts an array in the token.
    describe('with a token that is not a string', function () {
        it('answers 400, not an error', function (string $method) {
            expect($this->renderPage(UNSUBSCRIBE_PAGE, $method, ['t' => ['bad']])['status'])->toBe(400);
        })->with(['GET', 'POST']);

        it('runs without error', function (string $method) {
            expect($this->renderPage(UNSUBSCRIBE_PAGE, $method, ['t' => ['bad']])['exit'])->toBe(0);
        })->with(['GET', 'POST']);
    });
});
