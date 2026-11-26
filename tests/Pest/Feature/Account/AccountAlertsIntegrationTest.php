<?php

/**
 * @file
 * The alerts on /account, and unsubscribing from them. Uses fictional users and members.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AccountRequests.php';
require_once __DIR__ . '/../../Support/AlertFixtures.php';
require_once __DIR__ . '/../../Support/MemberFixtures.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use OpenAustralia\TWFY\EmailSuppressions;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

defined('WEBPATH') || define('WEBPATH', '/');

beforeEach(function () {
    MemberPageStubs::bind();

    $this->user = User::create(['firstname' => 'Pat', 'lastname' => 'Alertspec', 'email' => 'pat.alertspec@example.test', 'password' => 'stored-hash', 'confirmed' => true]);
    $this->session = AccountRequests::signIn($this->user);
    $this->page = fn () => AccountRequests::send('GET', '/account', [], $this->session)->getContent();
    MemberFixtures::term(97201, ['first_name' => 'Alex', 'last_name' => 'Alertspec']);
});

afterEach(function () {
    MemberPageStubs::restoreVisitor();
    laravel_spec_new_request();
});

describe('the alerts on GET /account', function () {
    it('says when there are none', function () {
        expect(($this->page)())->toContain("You don't have any alerts yet");
    });

    it('lists an alert about a person by their name', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'speaker:97201');

        expect(($this->page)())->toContain('Alex Alertspec')->toContain('Emails you when they speak');
    });

    it('lists an alert about a word', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'wellington');

        expect(($this->page)())->toContain('“wellington”')->toContain('Emails you when anyone says this in Parliament');
    });

    it('lists an alert about a person and a word', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'speaker:97201 wellington');

        expect(($this->page)())->toContain('Emails you when Alex Alertspec says this in Parliament');
    });

    it('counts the active alerts', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'wellington');
        AlertFixtures::alert('pat.alertspec@example.test', 'speaker:97201');

        expect(($this->page)())->toContain('You have 2 alerts.');
    });

    it('links to the search for an alert', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'speaker:97201 climate change');

        expect(($this->page)())->toContain('href="/search/?pid=97201&amp;s=climate+change"');
    });

    it('escapes the words of an alert', function () {
        AlertFixtures::alert('pat.alertspec@example.test', '<script>alert(1)</script>');

        expect(($this->page)())->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')->not->toContain('<script>alert(1)</script>');
    });

    it('escapes the words in the link to their search', function () {
        AlertFixtures::alert('pat.alertspec@example.test', '"><script>alert(1)</script>');

        expect(($this->page)())->not->toContain('"><script>alert(1)</script>');
    });

    it('leaves out other addresses alerts', function () {
        AlertFixtures::alert('someone.else.alertspec@example.test', 'secretword');

        expect(($this->page)())->not->toContain('secretword');
    });

    it('offers to unsubscribe from an active alert', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'wellington');

        expect(($this->page)())->toContain('Unsubscribe');
    });

    it('lists unsubscribed alerts apart', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'wellington', deleted: true);

        expect(($this->page)())->toContain('Subscribe again')->toContain("You don't have any alerts yet");
    });

    it('lists alerts waiting to be confirmed apart', function () {
        AlertFixtures::alert('pat.alertspec@example.test', 'wellington', confirmed: false, token: 'abc123');

        expect(($this->page)())->toContain('Waiting for you to confirm')->toContain('/A/');
    });

    it('says when emails to the address are bouncing', function () {
        EmailSuppressions::suppress('pat.alertspec@example.test', 'hard_bounce', suppressedAt: '2026-10-05 10:00:00');

        expect(($this->page)())->toContain('returning as undeliverable since 5 October 2026');
    });

    it('says nothing about bounces otherwise', function () {
        expect(($this->page)())->not->toContain('undeliverable');
    });
});

describe('DELETE /account/alerts/{alert}', function () {
    beforeEach(function () {
        $this->alert = AlertFixtures::alert('pat.alertspec@example.test', 'wellington');
        $this->unsubscribe = fn (int $id, bool $withToken = true) => AccountRequests::send('DELETE', "/account/alerts/$id", [], $this->session, $withToken);
    });

    it('unsubscribes', function () {
        ($this->unsubscribe)($this->alert->alert_id);

        expect($this->alert->fresh()->deleted)->toBeTrue();
    });

    it('goes back to the details page', function () {
        expect(($this->unsubscribe)($this->alert->alert_id)->headers->get('Location'))->toEndWith('/account');
    });

    it('says it unsubscribed', function () {
        ($this->unsubscribe)($this->alert->alert_id);

        expect(($this->page)())->toContain('You&#039;ve unsubscribed from that alert');
    });

    it('needs a CSRF token', function () {
        expect(($this->unsubscribe)($this->alert->alert_id, false)->getStatusCode())->toBe(419);
    });

    it('does nothing without a CSRF token', function () {
        ($this->unsubscribe)($this->alert->alert_id, false);

        expect($this->alert->fresh()->deleted)->toBeFalse();
    });

    it('answers 404 for another address alert', function () {
        $other = AlertFixtures::alert('someone.else.alertspec@example.test', 'wellington');

        expect(($this->unsubscribe)($other->alert_id)->getStatusCode())->toBe(404);
    });

    it('leaves another address alert alone', function () {
        $other = AlertFixtures::alert('someone.else.alertspec@example.test', 'wellington');

        ($this->unsubscribe)($other->alert_id);

        expect($other->fresh()->deleted)->toBeFalse();
    });

    it('answers 404 for an alert nobody confirmed', function () {
        $unconfirmed = AlertFixtures::alert('pat.alertspec@example.test', 'wellington', confirmed: false);

        expect(($this->unsubscribe)($unconfirmed->alert_id)->getStatusCode())->toBe(404);
    });

    it('answers 404 for an alert that does not exist', function () {
        expect(($this->unsubscribe)(999999999)->getStatusCode())->toBe(404);
    });

    it('does not act on a GET', function () {
        AccountRequests::send('GET', '/account/alerts/' . $this->alert->alert_id, [], $this->session);

        expect($this->alert->fresh()->deleted)->toBeFalse();
    });

    it('answers a visitor who is signed out with a missing CSRF token', function () {
        expect(AccountRequests::send('DELETE', '/account/alerts/' . $this->alert->alert_id)->getStatusCode())->toBe(419);
    });
});

describe('PUT /account/alerts/{alert}', function () {
    beforeEach(function () {
        $this->alert = AlertFixtures::alert('pat.alertspec@example.test', 'wellington', deleted: true);
    });

    it('subscribes again', function () {
        AccountRequests::send('PUT', '/account/alerts/' . $this->alert->alert_id, [], $this->session);

        expect($this->alert->fresh()->deleted)->toBeFalse();
    });

    it('answers 404 for another address alert', function () {
        $other = AlertFixtures::alert('someone.else.alertspec@example.test', 'wellington', deleted: true);

        expect(AccountRequests::send('PUT', '/account/alerts/' . $other->alert_id, [], $this->session)->getStatusCode())->toBe(404);
    });

    it('needs a CSRF token', function () {
        expect(AccountRequests::send('PUT', '/account/alerts/' . $this->alert->alert_id, [], $this->session, false)->getStatusCode())->toBe(419);
    });
});
