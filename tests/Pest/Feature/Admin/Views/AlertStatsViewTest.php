<?php

/**
 * @file
 * The alert stats view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$hostile = '"><script>alert(1)</script>';

describe('admin.alert_stats', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->view = $this->view('admin.alert_stats', [
            'separate' => [[1, $hostile]],
            'speakers' => [[$hostile, 1, 0]],
            'keywords' => [[$hostile, 2, 0]],
        ]);
    });

    it('draws no script tag', function () {
        $this->view->assertDontSee('<script>', false);
    });

    it('escapes the criteria', function () use ($hostile) {
        $this->view->assertSee($hostile);
    });
});
