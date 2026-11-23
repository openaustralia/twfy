<?php

/**
 * @file
 * The password reset pages and the error page, drawn with a stand-in for the legacy PAGE.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use OpenAustralia\TWFY\Auth\ErrorPage;
use OpenAustralia\TWFY\Auth\PasswordPages;

/**
 * A request with a session, as Laravel's middleware would leave it.
 *
 * @param array<string, string> $query
 *   Query string values.
 * @param array<string, mixed> $flash
 *   Session values the last request left, such as status and errors.
 */
function pages_spec_request(string $path = '/user/password', array $query = [], array $flash = []): Request {
    $request = Request::create($path, 'GET', $query);
    $session = new Store('pages-spec', new ArraySessionHandler(10));
    $session->put('_token', 'spec-csrf-token');
    foreach ($flash as $key => $value) {
        $session->put($key, $value);
    }
    $request->setLaravelSession($session);
    $request->setRouteResolver(fn () => (new Route('GET', '/user/password/reset/{token}', []))->bind($request));
    return $request;
}

/**
 * Errors as Laravel's ShareErrorsFromSession finds them.
 *
 * @param array<string, list<string>> $messages
 *   Messages by field.
 */
function pages_spec_errors(array $messages): ViewErrorBag {
    return (new ViewErrorBag())->put('default', new MessageBag($messages));
}

beforeEach(function () {
    $this->savedPage = $GLOBALS['PAGE'] ?? null;
    $GLOBALS['PAGE'] = new class {

        public function page_start(): void {
            echo '[page]';
        }

        public function stripe_start(): void {
        }

        public function stripe_end(): void {
        }

        public function page_end(): void {
            echo '[/page]';
        }

        public function error_message(string $message): void {
            echo '[error: ' . $message . ']';
        }

        /**
         * @param array<string, string> $message
         *   The title and text.
         */
        public function message(array $message): void {
            echo '[message: ' . $message['title'] . ' - ' . $message['text'] . ']';
        }

    };
});

afterEach(function () {
    $GLOBALS['PAGE'] = $this->savedPage;
});

describe('PasswordPages', function () {
    describe('requestLink()', function () {
        it('explains what the form does', function () {
            expect((new PasswordPages())->requestLink(pages_spec_request()))->toContain("can email you a link to choose a new one");
        });

        it('shows what the last request said instead', function () {
            $html = (new PasswordPages())->requestLink(pages_spec_request(flash: ['status' => "We've sent it"]));

            expect($html)->toContain('We&#039;ve sent it')->not->toContain('can email you a link');
        });

        it('fills in the email address from the link', function () {
            expect((new PasswordPages())->requestLink(pages_spec_request(query: ['email' => 'ann@example.test'])))->toContain('value="ann@example.test"');
        });

        it('escapes the email address', function () {
            expect((new PasswordPages())->requestLink(pages_spec_request(query: ['email' => '"><script>'])))->not->toContain('<script>');
        });

        it('carries the CSRF token', function () {
            expect((new PasswordPages())->requestLink(pages_spec_request()))->toContain('name="_token" value="spec-csrf-token"');
        });

        it('posts to the password.email route', function () {
            expect((new PasswordPages())->requestLink(pages_spec_request()))->toContain('action="' . e(route('password.email')) . '"');
        });

        it('shows the email error', function () {
            $html = (new PasswordPages())->requestLink(pages_spec_request(flash: ['errors' => pages_spec_errors(['email' => ['Not right']])]));

            expect($html)->toContain('[error: Not right]');
        });

        it('draws it inside the page', function () {
            expect((new PasswordPages())->requestLink(pages_spec_request()))->toStartWith('[page]')->toEndWith('[/page]');
        });
    });

    describe('resetForm()', function () {
        it('carries the token from the link', function () {
            $html = (new PasswordPages())->resetForm(pages_spec_request('/user/password/reset/abc123'));

            expect($html)->toContain('name="token" value="abc123"');
        });

        it('carries the email address from the link', function () {
            $html = (new PasswordPages())->resetForm(pages_spec_request('/user/password/reset/abc123', ['email' => 'ann@example.test']));

            expect($html)->toContain('name="email" value="ann@example.test"');
        });

        it('carries the CSRF token', function () {
            expect((new PasswordPages())->resetForm(pages_spec_request('/user/password/reset/abc123')))->toContain('name="_token" value="spec-csrf-token"');
        });

        it('posts to the password.update route', function () {
            expect((new PasswordPages())->resetForm(pages_spec_request('/user/password/reset/abc123')))->toContain('action="' . e(route('password.update')) . '"');
        });

        it('asks for the password twice', function () {
            $html = (new PasswordPages())->resetForm(pages_spec_request('/user/password/reset/abc123'));

            expect($html)->toContain('name="password"')->toContain('name="password_confirmation"');
        });

        it('shows the password error', function () {
            $html = (new PasswordPages())->resetForm(pages_spec_request('/user/password/reset/abc123', flash: ['errors' => pages_spec_errors(['password' => ['Too short']])]));

            expect($html)->toContain('[error: Too short]');
        });

        it('shows the link error', function () {
            $html = (new PasswordPages())->resetForm(pages_spec_request('/user/password/reset/abc123', flash: ['errors' => pages_spec_errors(['email' => ['That link has expired']])]));

            expect($html)->toContain('[error: That link has expired]');
        });
    });
});

describe('ErrorPage', function () {
    describe('render()', function () {
        dataset('statuses', [
            'not found' => [404, 'Page not found'],
            'expired' => [419, 'This page has expired'],
            'anything else' => [500, 'Sorry, something went wrong'],
        ]);

        it('titles the page for the status', function (int $status, string $title) {
            expect((new ErrorPage())->render($status))->toContain('[message: ' . $title);
        })->with('statuses');

        it('draws it inside the page', function () {
            expect((new ErrorPage())->render(404))->toStartWith('[page]')->toEndWith('[/page]');
        });
    });
});
