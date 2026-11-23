<?php

/**
 * @file
 * Signing in at /user/login through Fortify, and out through THEUSER. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Auth\LoginPage;
use OpenAustralia\TWFY\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(TransactionalTestCase::class);

/**
 * Posts the login form to Laravel and returns its response.
 *
 * @param array<string, string> $input
 *   Form fields.
 */
function login_spec_post(array $input): Response {
    return twfy_laravel()->make(Kernel::class)->handle(Request::create('/user/login', 'POST', $input));
}

/**
 * The cookies a response sets, by name.
 *
 * @return array<string, string>
 *   Cookie names.
 */
function login_spec_cookie_names(Response $response): array {
    return array_map(fn ($cookie) => $cookie->getName(), $response->headers->getCookies());
}

beforeEach(function () {
    $app = twfy_laravel();
    // The legacy page layout isn't loaded in tests, so draw the error messages alone.
    $app->bind(LoginPage::class, fn () => new class extends LoginPage {

        public function render(array $errors = []): string {
            return implode('|', array_map(fn (array $messages): string => $messages[0], $errors));
        }

    });
    $this->user = User::create([
        'firstname' => 'Lee',
        'lastname' => 'Loginspec',
        'email' => 'lee.loginspec@example.test',
        'password' => password_hash('correct-horse', PASSWORD_DEFAULT),
        'confirmed' => true,
    ]);
    $this->form = ['email' => 'lee.loginspec@example.test', 'password' => 'correct-horse'];
});

afterEach(function () {
    $app = twfy_laravel();
    $app->make('auth')->forgetGuards();
    $app->make('session')->forgetDrivers();
    $app->forgetInstance('session.store');
});

describe('POST /user/login', function () {
    describe('with the right password', function () {
        it('redirects to the home page', function () {
            expect(login_spec_post($this->form)->headers->get('Location'))->toEndWith('/');
        });

        it('signs the user in', function () {
            login_spec_post($this->form);

            expect(twfy_laravel()->make('auth')->guard()->id())->toBe($this->user->user_id);
        });

        it('starts a session', function () {
            expect(login_spec_cookie_names(login_spec_post($this->form)))->toContain('oa_session');
        });

        it('sets no "remember me" cookie', function () {
            expect(login_spec_cookie_names(login_spec_post($this->form)))->not->toContain('remember_web_' . sha1(Illuminate\Auth\SessionGuard::class));
        });

        describe('and remember ticked', function () {
            it('sets the "remember me" cookie', function () {
                $response = login_spec_post($this->form + ['remember' => 'true']);

                expect(login_spec_cookie_names($response))->toContain('remember_web_' . sha1(Illuminate\Auth\SessionGuard::class));
            });
        });

        describe('and a ret on this site', function () {
            it('redirects there, keeping the trailing slash', function () {
                expect(login_spec_post($this->form + ['ret' => '/search/?s=tax'])->headers->get('Location'))->toEndWith('/search/?s=tax');
            });
        });

        describe('and a ret on another site', function () {
            dataset('offsite', ['absolute' => 'https://example.org/evil', 'protocol-relative' => '//example.org/evil']);

            it('redirects to the home page', function (string $ret) {
                expect(login_spec_post($this->form + ['ret' => $ret])->headers->get('Location'))->toEndWith('/');
            })->with('offsite');
        });
    });

    describe('with the wrong password', function () {
        it('answers 422', function () {
            expect(login_spec_post(['password' => 'wrong'] + $this->form)->getStatusCode())->toBe(422);
        });

        it('says they don\'t match', function () {
            expect(login_spec_post(['password' => 'wrong'] + $this->form)->getContent())->toStartWith("That email address and password don't match.");
        });

        it('signs nobody in', function () {
            login_spec_post(['password' => 'wrong'] + $this->form);

            expect(twfy_laravel()->make('auth')->guard()->check())->toBeFalse();
        });
    });

    describe('with nothing entered', function () {
        it('asks for both', function () {
            expect(login_spec_post(['email' => '', 'password' => ''])->getContent())->toBe('Please enter your email address|Please enter your password');
        });
    });

    describe('with an md5-crypt password', function () {
        it('signs the user in', function () {
            $this->user->update(['password' => crypt('legacy-pass', '$1$testsalt$')]);

            login_spec_post(['password' => 'legacy-pass'] + $this->form);

            expect(twfy_laravel()->make('auth')->guard()->id())->toBe($this->user->user_id);
        });
    });

    describe('after five wrong passwords', function () {
        it('answers 429, even with the right one', function () {
            twfy_laravel()->make('cache')->flush();
            foreach (range(1, 5) as $attempt) {
                login_spec_post(['password' => 'wrong' . $attempt] + $this->form);
            }

            expect(login_spec_post($this->form)->getStatusCode())->toBe(429);
        });
    });
});

describe('THEUSER::logout()', function () {
    describe('for a signed-in user', function () {
        it('signs them out', function () {
            $guard = twfy_laravel()->make('auth')->guard();
            $guard->setUser($this->user);

            (new THEUSER())->logout('/safe-path');

            expect($guard->check())->toBeFalse();
        });
    });

    describe('for nobody', function () {
        it('does nothing', function () {
            $guard = twfy_laravel()->make('auth')->guard();
            $guard->forgetUser();

            (new THEUSER())->logout('/safe-path');

            expect($guard->check())->toBeFalse();
        });
    });
});
