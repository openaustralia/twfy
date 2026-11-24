<?php

/**
 * @file
 * The admin statistics page, from the controller to the HTML.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';

use OpenAustralia\TWFY\Admin\StatisticsController;

uses(AdminControllerTestCase::class);

describe('StatisticsController::show()', function () {
    beforeEach(function () {
        $this->html = $this->render(fn () => (new StatisticsController())->show());
    });

    it('draws the page', function () {
        expect($this->html)->toContain('Hansard data in database');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_statistics');
    });
});
