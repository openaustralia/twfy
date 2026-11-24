<?php

/**
 * @file
 * The admin report controllers' queries, against the database.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/laravel.php';

use OpenAustralia\TWFY\Admin\HomeController;
use OpenAustralia\TWFY\Admin\StatisticsController;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * Stands in for the legacy PAGE, which would turn away a visitor who is not an admin.
 */
function stand_in_page(): void {
    $GLOBALS['PAGE'] = new class() {

        public function __call(string $name, array $args): string {
            return '';
        }

    };
}

describe('HomeController::index()', function () {
    beforeEach(function () {
        stand_in_page();
        User::create(['firstname' => '<script>alert(1)</script>', 'lastname' => 'X', 'email' => 'xss@example.invalid', 'confirmed' => 1, 'registrationtime' => '2099-01-01 00:00:00']);
        ob_start();
        (new HomeController())->index();
        $this->html = ob_get_clean();
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
});

describe('StatisticsController::show()', function () {
    it('draws the page', function () {
        stand_in_page();
        ob_start();
        (new StatisticsController())->show();
        $html = ob_get_clean();

        expect($html)->toContain('Hansard data in database');
    });
});
