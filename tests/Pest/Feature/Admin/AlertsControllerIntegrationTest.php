<?php

/**
 * @file
 * The admin alert pages, from the controller to the HTML. Uses fictional alerts.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';
require_once __DIR__ . '/../../Support/AlertFixtures.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/searchengine.php';

use OpenAustralia\TWFY\Admin\AlertsController;

uses(AdminControllerTestCase::class);

describe('AlertsController::index()', function () {
    beforeEach(function () {
        AlertFixtures::alert('visitor@example.invalid', '"><script>alert(1)</script>');
        AlertFixtures::alert('gone@example.invalid', 'budget', true, true);
        $this->html = $this->render(fn () => (new AlertsController())->index());
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('lists an active alert', function () {
        expect($this->html)->toContain('visitor@example.invalid');
    });

    it('lists a deleted alert', function () {
        expect($this->html)->toContain('gone@example.invalid');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_alerts');
    });
});

describe('AlertsController::stats()', function () {
    beforeEach(function () {
        AlertFixtures::member(10001);
        AlertFixtures::alert('a@example.invalid', 'budget,tax');
        AlertFixtures::alert('b@example.invalid', 'speaker:10001');
        $this->html = $this->render(fn () => (new AlertsController())->stats());
    });

    it('names a followed person', function () {
        expect($this->html)->toContain('Alex Example');
    });

    it('lists criteria that look like separate sign-ups', function () {
        expect($this->html)->toContain('budget,tax');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('alert_stats');
    });
});
