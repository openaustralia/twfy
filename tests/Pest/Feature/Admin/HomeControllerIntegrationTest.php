<?php

/**
 * @file
 * The admin home page, from the controller to the HTML.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';

use OpenAustralia\TWFY\Admin\HomeController;
use OpenAustralia\TWFY\Models\User;

uses(AdminControllerTestCase::class);

describe('HomeController::index()', function () {
    beforeEach(function () {
        User::create(['firstname' => '<script>alert(1)</script>', 'lastname' => 'X', 'email' => 'xss@example.invalid', 'confirmed' => 1, 'registrationtime' => '2099-01-01 00:00:00']);
        $this->html = $this->render(fn () => (new HomeController())->index());
    });

    it('draws the page around the stats', function () {
        expect($this->html)->toStartWith('[page_start][stripe_start]');
    });

    it('escapes a user\'s name', function () {
        expect($this->html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt; X');
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('links a confirmed user to their page', function () {
        expect($this->html)->toContain('href="/user/?u=');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_home');
    });
});
