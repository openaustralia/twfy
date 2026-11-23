<?php

/**
 * @file
 * Choosing a new password from an emailed link, through Fortify's routes. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/utility.php';

if (!defined('CONTACTEMAIL')) {
    define('CONTACTEMAIL', 'contact@example.test');
}

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Auth\ErrorPage;
use OpenAustralia\TWFY\Auth\PasswordPages;
use OpenAustralia\TWFY\Models\Session;
use OpenAustralia\TWFY\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(TransactionalTestCase::class);

/**
 * Sends a request to Laravel, with a session's cookies.
 *
 * @param array<string, string> $input
 *   Form fields.
 * @param array<string, string> $cookies
 *   Cookies by name, as an earlier response set them.
 */
function reset_spec_call(string $method, string $uri, array $input = [], array $cookies = []): Response {
    return twfy_laravel()->make(Kernel::class)->handle(Request::create($uri, $method, $input, $cookies));
}

/**
 * Starts a session by fetching a form, and returns its cookies and CSRF token.
 *
 * The forms are faked to print only the token.
 *
 * @return array{cookies: array<string, string>, token: string}
 *   The session.
 */
function reset_spec_session(): array {
    $response = reset_spec_call('GET', '/user/password');
    $cookies = [];
    foreach ($response->headers->getCookies() as $cookie) {
        $cookies[$cookie->getName()] = $cookie->getValue();
    }
    return ['cookies' => $cookies, 'token' => $response->getContent()];
}

/**
 * Posts a form with a valid CSRF token.
 *
 * @param array<string, string> $input
 *   Form fields.
 */
function reset_spec_post(string $uri, array $input): Response {
    $session = reset_spec_session();
    return reset_spec_call('POST', $uri, $input + ['_token' => $session['token']], $session['cookies']);
}

beforeEach(function () {
    $app = twfy_laravel();
    $app->bind(PasswordPages::class, fn () => new class extends PasswordPages {

        public function requestLink(Request $request): string {
            return $request->session()->token();
        }

        public function resetForm(Request $request): string {
            return $request->session()->token();
        }

    });

    // The legacy page layout isn't loaded in tests, so draw the error pages alone.
    $app->bind(ErrorPage::class, fn () => new class extends ErrorPage {

        public function render(int $status): string {
            return 'error ' . $status;
        }

    });

    $this->sent = [];
    $GLOBALS['mail_transport'] = function (string $to, string $subject, string $message) {
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'message' => $message];
        return true;
    };

    $this->user = User::create([
        'firstname' => 'Pat',
        'lastname' => 'Resetspec',
        'email' => 'pat.resetspec@example.test',
        'password' => password_hash('old-password', PASSWORD_DEFAULT),
        'confirmed' => true,
    ]);
    $this->token = fn () => $app->make('auth.password')->broker()->createToken($this->user);
});

afterEach(function () {
    unset($GLOBALS['mail_transport']);
    laravel_spec_new_request();
});

describe('POST /user/password', function () {
    describe('for an address with an account', function () {
        it('emails it a reset link', function () {
            reset_spec_post('/user/password', ['email' => 'pat.resetspec@example.test']);

            expect($this->sent[0]['message'])->toContain('/user/password/reset/');
        });

        it('emails only that address', function () {
            reset_spec_post('/user/password', ['email' => 'pat.resetspec@example.test']);

            expect(array_column($this->sent, 'to'))->toBe(['pat.resetspec@example.test']);
        });

        it('sends no password', function () {
            reset_spec_post('/user/password', ['email' => 'pat.resetspec@example.test']);

            expect($this->sent[0]['message'])->not->toContain('old-password');
        });

        it('leaves the password alone', function () {
            reset_spec_post('/user/password', ['email' => 'pat.resetspec@example.test']);

            expect(password_verify('old-password', $this->user->fresh()->password))->toBeTrue();
        });

        describe('sent with a forged Host header', function () {
            it('links to the site, not that host', function () {
                $session = reset_spec_session();

                reset_spec_call('POST', 'http://attacker.example/user/password', ['email' => 'pat.resetspec@example.test', '_token' => $session['token']], $session['cookies']);

                expect($this->sent[0]['message'])->toContain(config('app.url') . '/user/password/reset/')->not->toContain('attacker.example');
            });
        });
    });

    describe('for an address with no account', function () {
        it('emails nobody', function () {
            reset_spec_post('/user/password', ['email' => 'nobody.resetspec@example.test']);

            expect($this->sent)->toBe([]);
        });

        it('answers as it does for an account', function () {
            $known = reset_spec_post('/user/password', ['email' => 'pat.resetspec@example.test']);
            $unknown = reset_spec_post('/user/password', ['email' => 'nobody.resetspec@example.test']);

            expect([$unknown->getStatusCode(), $unknown->headers->get('Location')])->toBe([$known->getStatusCode(), $known->headers->get('Location')]);
        });
    });

    describe('for a deleted user', function () {
        it('emails nobody', function () {
            $this->user->update(['deleted' => true]);

            reset_spec_post('/user/password', ['email' => 'pat.resetspec@example.test']);

            expect($this->sent)->toBe([]);
        });
    });

    describe('without a CSRF token', function () {
        it('answers 419', function () {
            expect(reset_spec_call('POST', '/user/password', ['email' => 'pat.resetspec@example.test'])->getStatusCode())->toBe(419);
        });
    });
});

describe('POST /user/password/reset', function () {
    beforeEach(function () {
        $this->form = fn (array $changes = []) => $changes + [
            'token' => ($this->token)(),
            'email' => 'pat.resetspec@example.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];
    });

    describe('with a good link and password', function () {
        it('saves the password', function () {
            reset_spec_post('/user/password/reset', ($this->form)());

            expect(password_verify('new-password', $this->user->fresh()->password))->toBeTrue();
        });

        it('saves it as bcrypt', function () {
            reset_spec_post('/user/password/reset', ($this->form)());

            expect($this->user->fresh()->password)->toStartWith('$2y$');
        });

        it('sends them to the login page', function () {
            expect(reset_spec_post('/user/password/reset', ($this->form)())->headers->get('Location'))->toEndWith('/user/login');
        });

        it('signs them out everywhere', function () {
            Session::create(['id' => 'reset-spec-session', 'user_id' => $this->user->user_id, 'payload' => '', 'last_activity' => time()]);

            reset_spec_post('/user/password/reset', ($this->form)());

            expect(Session::find('reset-spec-session'))->toBeNull();
        });

        it('replaces their "remember me" token', function () {
            $this->user->forceFill(['remember_token' => 'old-token'])->save();

            reset_spec_post('/user/password/reset', ($this->form)());

            expect($this->user->fresh()->remember_token)->not->toBe('old-token');
        });

        it('works once', function () {
            $form = ($this->form)();
            reset_spec_post('/user/password/reset', $form);

            reset_spec_post('/user/password/reset', array_merge($form, ['password' => 'second-password', 'password_confirmation' => 'second-password']));

            expect(password_verify('new-password', $this->user->fresh()->password))->toBeTrue();
        });
    });

    describe('with a link for another email address', function () {
        it('leaves the password alone', function () {
            $other = User::create(['firstname' => 'Sam', 'lastname' => 'Resetspec', 'email' => 'sam.resetspec@example.test', 'password' => password_hash('sam-password', PASSWORD_DEFAULT), 'confirmed' => true]);

            reset_spec_post('/user/password/reset', ($this->form)(['email' => 'sam.resetspec@example.test']));

            expect(password_verify('sam-password', $other->fresh()->password))->toBeTrue();
        });
    });

    describe('with an email address that has no account', function () {
        beforeEach(function () {
            // The message the reset form shows, as the redirect back flashes it.
            $this->messageFor = fn (array $changes): string => reset_spec_post('/user/password/reset', ($this->form)($changes))->getSession()->get('errors')->first('email');
        });

        it('says what it says for a bad link', function () {
            expect(($this->messageFor)(['email' => 'nobody.resetspec@example.test']))->toBe(($this->messageFor)(['token' => 'made-up']));
        });

        it('does not say a link was sent', function () {
            expect(($this->messageFor)(['email' => 'nobody.resetspec@example.test']))->toBe(trans('passwords.token'));
        });
    });

    describe('with a made-up token', function () {
        it('leaves the password alone', function () {
            reset_spec_post('/user/password/reset', ($this->form)(['token' => 'made-up']));

            expect(password_verify('old-password', $this->user->fresh()->password))->toBeTrue();
        });
    });

    describe('with a password that is not acceptable', function () {
        dataset('bad passwords', [
            'too short' => [['password' => 'abc', 'password_confirmation' => 'abc']],
            'not repeated' => [['password' => 'new-password', 'password_confirmation' => 'other-password']],
            'empty' => [['password' => '', 'password_confirmation' => '']],
        ]);

        it('leaves the password alone', function (array $password) {
            reset_spec_post('/user/password/reset', ($this->form)($password));

            expect(password_verify('old-password', $this->user->fresh()->password))->toBeTrue();
        })->with('bad passwords');

        it('keeps the link usable', function (array $password) {
            $form = ($this->form)($password);
            reset_spec_post('/user/password/reset', $form);

            reset_spec_post('/user/password/reset', array_merge($form, ['password' => 'new-password', 'password_confirmation' => 'new-password']));

            expect(password_verify('new-password', $this->user->fresh()->password))->toBeTrue();
        })->with('bad passwords');
    });
});
