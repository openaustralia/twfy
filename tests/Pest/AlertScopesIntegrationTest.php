<?php

/**
 * @file
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Support/AlertFixtures.php';

use Illuminate\Database\Eloquent\Builder;
use OpenAustralia\TWFY\Models\Alert as AlertModel;

uses(TransactionalTestCase::class);

/**
 * Narrows a query to the rows these specs created.
 *
 * @param \Illuminate\Database\Eloquent\Builder<AlertModel> $query
 *   The query to narrow.
 *
 * @return list<string>
 *   The alerts' addresses, sorted.
 */
function emails_in(Builder $query): array {
    $emails = $query->where('email', 'like', '%@example.invalid')->pluck('email')->all();
    sort($emails);
    return $emails;
}

describe('Alert::active()', function () {
    it('keeps confirmed alerts that are not deleted', function () {
        AlertFixtures::alert('live@example.invalid', 'budget');
        AlertFixtures::alert('gone@example.invalid', 'budget', true, true);
        AlertFixtures::alert('new@example.invalid', 'budget', false);

        expect(emails_in(AlertModel::active()))->toBe(['live@example.invalid']);
    });
});

describe('Alert::deletedAlerts()', function () {
    it('keeps deleted alerts, confirmed or not', function () {
        AlertFixtures::alert('gone@example.invalid', 'budget', true, true);
        AlertFixtures::alert('goneunconfirmed@example.invalid', 'budget', false, true);
        AlertFixtures::alert('live@example.invalid', 'budget');

        expect(emails_in(AlertModel::deletedAlerts()))->toBe(['gone@example.invalid', 'goneunconfirmed@example.invalid']);
    });
});

describe('Alert::unconfirmedAlerts()', function () {
    it('keeps unconfirmed alerts, deleted or not', function () {
        AlertFixtures::alert('new@example.invalid', 'budget', false);
        AlertFixtures::alert('newgone@example.invalid', 'budget', false, true);
        AlertFixtures::alert('live@example.invalid', 'budget');

        expect(emails_in(AlertModel::unconfirmedAlerts()))->toBe(['new@example.invalid', 'newgone@example.invalid']);
    });
});

describe('Alert::speakerAlerts()', function () {
    beforeEach(function () {
        AlertFixtures::alert('speaker@example.invalid', 'speaker:10001');
        AlertFixtures::alert('both@example.invalid', 'speaker:10001 budget');
        AlertFixtures::alert('keyword@example.invalid', 'budget');
        AlertFixtures::alert('midway@example.invalid', 'budget speaker:10001');
    });

    it('keeps criteria that start with speaker:', function () {
        expect(emails_in(AlertModel::speakerAlerts()))->toBe(['both@example.invalid', 'speaker@example.invalid']);
    });
});

describe('Alert::keywordAlerts()', function () {
    beforeEach(function () {
        AlertFixtures::alert('speaker@example.invalid', 'speaker:10001');
        AlertFixtures::alert('both@example.invalid', 'speaker:10001 budget');
        AlertFixtures::alert('keyword@example.invalid', 'budget');
        AlertFixtures::alert('midway@example.invalid', 'budget speaker:10001');
    });

    it('drops criteria that mention a speaker anywhere', function () {
        expect(emails_in(AlertModel::keywordAlerts()))->toBe(['keyword@example.invalid']);
    });
});

describe('Alert::countedByCriteria()', function () {
    beforeEach(function () {
        AlertFixtures::alert('one@example.invalid', 'budget');
        AlertFixtures::alert('two@example.invalid', 'budget');
        AlertFixtures::alert('three@example.invalid', 'tax');
    });

    /**
     * The counts for these specs' criteria, most shared first.
     *
     * @return array<string, int>
     *   Alert counts keyed by criteria.
     */
    function counts_for_specs(): array {
        return AlertModel::where('email', 'like', '%@example.invalid')
          ->countedByCriteria()
          ->pluck('c', 'criteria')
          ->map(fn ($count) => (int) $count)
          ->all();
    }

    it('counts the alerts sharing each criteria', function () {
        expect(counts_for_specs())->toBe(['budget' => 2, 'tax' => 1]);
    });
});
