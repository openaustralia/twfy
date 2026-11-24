<?php

/**
 * @file
 * The recent searches view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$hostile = '"><script>alert(1)</script>';

describe('admin.searchlogs', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->view = $this->view('admin.searchlogs', ['searches' => [['url' => '/', 'query' => $hostile, 'page' => 1, 'hits' => 2, 'host' => $hostile, 'ago' => '1 hour']]]);
    });

    it('escapes the host and query', function () use ($hostile) {
        $this->view->assertSee($hostile);
    });

    it('draws no script tag', function () {
        $this->view->assertDontSee('<script>', false);
    });
});
