<?php

/**
 * @file
 * Reading the usage stats settings. Uses made-up setting names and
 * documentation IP addresses.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\UsageStats\Settings;

const USAGE_SETTINGS_SPEC_CONSTANT = 'from-conf';

afterEach(function () {
    putenv('USAGE_SETTINGS_SPEC_CONSTANT');
    putenv('USAGE_SETTINGS_SPEC_UNSET');
    putenv('DATA_DOWNLOADS_INTERNAL_IPS');
});

describe('Settings::get()', function () {
    describe('with an environment variable', function () {
        it('uses it over conf/general', function () {
            putenv('USAGE_SETTINGS_SPEC_CONSTANT=from-env');

            expect(Settings::get('USAGE_SETTINGS_SPEC_CONSTANT'))->toBe('from-env');
        });
    });

    describe('with an empty environment variable', function () {
        it('uses conf/general', function () {
            putenv('USAGE_SETTINGS_SPEC_CONSTANT=');

            expect(Settings::get('USAGE_SETTINGS_SPEC_CONSTANT'))->toBe('from-conf');
        });
    });

    describe('without either', function () {
        it('returns an empty string', function () {
            expect(Settings::get('USAGE_SETTINGS_SPEC_UNSET'))->toBe('');
        });
    });
});

describe('Settings::internalAddresses()', function () {
    it('splits and trims the list', function () {
        putenv('DATA_DOWNLOADS_INTERNAL_IPS=192.0.2.1, 192.0.2.2 ,,');

        expect(Settings::internalAddresses())->toBe(['192.0.2.1', '192.0.2.2']);
    });
});
