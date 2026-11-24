<?php

/**
 * @file
 * The admin proof report pages, from rows in the database to the HTML. Uses fictional gids and dates.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';

use OpenAustralia\TWFY\Admin\ProofController;
use OpenAustralia\TWFY\ImportErrors;
use OpenAustralia\TWFY\Models\Hansard;

uses(AdminControllerTestCase::class);

/**
 * Creates one hansard row for a day, house and proof value.
 */
function proof_page_row(int $id, string $date, int $major, ?bool $proof): void {
    Hansard::create([
        'epobject_id' => $id,
        'gid' => "uk.org.publicwhip/debate/$date.$id.1",
        'major' => $major,
        'hdate' => $date,
        'proof' => $proof,
        'modified' => '2026-03-02 10:00:00',
    ]);
}

/**
 * The HTML of a proof page for some query parameters.
 *
 * @param string $page
 *   The controller action: overview, year or errors.
 * @param array<string, string> $query
 *   The query string.
 */
function proof_page(object $test, string $page, array $query = []): string {
    $_GET = $query;
    return $test->render(fn () => (new ProofController())->$page());
}

describe('ProofController::overview()', function () {
    beforeEach(function () {
        proof_page_row(970001, '2026-03-01', 1, true);
        $this->html = proof_page($this, 'overview');
    });

    it('draws the page around the bars', function () {
        expect($this->html)->toStartWith('[page_start][stripe_start]');
    });

    it('links a year that has days', function () {
        expect($this->html)->toContain('href="/admin/proof_year.php?year=2026"');
    });

    it('greys out a year with no days', function () {
        expect($this->html)->toContain('<span class="w-14 text-sm text-slate-400">2010</span>');
    });

    it('lists years back to 2007', function () {
        expect($this->html)->toContain('>2007</span>');
    });

    it('shows a year\'s days with errors', function () {
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');

        expect(proof_page($this, 'overview'))->toContain('1 day with errors');
    });

    it('shows where you are', function () {
        expect($this->html)->toContain('aria-current="page">Import status');
    });

    it('sets the page key for the menu', function () {
        expect($GLOBALS['this_page'])->toBe('admin_proof');
    });
});

describe('ProofController::year()', function () {
    beforeEach(function () {
        proof_page_row(980001, '2026-03-01', 1, true);
        proof_page_row(980002, '2026-03-02', 1, false);
        proof_page_row(980003, '2026-03-03', 101, null);
        proof_page_row(980004, '2026-04-01', 1, false);
        proof_page_row(980005, '2026-05-01', 1, true);
        proof_page_row(980006, '2026-05-01', 1, false);
        $this->newest = max(2026, (int) date('Y'));
    });

    it('lists every month', function () {
        expect(proof_page($this, 'year', ['year' => '2026']))->toContain('>December</span>');
    });

    it('links a month that has days', function () {
        expect(proof_page($this, 'year', ['year' => '2026']))->toContain('year=2026&amp;month=3#days');
    });

    it('greys out a month with no days', function () {
        expect(proof_page($this, 'year', ['year' => '2026']))->toContain('<span class="text-slate-400">July</span>');
    });

    it('shows no days until a month is chosen', function () {
        expect(proof_page($this, 'year', ['year' => '2026']))->not->toContain('id="days"');
    });

    it('ignores a month outside 1 to 12', function () {
        expect(proof_page($this, 'year', ['year' => '2026', 'month' => '13']))->not->toContain('id="days"');
    });

    it('falls back to the newest year', function () {
        expect(proof_page($this, 'year', ['year' => '1999']))->toContain('aria-current="page">' . $this->newest);
    });

    it('links the newer year on the left', function () {
        expect(proof_page($this, 'year', ['year' => '2020']))->toContain('&larr; 2021');
    });

    it('links the older year on the right', function () {
        expect(proof_page($this, 'year', ['year' => '2020']))->toContain('2019 &rarr;');
    });

    it('has no newer link for the newest year', function () {
        expect(proof_page($this, 'year', ['year' => (string) $this->newest]))->not->toContain('&larr;');
    });

    it('marks the chosen month', function () {
        expect(proof_page($this, 'year', ['year' => '2026', 'month' => '3']))->toContain('aria-current="true"');
    });

    it('lists the days not yet known to be final', function () {
        expect(proof_page($this, 'year', ['year' => '2026', 'month' => '3']))->toContain('?d=2026-03-01');
    });

    it('lists the final days too', function () {
        expect(proof_page($this, 'year', ['year' => '2026', 'month' => '3']))->toContain('?d=2026-03-02');
    });

    it('links a Senate day to the Senate', function () {
        expect(proof_page($this, 'year', ['year' => '2026', 'month' => '3']))->toContain('/senate/?d=2026-03-03');
    });

    it('shows a day of mixed rows as mixed', function () {
        expect(proof_page($this, 'year', ['year' => '2026', 'month' => '5']))->toContain('bg-fuchsia-100');
    });

    it('has a column for days with errors', function () {
        expect(proof_page($this, 'year', ['year' => '2026']))->toContain('Days with errors');
    });

    it('says when a month has no days', function () {
        expect(proof_page($this, 'year', ['year' => '2026', 'month' => '7']))->toContain('We hold no sitting days in this month.');
    });

    it('shows a month\'s days with errors in red', function () {
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');

        expect(proof_page($this, 'year', ['year' => '2026']))->toContain('font-semibold text-rose-700">1</td>');
    });
});

describe('ProofController::year() days with errors', function () {
    beforeEach(function () {
        proof_page_row(995001, '2026-03-01', 1, true);
        proof_page_row(995002, '2026-03-02', 1, false);
        ImportErrors::record('2026-03-01', 1, 'loader', 'Bad time');
        ImportErrors::record('2026-03-01', 1, 'parser', 'Bad entity');
        ImportErrors::record('2026-03-10', 101, 'parser', 'Failed before loading');
        $this->html = proof_page($this, 'year', ['year' => '2026', 'month' => '3']);
    });

    it('links a day to its errors', function () {
        expect($this->html)->toContain('href="/admin/proof_errors.php?date=2026-03-01&amp;house=representatives"');
    });

    it('counts a day\'s errors in the link', function () {
        expect($this->html)->toContain('2 errors</a>');
    });

    it('says one error in the singular', function () {
        expect($this->html)->toContain('1 error</a>');
    });

    it('tints a row with errors pale red', function () {
        expect($this->html)->toContain('bg-rose-50 hover:bg-rose-100');
    });

    it('leaves a row without errors striped', function () {
        expect($this->html)->toContain('even:bg-slate-50 hover:bg-teal-50');
    });

    it('lists a day with errors and no rows', function () {
        expect($this->html)->toContain('house=senate');
    });

    it('has no second errors column in the months table', function () {
        expect(proof_page($this, 'year', ['year' => '2026']))->not->toContain('Errors</th>');
    });
});

describe('ProofController::errors()', function () {
    beforeEach(function () {
        ImportErrors::record('2022-03-29', 1, 'loader', 'Incorrect <b>time</b> value');
        $this->html = proof_page($this, 'errors', ['date' => '2022-03-29', 'house' => 'representatives']);
    });

    it('names the day and house', function () {
        expect($this->html)->toContain('Import errors for 2022-03-29, Representatives');
    });

    it('shows when it was recorded with the time zone', function () {
        $zone = date_default_timezone_get();
        date_default_timezone_set('Australia/Sydney');
        $html = proof_page($this, 'errors', ['date' => '2022-03-29', 'house' => 'representatives']);
        date_default_timezone_set($zone);

        expect($html)->toMatch('/\d{2}:\d{2}:\d{2} (AEDT|AEST)\s*<\/td>/');
    });

    it('shows the message escaped', function () {
        expect($this->html)->toContain('Incorrect &lt;b&gt;time&lt;/b&gt; value');
    });

    it('says who found it', function () {
        expect($this->html)->toContain('Loader');
    });

    it('links back to the month', function () {
        expect($this->html)->toContain('href="/admin/proof_year.php?year=2022&amp;month=3#days"');
    });

    it('links the day on Parliament House', function () {
        expect($this->html)->toContain('Dataset%3Ahansardr,hansardr80%20Date%3A29%2F3%2F2022;');
    });

    it('links the original XML', function () {
        expect($this->html)->toContain('href="https://data.openaustralia.org.au/origxml/representatives_debates/2022-03-29.xml"');
    });

    it('links the parsed XML', function () {
        expect($this->html)->toContain('href="https://data.openaustralia.org.au/scrapedxml/representatives_debates/2022-03-29.xml"');
    });

    it('links a Senate day to the Senate files', function () {
        expect(proof_page($this, 'errors', ['date' => '2022-03-29', 'house' => 'senate']))->toContain('/origxml/senate_debates/2022-03-29.xml');
    });

    it('says when a day has no errors', function () {
        expect(proof_page($this, 'errors', ['date' => '2022-03-30', 'house' => 'senate']))->toContain('No errors are recorded for this day.');
    });

    it('turns away a date that is not a day', function () {
        expect(proof_page($this, 'errors', ['date' => '2022-02-30', 'house' => 'senate']))->toContain('That is not a day and house');
    });

    it('turns away an unknown house', function () {
        expect(proof_page($this, 'errors', ['date' => '2022-03-29', 'house' => 'lords']))->toContain('That is not a day and house');
    });

    it('draws nothing from text in place of a date', function () {
        expect(proof_page($this, 'errors', ['date' => '<script>', 'house' => 'senate']))->not->toContain('<script>');
    });
});
