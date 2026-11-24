<?php

/**
 * @file
 * The statistics view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$hostile = '"><script>alert(1)</script>';

describe('admin.statistics', function () {
    it('shows the speech count with separators', function () {
        $this->view('admin.statistics', ['from' => '2000-01-01', 'to' => '2026-01-01', 'sittingDays' => 10, 'items' => 12345, 'speeches' => 9876, 'speechesPerDay' => 988])
          ->assertSee('9,876', false);
    });
});
