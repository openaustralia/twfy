<?php

/**
 * @file
 * The admin usage view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

/**
 * What the view shows for one key, with no downloads.
 *
 * @param array<string, mixed> $key
 *   Values to change in the key's row.
 *
 * @return array<string, mixed>
 *   The data.
 */
function usage_view_data(array $key = []): array {
    return [
        'filters' => [['Last 30 days', '/admin/usage.php', true], ['Today', '/admin/usage.php?period=1', false]],
        'from' => '1999-01-01',
        'to' => '1999-01-30',
        'keys' => [$key + ['name' => 'Ada <Example>', 'website' => null, 'email' => 'ada@example.test', 'reason' => 'A school project', 'short_reason' => 'A school', 'commercial' => false, 'disabled' => '', 'total' => 12345, 'mean' => 411.5, 'min' => 3, 'max' => 2000]],
        'daily' => [],
        'paths' => [],
        'visitors' => [],
    ];
}

describe('admin.usage', function () {
    it('gives the range', function () {
        $this->view('admin.usage', usage_view_data())->assertSee('from 1999-01-01 to 1999-01-30');
    });

    it('offers the periods', function () {
        $this->view('admin.usage', usage_view_data())->assertSee('href="/admin/usage.php?period=1">Today</a>', false);
    });
});

describe('admin.usage key rows', function () {
    it('escapes the name', function () {
        $this->view('admin.usage', usage_view_data())->assertSee('Ada &lt;Example&gt;', false);
    });

    it('links the name to a web address', function () {
        $this->view('admin.usage', usage_view_data(['website' => 'https://example.test/?a=1&b=2']))->assertSee('href="https://example.test/?a=1&amp;b=2"', false);
    });

    it('leaves the name plain without a web address', function () {
        $this->view('admin.usage', usage_view_data())->assertDontSee('<a class="!text-teal-700 !no-underline hover:!underline" href="https', false);
    });

    it('says when there is no account', function () {
        $this->view('admin.usage', usage_view_data(['name' => null]))->assertSee('<em>No account</em>', false);
    });

    it('formats the figures', function () {
        $this->view('admin.usage', usage_view_data())->assertSee('>12,345</td>', false);
    });

    it('shows the mean to one place', function () {
        $this->view('admin.usage', usage_view_data())->assertSee('>411.5</td>', false);
    });

    it('marks commercial keys', function () {
        $this->view('admin.usage', usage_view_data(['commercial' => true]))->assertSee('>Yes</td>', false);
    });

    it('shows the whole reason on hover', function () {
        $this->view('admin.usage', usage_view_data())->assertSee('<span title="A school project">A school</span>', false);
    });
});

describe('admin.usage without any usage', function () {
    it('says there are no keys', function () {
        $this->view('admin.usage', ['keys' => []] + usage_view_data())->assertSee('No API calls with a known key in this period.');
    });
});

describe('admin.usage downloads', function () {
    beforeEach(function () {
        $this->view = $this->view('admin.usage', ['daily' => [['day' => '1999-01-10', 'downloads' => 1500, 'internal' => 2]], 'paths' => [['path' => '/a&b.xml', 'downloads' => 1000]], 'visitors' => [['ip_address' => '203.0.113.5', 'user_agent' => '<Bot>', 'downloads' => 7, 'internal' => true]]] + usage_view_data());
    });

    it('formats a day', function () {
        $this->view->assertSee('>1,500</td>', false);
    });

    it('escapes a path', function () {
        $this->view->assertSee('/a&amp;b.xml', false);
    });

    it('escapes a user agent', function () {
        $this->view->assertSee('&lt;Bot&gt;', false);
    });

    it('marks a visitor from OAF', function () {
        $this->view->assertSee('>Yes</td>', false);
    });
});
