<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use League\Plates\Engine;
use OpenAustralia\TWFY\AlertUnsubscribePage;
use OpenAustralia\TWFY\Models\Alert as AlertModel;

uses(TransactionalTestCase::class);

defined('WEBPATH') || define('WEBPATH', '/');
defined('CONTACTEMAIL') || define('CONTACTEMAIL', 'contact@example.invalid');

/**
 * Renders one of the page's views.
 *
 * @param string $view
 *   The view's name.
 * @param array<string, string> $data
 *   What the view needs.
 *
 * @return string
 *   The HTML.
 */
function render_unsubscribe_view(string $view, array $data): string {
    return (new Engine(__DIR__ . '/../../www/resources/views'))->render($view, $data);
}

/**
 * How many alerts at the page test's address are removed.
 *
 * @return int
 *   The count.
 */
function removed_page_test_alerts(): int {
    return AlertModel::where('deleted', true)->where('email', 'page.test@example.invalid')->count();
}

// The /alert/unsubscribe/ page's decisions and views, run in this process.
// AlertUnsubscribePageIntegrationTest runs index.php itself, in another one.
describe('AlertUnsubscribePage::handle()', function () {
    beforeEach(function () {
        $alert = AlertFixtures::alert('page.test@example.invalid', 'climate', true, false, 'pagetesttoken');
        $this->token = $alert->alert_id . '-pagetesttoken';
    });

    describe('with a GET', function () {
        beforeEach(function () {
            $this->response = AlertUnsubscribePage::handle('GET', $this->token, new ALERT());
        });

        it('answers 200', function () {
            expect($this->response['status'])->toBe(200);
        });

        it('asks first', function () {
            expect($this->response['page'])->toBe('alertunsubscribeconfirm');
        });

        it('posts back to the same token', function () {
            expect($this->response['data']['actionUrl'])->toBe('/alert/unsubscribe/?t=' . urlencode($this->token));
        });

        it('removes nothing', function () {
            expect(removed_page_test_alerts())->toBe(0);
        });
    });

    describe('with a POST', function () {
        beforeEach(function () {
            $this->response = AlertUnsubscribePage::handle('POST', $this->token, new ALERT());
        });

        it('answers 200', function () {
            expect($this->response['status'])->toBe(200);
        });

        it('says it succeeded', function () {
            expect($this->response['page'])->toBe('alertunsubscribesucceeded');
        });

        it('removes the alerts', function () {
            expect(removed_page_test_alerts())->toBe(1);
        });
    });

    describe('with a bad token', function () {
        it('answers 400', function (string $method, mixed $token) {
            expect(AlertUnsubscribePage::handle($method, $token, new ALERT())['status'])->toBe(400);
        })->with(['GET', 'POST'])->with(['an unknown alert' => '1-nosuchtoken', 'an array' => [['bad']]]);

        it('says it failed', function (string $method) {
            expect(AlertUnsubscribePage::handle($method, '1-nosuchtoken', new ALERT())['page'])->toBe('alertunsubscribefailed');
        })->with(['GET', 'POST']);
    });
});

describe('the confirm view', function () {
    beforeEach(function () {
        $this->html = render_unsubscribe_view('alert/unsubscribe-confirm', ['actionUrl' => '/alert/unsubscribe/?t=1-a%26b', 'signUpUrl' => '/alert/']);
    });

    it('posts to the page with the token', function () {
        expect($this->html)->toContain('<form action="/alert/unsubscribe/?t=1-a%26b" method="post">');
    });

    it('offers the button', function () {
        expect($this->html)->toContain('Unsubscribe from all alerts');
    });

    it('links back to sign up', function () {
        expect($this->html)->toContain('<a href="/alert/">sign up for an alert again</a>');
    });
});

describe('the succeeded view', function () {
    beforeEach(function () {
        $this->html = render_unsubscribe_view('alert/unsubscribe-succeeded', ['signUpUrl' => '/alert/']);
    });

    it('says the alerts are gone', function () {
        expect($this->html)->toContain('Your alerts have been unsubscribed');
    });

    it('links back to sign up', function () {
        expect($this->html)->toContain('<a href="/alert/">sign up for an alert again</a>');
    });
});

describe('the failed view', function () {
    beforeEach(function () {
        $this->html = render_unsubscribe_view('alert/unsubscribe-failed', ['contactEmail' => 'contact@example.invalid']);
    });

    it('says the link looks incomplete', function () {
        expect($this->html)->toContain('appears to be incomplete');
    });

    it('offers the contact address', function () {
        expect($this->html)->toContain('mailto:contact@example.invalid');
    });
});
