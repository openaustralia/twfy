<?php

/**
 * @file
 * The admin usage page, from the database to the HTML. Uses fictional people, keys, documentation IP addresses and
 * made-up requests.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';

use OpenAustralia\TWFY\Admin\UsageController;
use OpenAustralia\TWFY\Models\ApiDailyUsage;
use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\DataDownload;
use OpenAustralia\TWFY\Models\User;

uses(AdminControllerTestCase::class);

/**
 * Creates a fictional person with a key, who made some calls today, and returns nothing.
 */
function usage_page_key(string $name, string $url): void {
    $user = User::create(['firstname' => $name, 'lastname' => '<Example>', 'email' => strtolower($name) . '@example.test', 'url' => $url]);
    $key = ApiKey::create(['user_id' => $user->user_id, 'api_key' => 'usagepage' . strtolower($name) . 'aaaaaaaaaa', 'commercial' => 0, 'created' => '2026-01-01 00:00:00', 'reason' => "A school\n project"]);
    ApiDailyUsage::insert([['api_key_id' => $key->id, 'date' => gmdate('Y-m-d'), 'count' => 1234]]);
}

describe('UsageController::index()', function () {
    beforeEach(function () {
        usage_page_key('Ada', 'https://example.test/');
        usage_page_key('Bob', 'javascript:alert(1)');
        DataDownload::create([
            'request_time' => gmdate('Y-m-d') . ' 00:00:01',
            'ip_address' => '203.0.113.5',
            'method' => 'GET',
            'path' => '/usage-page-spec.xml',
            'status' => 200,
            'bytes' => 512,
            'referrer' => '',
            'user_agent' => 'ExampleBot/1.0',
            'line_hash' => sha1(random_bytes(16)),
        ]);
        $this->html = $this->render(fn () => (new UsageController())->index());
    });

    it('draws the page around the tables', function () {
        expect($this->html)->toStartWith('[page_start][stripe_start]');
    });

    it('lists a key holder', function () {
        expect($this->html)->toContain('Ada &lt;Example&gt;');
    });

    it('links a web address', function () {
        expect($this->html)->toContain('href="https://example.test/"');
    });

    it('does not link another kind of address', function () {
        expect($this->html)->not->toContain('javascript:');
    });

    it('lists a download', function () {
        expect($this->html)->toContain('/usage-page-spec.xml');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_usage');
    });
});
