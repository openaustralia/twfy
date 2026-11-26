<?php

/**
 * @file
 * Page-level specs for what was the public profile page, /user/?u=ID. Nobody's details are public now. Uses
 * fictional people. The page runs in another process against the committed database, so the rows are real and
 * removed afterwards.
 */

require_once __DIR__ . '/../PageRenderingIntegrationTestCase.php';

use OpenAustralia\TWFY\Models\User as UserModel;

uses(PageRenderingIntegrationTestCase::class);

const USER_PUBLIC_PAGE = __DIR__ . '/../../www/docs/user/index.php';
const USER_PUBLIC_PAGE_LASTNAME = 'Publicpagespec';

afterEach(function () {
    UserModel::where('lastname', USER_PUBLIC_PAGE_LASTNAME)->delete();
});

describe('/user/?u=ID', function () {
    beforeEach(function () {
        $this->max = UserModel::create([
            'firstname' => 'Max',
            'lastname' => USER_PUBLIC_PAGE_LASTNAME,
            'email' => 'max.publicpagespec@example.test',
            'emailpublic' => true,
            'url' => 'https://max.example.test/',
            'confirmed' => true,
            'registrationtime' => date('Y-m-d H:i:s'),
        ]);
        $this->visit = fn (array $cookies = []) => $this->renderPage(USER_PUBLIC_PAGE, 'GET', ['u' => (string) $this->max->user_id], [], $cookies);
    });

    describe('for a visitor who is signed out', function () {
        it('redirects', function () {
            expect(($this->visit)()['status'])->toBe(301);
        });

        it('shows nothing of the user', function () {
            expect(($this->visit)()['output'])->toBe('');
        });
    });

    describe('for someone signed in', function () {
        beforeEach(function () {
            $this->ann = UserModel::create([
                'firstname' => 'Ann',
                'lastname' => USER_PUBLIC_PAGE_LASTNAME,
                'email' => 'ann.publicpagespec@example.test',
                'status' => 'User',
                'confirmed' => true,
            ]);
            $this->cookies = $this->signedInCookies($this->ann);
        });

        it('redirects', function () {
            expect(($this->visit)($this->cookies)['status'])->toBe(301);
        });

        it('shows nothing of the other user', function () {
            expect(($this->visit)($this->cookies)['output'])->toBe('');
        });
    });
});
