<?php

/**
 * @file
 * The admin search log pages, from the controller to the HTML.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';

require_once __DIR__ . '/../../../../www/includes/utility.php';

use OpenAustralia\TWFY\Admin\SearchLogsController;

uses(AdminControllerTestCase::class);

beforeEach(function () {
    $hostile = '"><script>alert(1)</script>';
    $GLOBALS['SEARCHLOG'] = new class($hostile) {

        public function __construct(private string $query) {
        }

        public function admin_recent_searches(int $count): array {
            return [['url' => '/search/?s=x&amp;pop=1', 'query' => $this->query, 'page_number' => 1, 'count_hits' => 4, 'ip_address' => '127.0.0.1', 'query_time' => date('Y-m-d H:i:s', time() - 3600)]];
        }

        public function admin_popular_searches(int $count): array {
            return [['url' => '/search/?s=x&amp;pop=1', 'query' => $this->query, 'c' => 7]];
        }

        public function admin_failed_searches(): array {
            return [['url' => '/search/?s=x&amp;pop=1', 'query' => $this->query, 'count_ips' => 1, 'group_count' => 2, 'max_time' => date('Y-m-d H:i:s', time() - 3600), 'min_time' => date('Y-m-d H:i:s', time() - 86400)]];
        }

    };
});

describe('SearchLogsController::recent()', function () {
    beforeEach(function () {
        $this->html = $this->render(fn () => (new SearchLogsController())->recent());
    });

    it('encodes the link once', function () {
        expect($this->html)->toContain('href="/search/?s=x&amp;pop=1"');
    });

    it('draws the page around the table', function () {
        expect($this->html)->toStartWith('[page_start][stripe_start]');
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('shows how long ago the search was', function () {
        expect($this->html)->toContain('1 hour');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_searchlogs');
    });
});

describe('SearchLogsController::popular()', function () {
    beforeEach(function () {
        $this->html = $this->render(fn () => (new SearchLogsController())->popular());
    });

    it('encodes the link once', function () {
        expect($this->html)->toContain('href="/search/?s=x&amp;pop=1"');
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('shows the count', function () {
        expect($this->html)->toContain('>7</td>');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_popularsearches');
    });
});

describe('SearchLogsController::failed()', function () {
    beforeEach(function () {
        $this->html = $this->render(fn () => (new SearchLogsController())->failed());
    });

    it('encodes the link once', function () {
        expect($this->html)->toContain('href="/search/?s=x&amp;pop=1"');
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('shows how long ago the search first failed', function () {
        expect($this->html)->toContain('1 day');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_failedsearches');
    });
});
