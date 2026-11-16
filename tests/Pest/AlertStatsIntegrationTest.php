<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use OpenAustralia\TWFY\AlertStats;
use OpenAustralia\TWFY\Models\Alert as AlertModel;
use OpenAustralia\TWFY\Models\User as UserModel;

uses(TransactionalTestCase::class);

/**
 * Names for the people the stats specs follow. Person 10003 has no name.
 */
function stats_name_for(int $person_id): ?string {
    return [10001 => 'Alex Example', 10002 => 'Sam Sample', 10004 => 'Kim Keyword'][$person_id] ?? null;
}

describe('AlertStats::probablySeparateSignups()', function () {
    beforeEach(function () {
        AlertFixtures::alert('a@example.invalid', 'budget,tax');
        AlertFixtures::alert('b@example.invalid', 'budget');
        AlertFixtures::alert('c@example.invalid', 'speaker:10001,budget');
        AlertFixtures::alert('d@example.invalid', 'roads,rail', false);
        AlertFixtures::alert('e@example.invalid', 'roads,rail', true, true);
    });

    it('lists active keyword alerts with commas', function () {
        $criteria = AlertStats::probablySeparateSignups()->pluck('criteria')->all();

        expect($criteria)->toBe(['budget,tax']);
    });

    it('returns the alert id', function () {
        expect(AlertStats::probablySeparateSignups()->first()->alert_id)->toBeInt();
    });
});

describe('AlertStats::bySpeaker()', function () {
    beforeEach(function () {
        AlertFixtures::alert('a@example.invalid', 'speaker:10001');
        AlertFixtures::alert('b@example.invalid', 'speaker:10001');
        AlertFixtures::alert('c@example.invalid', 'speaker:10001 budget');
        AlertFixtures::alert('d@example.invalid', 'speaker:10001', false);
        AlertFixtures::alert('e@example.invalid', 'speaker:10002');
        AlertFixtures::alert('f@example.invalid', 'speaker:10003');
        AlertFixtures::alert('g@example.invalid', 'speaker:10004', false);
        AlertFixtures::alert('h@example.invalid', 'speaker:10001', true, true);
        AlertFixtures::alert('i@example.invalid', 'speaker:');
        $this->rows = AlertStats::bySpeaker('stats_name_for');
    });

    it('lists the most followed person first', function () {
        expect(array_column($this->rows, 'name'))->toBe(['Alex Example', 'Sam Sample']);
    });

    it('carries the person id', function () {
        expect($this->rows[0]['person_id'])->toBe(10001);
    });

    it('counts active alerts, adding variants', function () {
        expect($this->rows[0]['confirmed'])->toBe(3);
    });

    it('counts unconfirmed alerts', function () {
        expect($this->rows[0]['unconfirmed'])->toBe(1);
    });

    it('counts none unconfirmed for a person with none', function () {
        expect($this->rows[1]['unconfirmed'])->toBe(0);
    });

    it('skips people the lookup does not know', function () {
        expect(array_column($this->rows, 'person_id'))->not->toContain(10003);
    });

    it('skips people with no active alerts', function () {
        expect(array_column($this->rows, 'person_id'))->not->toContain(10004);
    });
});

describe('AlertStats::byKeyword()', function () {
    beforeEach(function () {
        AlertFixtures::alert('a@example.invalid', 'budget');
        AlertFixtures::alert('b@example.invalid', 'budget');
        AlertFixtures::alert('c@example.invalid', 'budget', false);
        AlertFixtures::alert('d@example.invalid', 'tax', false);
        AlertFixtures::alert('e@example.invalid', 'tax', false);
        AlertFixtures::alert('f@example.invalid', 'lonely');
        AlertFixtures::alert('g@example.invalid', 'speaker:10001');
        AlertFixtures::alert('h@example.invalid', '2026');
        AlertFixtures::alert('i@example.invalid', '2026');
        AlertFixtures::alert('j@example.invalid', '2026');
        $this->rows = AlertStats::byKeyword();
    });

    it('lists keywords shared by active alerts first', function () {
        expect(array_column($this->rows, 'criteria'))->toBe(['2026', 'budget', 'tax']);
    });

    it('counts active and unconfirmed alerts', function () {
        expect($this->rows[1])->toBe(['criteria' => 'budget', 'confirmed' => 2, 'unconfirmed' => 0]);
    });

    it('gives a keyword only unconfirmed alerts share', function () {
        expect($this->rows[2])->toBe(['criteria' => 'tax', 'confirmed' => 0, 'unconfirmed' => 2]);
    });

    it('keeps a numeric keyword as a string', function () {
        expect($this->rows[0]['criteria'])->toBe('2026');
    });

    it('leaves out keywords one alert has', function () {
        expect(array_column($this->rows, 'criteria'))->not->toContain('lonely');
    });
});

describe('AlertStats::totals()', function () {

    /**
     * The counts of each state, keyed by label.
     *
     * @return array<string, int>
     *   Counts keyed by label.
     */
    function totals_by_label(): array {
        return array_column(AlertStats::totals(), 1, 0);
    }

    it('labels each state', function () {
        expect(array_keys(totals_by_label()))->toBe(['Total', 'Active', 'Deleted', 'Unconfirmed']);
    });

    describe('after adding one alert of each kind', function () {
        beforeEach(function () {
            $this->before = totals_by_label();
            AlertFixtures::alert('live@example.invalid', 'budget');
            AlertFixtures::alert('gone@example.invalid', 'budget', true, true);
            AlertFixtures::alert('new@example.invalid', 'budget', false);
            $this->after = totals_by_label();
        });

        it('counts every alert in the total', function () {
            expect($this->after['Total'] - $this->before['Total'])->toBe(3);
        });

        it('counts the active alert', function () {
            expect($this->after['Active'] - $this->before['Active'])->toBe(1);
        });

        it('counts the deleted alert', function () {
            expect($this->after['Deleted'] - $this->before['Deleted'])->toBe(1);
        });

        it('counts the unconfirmed alert', function () {
            expect($this->after['Unconfirmed'] - $this->before['Unconfirmed'])->toBe(1);
        });
    });
});

describe('AlertStats::listRows()', function () {

    /**
     * Rows for the alerts these specs created.
     *
     * @param string $order
     *   How to order the rows.
     *
     * @return list<array{string, string, string}>
     *   The rows.
     */
    function list_rows_for_specs(string $order = 'email'): array {
        return AlertStats::listRows(
            AlertModel::active()->where('email', 'like', '%@example.invalid'),
            $order,
            fn (string $criteria): string => "about $criteria",
            fn (int $user_id): string => "/user/$user_id"
        );
    }

    beforeEach(function () {
        AlertFixtures::alert('zed@example.invalid', 'budget');
        AlertFixtures::alert('amy@example.invalid', 'tax');
        AlertFixtures::alert('gone@example.invalid', 'tax', true, true);
    });

    it('leaves out alerts the query leaves out', function () {
        expect(count(list_rows_for_specs()))->toBe(2);
    });

    it('sorts by email by default', function () {
        expect(array_column(list_rows_for_specs(), 0))->toBe(['amy@example.invalid', 'zed@example.invalid']);
    });

    it('describes the criteria', function () {
        expect(array_column(list_rows_for_specs(), 1))->toBe(['about tax', 'about budget']);
    });

    it('treats an unknown order as email', function () {
        expect(array_column(list_rows_for_specs('email; DROP TABLE alerts'), 0))->toBe(['amy@example.invalid', 'zed@example.invalid']);
    });

    describe('when ordered by created', function () {
        it('sorts oldest first', function () {
            AlertModel::where('email', 'zed@example.invalid')->update(['created' => '2020-01-01 00:00:00']);

            expect(array_column(list_rows_for_specs('created'), 0)[0])->toBe('zed@example.invalid');
        });
    });

    describe('when the alert has no creation time', function () {
        it('shows a space', function () {
            AlertModel::where('email', 'zed@example.invalid')->update(['created' => '0000-01-01 00:00:00']);

            expect(array_column(list_rows_for_specs(), 2)[1])->toBe('&nbsp;');
        });
    });

    describe('when the email has a user', function () {
        beforeEach(function () {
            $this->user = UserModel::create(['firstname' => 'Zoe', 'lastname' => 'Example', 'email' => 'ZED@example.invalid']);
        });

        it('links the name to the user page', function () {
            expect(array_column(list_rows_for_specs(), 0)[1])->toBe('<a href="/user/' . $this->user->user_id . '">Zoe Example</a>');
        });
    });
});

describe('AlertStats::htmlTable()', function () {
    it('wraps rows in a table', function () {
        expect(AlertStats::htmlTable([], [['a', 1]]))->toBe('<table><tr><td>a</td><td>1</td></tr></table>');
    });

    it('adds a heading row', function () {
        expect(AlertStats::htmlTable(['Name'], []))->toBe('<table><tr><th>Name</th></tr></table>');
    });

    it('escapes cells', function () {
        expect(AlertStats::htmlTable([], [['<script>']]))->toContain('&lt;script&gt;');
    });

    it('escapes headings', function () {
        expect(AlertStats::htmlTable(['<b>'], []))->toContain('&lt;b&gt;');
    });
});

describe('AlertStats row helpers', function () {
    beforeEach(function () {
        AlertFixtures::alert('a@example.invalid', 'budget,tax');
        AlertFixtures::alert('b@example.invalid', 'speaker:10001');
        AlertFixtures::alert('c@example.invalid', 'speaker:10001');
        AlertFixtures::alert('d@example.invalid', '2026');
        AlertFixtures::alert('e@example.invalid', '2026');
    });

    it('lists separate signups as id and criteria', function () {
        expect(array_column(AlertStats::probablySeparateSignupRows(), 1))->toContain('budget,tax');
    });

    it('lists speakers as name and counts', function () {
        expect(array_slice(AlertStats::speakerRows('stats_name_for'), 0, 1))->toBe([['Alex Example', 2, 0]]);
    });

    it('lists keywords as criteria and counts', function () {
        expect(array_slice(AlertStats::keywordRows(), 0, 1))->toBe([['2026', 2, 0]]);
    });
});
