<?php

/**
 * @file
 * Page-level specs for the public profile page, /user/?u=ID. They cover how
 * a person's name appears in the page title, the h2 and the meta keywords.
 */

require_once __DIR__ . '/../PageRenderingIntegrationTestCase.php';

use OpenAustralia\TWFY\Models\User as UserModel;

// The page runs in another process against the committed database, so the
// rows are real and have to be removed afterwards.
uses(PageRenderingIntegrationTestCase::class);

const PROFILE_PAGE = __DIR__ . '/../../www/docs/user/index.php';
const PROFILE_PAGE_EMAIL = 'profile.title.test@example.invalid';

/**
 * Create a confirmed person with the given name.
 *
 * @param string $firstname
 *   The first name.
 * @param string $lastname
 *   The last name.
 *
 * @return array<string, string>
 *   The query string for the person's profile page.
 */
function profile_query_for(string $firstname, string $lastname): array {
    $user = UserModel::create([
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => PROFILE_PAGE_EMAIL,
        'confirmed' => true,
        'deleted' => false,
        'registrationtime' => date('Y-m-d H:i:s'),
    ]);

    return ['u' => (string) $user->user_id];
}

/**
 * The text of the first element with the given tag.
 *
 * @param string $html
 *   The page.
 * @param string $tag
 *   The tag name.
 *
 * @return string
 *   The element's contents as written in the page, or an empty string.
 */
function profile_element(string $html, string $tag): string {
    return preg_match('/<' . $tag . '[^>]*>(.*?)<\/' . $tag . '>/s', $html, $matches) ? $matches[1] : '';
}

/**
 * The content of the meta keywords tag.
 *
 * @param string $html
 *   The page.
 *
 * @return string
 *   The attribute value as written in the page, or an empty string.
 */
function profile_keywords(string $html): string {
    return preg_match('/<meta name="keywords"\s+content="([^"]*)"/s', $html, $matches) ? $matches[1] : '';
}

afterEach(function () {
    UserModel::where('email', PROFILE_PAGE_EMAIL)->delete();
});

describe('/user/?u=ID', function () {
    describe('with a name containing a less-than sign', function () {
        beforeEach(function () {
            $this->page = $this->renderPage(PROFILE_PAGE, 'GET', profile_query_for('Ann<b>Lee', 'Jones'));
        });

        it('runs without error', function () {
            expect($this->page['exit'])->toBe(0);
        });

        it('keeps the character out of the title', function () {
            expect(profile_element($this->page['output'], 'title'))->not->toContain('<');
        });

        it('keeps the character out of the h2', function () {
            expect(profile_element($this->page['output'], 'h2'))->not->toContain('<');
        });

        it('keeps the character out of the keywords', function () {
            expect(profile_keywords($this->page['output']))->not->toContain('<');
        });

        it('still shows the name in the title', function () {
            expect(profile_element($this->page['output'], 'title'))->toContain('Ann');
        });
    });

    describe('with an ordinary name', function () {
        dataset('ordinary names', [
            'an apostrophe' => ['Sean', "O'Brien", "Sean O'Brien", "Sean O&#039;Brien"],
            'an ampersand' => ['Smith', '& Jones', 'Smith & Jones', 'Smith &amp; Jones'],
        ]);

        it('keeps the name in the title', function (string $first, string $last, string $shown) {
            $page = $this->renderPage(PROFILE_PAGE, 'GET', profile_query_for($first, $last));

            expect(profile_element($page['output'], 'title'))->toStartWith($shown);
        })->with('ordinary names');

        it('keeps the name in the h2', function (string $first, string $last, string $shown) {
            $page = $this->renderPage(PROFILE_PAGE, 'GET', profile_query_for($first, $last));

            expect(profile_element($page['output'], 'h2'))->toContain($shown);
        })->with('ordinary names');

        it('encodes the name in the keywords', function (string $first, string $last, string $shown, string $encoded) {
            $page = $this->renderPage(PROFILE_PAGE, 'GET', profile_query_for($first, $last));

            expect(profile_keywords($page['output']))->toContain($encoded);
        })->with('ordinary names');
    });
});
