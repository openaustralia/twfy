<?php

/**
 * @file
 * Who is signed in, from the cookies a browser sends back to a legacy page. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Auth\LoginPage;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * Signs in through Fortify and returns the cookies it sets, as a browser would keep them.
 *
 * @param array<string, string> $extra
 *   Form fields beyond the email address and password.
 *
 * @return array<string, string>
 *   Cookie values by name.
 */
function cookies_spec_sign_in(array $extra = []): array {
    $form = ['email' => 'cass.cookiespec@example.test', 'password' => 'correct-horse'] + $extra;
    $response = twfy_laravel()->make(Kernel::class)->handle(Request::create('/user/login', 'POST', $form));

    $cookies = [];
    foreach ($response->headers->getCookies() as $cookie) {
        $cookies[$cookie->getName()] = $cookie->getValue();
    }
    return $cookies;
}

/**
 * Makes the next legacy page see only these cookies, with nothing remembered from earlier requests.
 *
 * @param array<string, string> $cookies
 *   Cookie values by name.
 */
function cookies_spec_visit_with(array $cookies): void {
    $app = twfy_laravel();
    $app->make('auth')->forgetGuards();
    $app->make('session')->forgetDrivers();
    $app->forgetInstance('session.store');
    // The session handler records who is signed in from this, so it mustn't keep the last test's user.
    $app->forgetInstance('auth.driver');
    $_COOKIE = $cookies;
}

/**
 * The name of the "remember me" cookie.
 */
function cookies_spec_remember_name(): string {
    return 'remember_web_' . sha1(SessionGuard::class);
}

beforeEach(function () {
    // The legacy page layout isn't loaded in tests, and signing in doesn't draw the page.
    twfy_laravel()->bind(LoginPage::class, fn () => new LoginPage());
    $this->user = User::create([
        'firstname' => 'Cass',
        'lastname' => 'Cookiespec',
        'email' => 'cass.cookiespec@example.test',
        'password' => password_hash('correct-horse', PASSWORD_DEFAULT),
        'confirmed' => true,
    ]);
});

afterEach(function () {
    cookies_spec_visit_with([]);
});

describe('twfy_laravel_user()', function () {
    describe('with the session cookie', function () {
        it('finds the user who signed in', function () {
            $cookies = cookies_spec_sign_in();
            cookies_spec_visit_with(['oa_session' => $cookies['oa_session']]);

            expect(twfy_laravel_user()?->email)->toBe('cass.cookiespec@example.test');
        });
    });

    describe('with only the "remember me" cookie', function () {
        it('finds the user who ticked remember me', function () {
            $cookies = cookies_spec_sign_in(['remember' => 'true']);
            cookies_spec_visit_with([cookies_spec_remember_name() => $cookies[cookies_spec_remember_name()]]);

            expect(twfy_laravel_user()?->email)->toBe('cass.cookiespec@example.test');
        });
    });

    describe('with no cookies', function () {
        it('finds nobody', function () {
            cookies_spec_visit_with([]);

            expect(twfy_laravel_user())->toBeNull();
        });
    });

    describe('with a session cookie that is not ours', function () {
        it('finds nobody', function () {
            cookies_spec_visit_with(['oa_session' => 'not-a-real-cookie']);

            expect(twfy_laravel_user())->toBeNull();
        });
    });
});

describe('THEUSER::logout()', function () {
    describe('with the cookies from before', function () {
        it('leaves them unable to sign in', function () {
            $cookies = cookies_spec_sign_in(['remember' => 'true']);
            cookies_spec_visit_with($cookies);
            (new THEUSER())->logout('/safe-path');

            cookies_spec_visit_with($cookies);

            expect(twfy_laravel_user())->toBeNull();
        });
    });
});

describe('THEUSER::update_self()', function () {
    beforeEach(function () {
        $this->cookies = cookies_spec_sign_in(['remember' => 'true']);
        cookies_spec_visit_with($this->cookies);
        $this->visitor = new THEUSER();
        $this->visitor->update_self([
            'firstname' => 'Cass',
            'lastname' => 'Cookiespec',
            'email' => 'cass.cookiespec@example.test',
            'constituency' => '',
            'url' => '',
            'password' => 'a-new-password',
        ]);
    });

    describe('with a new password', function () {
        it('ends the sign-ins from before', function () {
            cookies_spec_visit_with($this->cookies);

            expect(twfy_laravel_user())->toBeNull();
        });

        it('signs the browser in again', function () {
            expect(DB::table('sessions')->where('user_id', $this->user->user_id)->count())->toBe(1);
        });
    });
});
