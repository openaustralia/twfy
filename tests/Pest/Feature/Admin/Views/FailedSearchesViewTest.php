<?php

/**
 * @file
 * The failed searches view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$hostile = '"><script>alert(1)</script>';

describe('admin.failedsearches', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->view = $this->view('admin.failedsearches', ['searches' => [['url' => '/', 'query' => $hostile, 'ips' => 1, 'times' => 2, 'latest' => '1 hour', 'first' => '2 days']]]);
    });

    it('escapes the query', function () use ($hostile) {
        $this->view->assertSee($hostile);
    });

    it('draws no script tag', function () {
        $this->view->assertDontSee('<script>', false);
    });
});
