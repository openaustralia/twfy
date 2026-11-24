<?php

/**
 * @file
 * The admin data site downloads view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

/**
 * What the view shows for one day, one file and one visitor.
 *
 * @param array<string, mixed> $overrides
 *   Data to change.
 *
 * @return array<string, mixed>
 *   The data.
 */
function downloads_view_data(array $overrides = []): array {
    return $overrides + [
        'filters' => [['Last 30 days', '/admin/downloads.php', true], ['Today', '/admin/downloads.php?period=1', false]],
        'from' => '1999-01-01',
        'to' => '1999-01-30',
        'daily' => [['day' => '1999-01-10', 'downloads' => 1500, 'internal' => 2]],
        'paths' => [['path' => '/a&b.xml', 'downloads' => 1000]],
        'visitors' => [['ip_address' => '203.0.113.5', 'user_agent' => '<Bot>', 'downloads' => 7, 'internal' => true]],
    ];
}

describe('admin.downloads', function () {
    it('gives the range', function () {
        $this->view('admin.downloads', downloads_view_data())->assertSee('from 1999-01-01 to 1999-01-30');
    });

    it('offers the periods', function () {
        $this->view('admin.downloads', downloads_view_data())->assertSee('href="/admin/downloads.php?period=1">Today</a>', false);
    });

    it('formats a day', function () {
        $this->view('admin.downloads', downloads_view_data())->assertSee('>1,500</td>', false);
    });

    it('escapes a path', function () {
        $this->view('admin.downloads', downloads_view_data())->assertSee('/a&amp;b.xml', false);
    });

    it('escapes a user agent', function () {
        $this->view('admin.downloads', downloads_view_data())->assertSee('&lt;Bot&gt;', false);
    });

    it('marks a visitor from OAF', function () {
        $this->view('admin.downloads', downloads_view_data())->assertSee('>Yes</td>', false);
    });
});

describe('admin.downloads without any downloads', function () {
    it('says there are none', function () {
        $this->view('admin.downloads', ['daily' => [], 'paths' => [], 'visitors' => []] + downloads_view_data())->assertSee('No downloads imported for this period.');
    });
});
