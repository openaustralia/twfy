<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use OpenAustralia\TWFY\AlertStats;

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
