<?php

/**
 * @file
 * The admin reports' Blade views escape what they show, and so draw nothing a visitor could script.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../www/includes/laravel.php';

use OpenAustralia\TWFY\Admin\SearchLogsController;

$hostile = '"><script>alert(1)</script>';

function admin_view(string $name, array $data): string {
    return twfy_laravel()->make('view')->make($name, $data)->render();
}

describe('admin.popularsearches', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->html = admin_view('admin.popularsearches', ['searches' => [['url' => '/search/?s=' . $hostile, 'query' => $hostile, 'count' => 3]]]);
    });

    it('escapes the query', function () {
        expect($this->html)->toContain('&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;</a>');
    });

    it('escapes the link', function () {
        expect($this->html)->toContain('href="/search/?s=&quot;&gt;&lt;script&gt;');
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('shows the count', function () {
        expect($this->html)->toContain('>3</td>');
    });
});

describe('admin.searchlogs', function () use ($hostile) {
    it('escapes the host', function () use ($hostile) {
        $html = admin_view('admin.searchlogs', ['searches' => [['url' => '/', 'query' => 'x', 'page' => 1, 'hits' => 2, 'host' => $hostile, 'ago' => '1 hour']]]);

        expect($html)->not->toContain('<script>');
    });
});

describe('admin.failedsearches', function () use ($hostile) {
    it('escapes the query', function () use ($hostile) {
        $html = admin_view('admin.failedsearches', ['searches' => [['url' => '/', 'query' => $hostile, 'ips' => 1, 'times' => 2, 'latest' => '1 hour', 'first' => '2 days']]]);

        expect($html)->not->toContain('<script>');
    });
});

describe('admin.index', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->html = admin_view('admin.index', [
            'confirmed' => 1,
'unconfirmed' => 2,
'activeDay' => 3,
'activeWeek' => 4,
            'recent' => [['name' => $hostile, 'url' => '/user/?u=1', 'email' => $hostile . '@example.invalid', 'confirmed' => 'Yes', 'registered' => '2026-01-01']],
        ]);
    });

    it('escapes the name', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('escapes the email link', function () {
        expect($this->html)->toContain('href="mailto:&quot;&gt;&lt;script&gt;');
    });

    it('shows the figures', function () {
        expect($this->html)->toContain('Unconfirmed users: 2');
    });
});

describe('admin.alerts', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $this->html = admin_view('admin.alerts', [
            'totals' => [['Total', 1]],
            'lists' => ['Active alerts' => [['who' => $hostile, 'url' => null, 'description' => 'containing the word \'' . $hostile . '\'', 'created' => '']]],
        ]);
    });

    it('escapes the email', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('escapes the described criteria', function () {
        expect($this->html)->toContain('the word &#039;&quot;&gt;&lt;script&gt;');
    });
});

describe('admin.alert_stats', function () use ($hostile) {
    it('escapes criteria and names', function () use ($hostile) {
        $html = admin_view('admin.alert_stats', [
            'separate' => [[1, $hostile]],
            'speakers' => [[$hostile, 1, 0]],
            'keywords' => [[$hostile, 2, 0]],
        ]);

        expect($html)->not->toContain('<script>');
    });
});

describe('admin.statistics', function () {
    it('shows the speech count with separators', function () {
        $html = admin_view('admin.statistics', ['from' => '2000-01-01', 'to' => '2026-01-01', 'sittingDays' => 10, 'items' => 12345, 'speeches' => 9876, 'speechesPerDay' => 988]);

        expect($html)->toContain('<b>9,876</b> debate speeches (12,345 items');
    });
});

describe('SearchLogsController::popular()', function () use ($hostile) {
    beforeEach(function () use ($hostile) {
        $GLOBALS['PAGE'] = new class() {

            public array $calls = [];

            public function __call(string $name, array $args): string {
                $this->calls[] = $name;
                echo "[$name]";
                return '';
            }

        };
        $GLOBALS['SEARCHLOG'] = new class($hostile) {

            public function __construct(private string $query) {
            }

            public function admin_popular_searches(int $count): array {
                return [['url' => '/search/?s=x', 'query' => $this->query, 'c' => 7]];
            }

        };
        ob_start();
        (new SearchLogsController())->popular();
        $this->html = ob_get_clean();
    });

    it('draws the page around the table', function () {
        expect($this->html)->toStartWith('[page_start][stripe_start]');
    });

    it('escapes the query in the page', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_popularsearches');
    });
});
