<?php

/**
 * @file
 * The admin statistics page, from the controller to the HTML.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';

use OpenAustralia\TWFY\Admin\StatisticsController;
use OpenAustralia\TWFY\Models\Hansard;

if (!defined('SHORTDATEFORMAT')) {
    define('SHORTDATEFORMAT', 'j M Y');
}

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

describe('StatisticsController::show() with debates', function () {
    beforeEach(function () {
        foreach ([[960001, 10, '2005-05-11'], [960002, 12, '2005-05-11'], [960003, 12, '2005-05-11'], [960004, 12, '2005-05-12']] as [$id, $type, $date]) {
            Hansard::create(['epobject_id' => $id, 'gid' => "uk.org.publicwhip/debate/$date.$id.1", 'htype' => $type, 'major' => 1, 'hdate' => $date]);
        }
        $this->html = $this->render(fn () => (new StatisticsController())->show());
    });

    it('formats the first date as the site does', function () {
        expect($this->html)->toContain('<b>11 May 2005</b>');
    });

    it('formats the last date as the site does', function () {
        expect($this->html)->toContain('<b>12 May 2005</b>');
    });

    it('counts the sitting days', function () {
        expect($this->html)->toContain('<b>2</b> of those days');
    });

    it('counts the speeches and every item', function () {
        expect($this->html)->toContain('<b>3</b> debate speeches (4 items');
    });

    it('averages the speeches over the sitting days', function () {
        expect($this->html)->toContain('<b>2</b> speeches');
    });
});
