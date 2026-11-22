<?php

/**
 * @file
 * Booting Laravel inside the legacy pages (docs/adr/0002-laravel-auth-replaces-homegrown-sign-in.md).
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/laravel.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Bootstrap\HandleExceptions;

uses(TransactionalTestCase::class);

describe('twfy_laravel()', function () {
    it('boots once', function () {
        expect(twfy_laravel())->toBe(twfy_laravel());
    });

    it('keeps sessions in the database', function () {
        expect(twfy_laravel()->make('config')->get('session.driver'))->toBe('database');
    });

    it('marks the session cookie HttpOnly', function () {
        expect(twfy_laravel()->make('config')->get('session.http_only'))->toBeTrue();
    });

    it('uses the legacy pages\' timezone', function () {
        expect(twfy_laravel()->make('config')->get('app.timezone'))->toBe(defined('TIMEZONE') ? TIMEZONE : 'UTC');
    });

    it('serves the legacy document root', function () {
        expect(twfy_laravel()->publicPath())->toEndWith('/www/docs');
    });

    describe('with the legacy database connection', function () {
        it('shares its connection manager', function () {
            expect(twfy_laravel()->make('db'))->toBe($GLOBALS['twfy_capsule']->getDatabaseManager());
        });

        it('shares one connection', function () {
            expect(twfy_laravel()->make('db')->connection()->getPdo())->toBe(Capsule::connection()->getPdo());
        });

        it('keeps the Eloquent models on it', function () {
            twfy_laravel();

            expect(Model::getConnectionResolver())->toBe($GLOBALS['twfy_capsule']->getDatabaseManager());
        });
    });

    it('leaves the legacy error handler alone', function () {
        twfy_laravel();
        $handler = set_error_handler(null);
        set_error_handler($handler);

        expect(is_array($handler) && $handler[0] instanceof HandleExceptions)->toBeFalse();
    });

    it('adds no global class aliases', function () {
        twfy_laravel();

        expect(AliasLoader::getInstance()->getAliases())->toBe([]);
    });
});
