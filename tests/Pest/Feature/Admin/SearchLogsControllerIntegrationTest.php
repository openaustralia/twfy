<?php

/**
 * @file
 * The admin search log pages, from the database to the HTML. Uses fictional searches.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';

use OpenAustralia\TWFY\Admin\SearchLogsController;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\SearchQueryLog;

uses(AdminControllerTestCase::class);

/**
 * One logged search, made an hour ago from the loopback address unless overridden.
 *
 * @param array<string, mixed> $overrides
 *   Columns to change.
 */
function logged_search(string $query, int $hits, array $overrides = []): SearchQueryLog {
    return SearchQueryLog::create($overrides + [
        'query_string' => $query,
        'page_number' => 1,
        'count_hits' => $hits,
        'ip_address' => '127.0.0.1',
        'query_time' => date('Y-m-d H:i:s', time() - 3600),
    ]);
}

describe('SearchLogsController::recent()', function () {
    beforeEach(function () {
        logged_search('"><script>alert(1)</script>', 4);
        $this->html = $this->render(fn () => (new SearchLogsController())->recent());
    });

    it('draws the page around the table', function () {
        expect($this->html)->toStartWith('[page_start][stripe_start]');
    });

    it('draws no script tag', function () {
        expect($this->html)->not->toContain('<script>');
    });

    it('encodes the link once', function () {
        expect($this->html)->toContain('href="/search/?s=%22%3E%3Cscript%3Ealert%281%29%3C%2Fscript%3E&amp;pop=1"');
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
        logged_search('budget', 3);
        logged_search('budget', 5);
        logged_search('speaker:99999', 2);
        $this->html = $this->render(fn () => (new SearchLogsController())->popular());
    });

    it('counts how often a query was made', function () {
        expect($this->html)->toContain('>2</td>');
    });

    it('encodes the link once', function () {
        expect($this->html)->toContain('href="/search/?s=budget&amp;pop=1"');
    });

    it('leaves out speaker searches', function () {
        expect($this->html)->not->toContain('speaker');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_popularsearches');
    });
});

describe('SearchLogsController::recent() for a speaker', function () {
    it('names the person', function () {
        Member::create(['member_id' => 940001, 'person_id' => 940001, 'house' => 1, 'first_name' => 'Alex', 'last_name' => 'Example']);
        logged_search('speaker:940001', 2);

        $html = $this->render(fn () => (new SearchLogsController())->recent());

        expect($html)->toContain('>Alex Example</a>');
    });
});

describe('SearchLogsController::failed()', function () {
    beforeEach(function () {
        logged_search('xyzzy', 0, ['ip_address' => '10.0.0.1']);
        logged_search('xyzzy', 0, ['ip_address' => '10.0.0.2', 'query_time' => date('Y-m-d H:i:s', time() - 86400)]);
        logged_search('found it', 3);
        $this->html = $this->render(fn () => (new SearchLogsController())->failed());
    });

    it('lists a query that found nothing', function () {
        expect($this->html)->toContain('>xyzzy</a>');
    });

    it('leaves out a query that found something', function () {
        expect($this->html)->not->toContain('found it');
    });

    it('shows how long ago it first failed', function () {
        expect($this->html)->toContain('1 day');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_failedsearches');
    });
});

describe('SearchLogsController::recent() from an address that was not kept', function () {
    it('still draws the page', function () {
        logged_search('x', 4, ['ip_address' => null]);

        $html = $this->render(fn () => (new SearchLogsController())->recent());

        expect($html)->toContain('1 hour');
    });
});

describe('SearchLogsController with a search that has no query', function () {
    beforeEach(function () {
        SearchQueryLog::create(['query_string' => null, 'page_number' => 1, 'count_hits' => 0, 'ip_address' => '127.0.0.1', 'query_time' => date('Y-m-d H:i:s', time() - 3600)]);
    });

    it('still draws the recent searches', function () {
        $html = $this->render(fn () => (new SearchLogsController())->recent());

        expect($html)->toContain('1 hour');
    });

    it('still draws the failed searches', function () {
        $html = $this->render(fn () => (new SearchLogsController())->failed());

        expect($html)->toContain('1 hour');
    });
});

describe('SearchLogsController with a search that has no time', function () {
    it('still draws the recent searches', function () {
        SearchQueryLog::create(['query_string' => 'x', 'page_number' => 1, 'count_hits' => 4, 'ip_address' => '127.0.0.1', 'query_time' => null]);

        $html = $this->render(fn () => (new SearchLogsController())->recent());

        expect($html)->toContain('>x</a>');
    });

    it('still draws the failed searches', function () {
        SearchQueryLog::create(['query_string' => 'x', 'page_number' => 1, 'count_hits' => 0, 'ip_address' => '127.0.0.1', 'query_time' => null]);

        $html = $this->render(fn () => (new SearchLogsController())->failed());

        expect($html)->toContain('>x</a>');
    });
});
