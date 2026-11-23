<?php

/**
 * @file
 * Laravel's session, read from a legacy page (docs/adr/0002-laravel-auth-replaces-homegrown-sign-in.md).
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Cookie\CookieValuePrefix;
use OpenAustralia\TWFY\Models\Session;

uses(TransactionalTestCase::class);

beforeEach(function () {
    $app = twfy_laravel();
    // A session id is 40 letters and digits.
    $id = 'flashspecsession0000000000000000000000AB';
    $name = $app->make('config')->get('session.cookie');
    $encrypter = $app->make('encrypter');
    $payload = ['_token' => 'spec-token', 'status' => 'Your password has been changed', '_flash' => ['old' => ['status'], 'new' => []]];
    Session::create(['id' => $id, 'payload' => base64_encode(serialize($payload)), 'last_activity' => time()]);

    $this->saved = $_COOKIE;
    $_COOKIE = [$name => $encrypter->encrypt(CookieValuePrefix::create($name, $encrypter->getKey()) . $id, false)];
});

afterEach(function () {
    $_COOKIE = $this->saved;
    $app = twfy_laravel();
    $app->make('session')->forgetDrivers();
    $app->forgetInstance('session.store');
});

describe('twfy_laravel_with_session()', function () {
    describe('when a legacy page reads the session first', function () {
        it('leaves the flash data for the next Laravel request', function () {
            twfy_laravel_with_session(fn () => null);
            twfy_laravel()->make('session')->forgetDrivers();
            twfy_laravel()->forgetInstance('session.store');

            expect(twfy_laravel_with_session(fn () => twfy_laravel()->make('session.store')->get('status')))->toBe('Your password has been changed');
        });
    });

    describe('with the session cookie', function () {
        it('runs the callback with the session started', function () {
            expect(twfy_laravel_with_session(fn () => twfy_laravel()->make('session.store')->token()))->toBe('spec-token');
        });
    });
});
