<?php

/**
 * @file
 * The login page, drawn with a stand-in for the legacy PAGE.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Auth\LoginPage;

// LoginPage builds the join link with the legacy URL class, which isn't loaded in tests.
if (!class_exists('URL')) {
    class URL {

        private string $page;

        public function __construct(string $page) {
            $this->page = $page;
        }

        /**
         * @param array<string, string> $params
         *   Query values.
         */
        public function insert(array $params): void {
        }

        public function generate(string $type = ''): string {
            return '/' . $this->page;
        }

    }
}

uses(TransactionalTestCase::class);

beforeEach(function () {
    $this->savedPage = $GLOBALS['PAGE'] ?? null;
    $GLOBALS['PAGE'] = new class {

        public function page_start(): void {
            echo '[page]';
        }

        public function stripe_start(): void {
        }

        /**
         * @param array<int, array<string, string>> $sidebar
         *   What goes beside the page.
         */
        public function stripe_end(array $sidebar = []): void {
            foreach ($sidebar as $item) {
                echo '[sidebar: ' . $item['content'] . ']';
            }
        }

        public function page_end(): void {
            echo '[/page]';
        }

        /**
         * @param array<string, string> $errors
         *   Messages by field.
         */
        public function login_form(array $errors = []): void {
            echo '[login form: ' . http_build_query($errors) . ']';
        }

    };
});

afterEach(function () {
    $GLOBALS['PAGE'] = $this->savedPage;
    laravel_spec_new_request();
});

describe('LoginPage', function () {
    describe('render()', function () {
        it('draws the login form inside the page', function () {
            expect((new LoginPage())->render())->toStartWith('[page]')->toContain('[login form: ]')->toEndWith('[/page]');
        });

        it('links to joining', function () {
            expect((new LoginPage())->render())->toContain('Not yet a member? <a href="/userjoin');
        });

        it('puts the login sidebar beside it', function () {
            expect((new LoginPage())->render())->toContain('[sidebar: userlogin]');
        });

        it('gives the form each field\'s first message', function () {
            $html = (new LoginPage())->render(['email' => ['Not right', 'Also wrong'], 'password' => ['Too short']]);

            expect($html)->toContain('[login form: email=Not+right&password=Too+short]');
        });
    });
});

describe('GET /user/login', function () {
    beforeEach(fn () => $this->response = twfy_laravel()->make(Kernel::class)->handle(Request::create('/user/login', 'GET')));

    it('answers 200', function () {
        expect($this->response->getStatusCode())->toBe(200);
    });

    it('draws the login page', function () {
        expect($this->response->getContent())->toStartWith('[page]')->toContain('[login form: ]');
    });
});
