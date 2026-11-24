<?php

/**
 * @file
 * The admin home view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$hostile = '"><script>alert(1)</script>';

describe('admin.index', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->view = $this->view('admin.index', [
            'confirmed' => 1,
            'unconfirmed' => 2,
            'activeDay' => 3,
            'activeWeek' => 4,
            'recent' => [['name' => $hostile, 'url' => '/user/?u=1', 'email' => $hostile . '@example.invalid', 'confirmed' => 'Yes', 'registered' => '2026-01-01']],
        ]);
    });

    it('escapes the name', function () use ($hostile) {
        $this->view->assertSee($hostile);
    });

    it('draws no script tag', function () {
        $this->view->assertDontSee('<script>', false);
    });

    it('escapes the email link', function () {
        $this->view->assertSee('href="mailto:&quot;&gt;&lt;script&gt;', false);
    });

    it('shows the figures', function () {
        $this->view->assertSee('Unconfirmed users: 2');
    });
});
