<?php

/**
 * @file
 * The popular searches view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$hostile = '"><script>alert(1)</script>';

describe('admin.popularsearches', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->view = $this->view('admin.popularsearches', ['searches' => [['url' => '/search/?s=' . $hostile, 'query' => $hostile, 'count' => 3]]]);
    });

    it('escapes the query', function () use ($hostile) {
        $this->view->assertSee($hostile);
    });

    it('escapes the link', function () {
        $this->view->assertSee('href="/search/?s=&quot;&gt;&lt;script&gt;', false);
    });

    it('draws no script tag', function () {
        $this->view->assertDontSee('<script>', false);
    });

    it('shows the count', function () {
        $this->view->assertSee('>3</td>', false);
    });
});
