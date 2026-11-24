<?php

/**
 * @file
 * Who can open each admin page, through the real page and the real sign-in. Uses fictional people and terms.
 */

require_once __DIR__ . '/../../../PageRenderingIntegrationTestCase.php';

use OpenAustralia\TWFY\Models\Editqueue;
use OpenAustralia\TWFY\Models\Glossary;
use OpenAustralia\TWFY\Models\User;

uses(PageRenderingIntegrationTestCase::class);

const ADMIN_ACCESS_DIR = __DIR__ . '/../../../../www/docs/admin/';
const ADMIN_ACCESS_NAME = 'Adminaccessspec';

/**
 * Every admin page that draws a report or a form.
 *
 * @return list<string>
 *   The file names.
 */
function admin_access_pages(): array {
    return ['alert_stats.php', 'alerts.php', 'failedsearches.php', 'glossary.php', 'glossary_pending.php', 'index.php', 'popularsearches.php', 'proof.php', 'proof_errors.php', 'proof_year.php', 'searchlogs.php', 'statistics.php', 'usage.php', 'users.php'];
}

/**
 * A fictional person with a role.
 */
function admin_access_user(string $status): User {
    return User::create(['firstname' => 'Ada', 'lastname' => ADMIN_ACCESS_NAME, 'email' => strtolower($status) . '.adminaccessspec@example.test', 'status' => $status, 'password' => 'stored-hash', 'confirmed' => 1]);
}

afterEach(function () {
    Editqueue::where('title', ADMIN_ACCESS_NAME)->delete();
    Glossary::where('title', ADMIN_ACCESS_NAME)->delete();
    User::where('lastname', ADMIN_ACCESS_NAME)->delete();
});

describe('an admin page', function () {
    it('turns away a regular user', function (string $page) {
        $cookies = $this->signedInCookies(admin_access_user('User'));

        expect($this->renderPage(ADMIN_ACCESS_DIR . $page, 'GET', [], [], $cookies)['output'])->toContain("That's all folks!");
    })->with(admin_access_pages());

    it('opens for an administrator', function (string $page) {
        $cookies = $this->signedInCookies(admin_access_user('Administrator'));

        expect($this->renderPage(ADMIN_ACCESS_DIR . $page, 'GET', [], [], $cookies)['output'])->not->toContain("That's all folks!");
    })->with(admin_access_pages());

    it('asks a visitor who is signed out to log in', function () {
        expect($this->renderPage(ADMIN_ACCESS_DIR . 'alert_stats.php')['output'])->toContain("You'd better log in!");
    });
});

describe('the glossary pages for a regular user', function () {
    beforeEach(function () {
        $this->user = admin_access_user('User');
        $this->cookies = $this->signedInCookies($this->user);
    });

    it('do not approve a submission', function () {
        $submission = Editqueue::create(['user_id' => $this->user->user_id, 'edit_type' => 2, 'title' => ADMIN_ACCESS_NAME, 'body' => 'A term.', 'submitted' => '2026-11-01 10:00:00']);

        $this->renderPage(ADMIN_ACCESS_DIR . 'glossary_pending.php', 'GET', ['approve' => (string) $submission->edit_id], [], $this->cookies);

        expect(Editqueue::find($submission->edit_id)->approved)->toBeNull();
    });

    it('do not decline a submission', function () {
        $submission = Editqueue::create(['user_id' => $this->user->user_id, 'edit_type' => 2, 'title' => ADMIN_ACCESS_NAME, 'body' => 'A term.', 'submitted' => '2026-11-01 10:00:00']);

        $this->renderPage(ADMIN_ACCESS_DIR . 'glossary_pending.php', 'GET', ['decline' => (string) $submission->edit_id], [], $this->cookies);

        expect(Editqueue::find($submission->edit_id)->approved)->toBeNull();
    });

    it('do not delete a term', function () {
        $term = Glossary::create(['title' => ADMIN_ACCESS_NAME, 'body' => 'A term.', 'type' => 2, 'visible' => true]);

        $this->renderPage(ADMIN_ACCESS_DIR . 'glossary.php', 'GET', ['delete_confirm' => (string) $term->glossary_id], [], $this->cookies);

        expect(Glossary::find($term->glossary_id))->not->toBeNull();
    });
});
