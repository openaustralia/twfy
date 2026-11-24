<?php

/**
 * @file
 * The alerts view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$hostile = '"><script>alert(1)</script>';

describe('admin.alerts', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->view = $this->view('admin.alerts', [
            'totals' => [['Total', 1]],
            'lists' => ['Active alerts' => [['who' => $hostile, 'url' => null, 'description' => "containing the word '" . $hostile . "'", 'created' => '']]],
        ]);
    });

    it('draws no script tag', function () {
        $this->view->assertDontSee('<script>', false);
    });

    it('escapes the email', function () use ($hostile) {
        $this->view->assertSee($hostile);
    });

    it('escapes the described criteria', function () use ($hostile) {
        $this->view->assertSee("the word '" . $hostile . "'");
    });
});
