<?php

/**
 * @file
 * The debate lists' queries, from the database to the rows a template gets. Uses fictional gids and dates.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../www/includes/utility.php';
require_once __DIR__ . '/../../../../www/includes/data.php';
require_once __DIR__ . '/../../../../www/includes/easyparliament/hansardlist.php';

use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;

// The list class builds each row's link with the legacy URL class, which other specs also stand in for.
if (!class_exists('URL')) {

/**
 *
 */
    class URL {

        public function __construct(private string $page) {
        }

        /**
         * @param array<string, string> $params
         *   Query values.
         */
        public function insert(array $params): void {
        }

        public function generate(string $type = ''): string {
            return '/' . $this->page . '/';
        }

    }
}

uses(TransactionalTestCase::class);

/**
 * A heading and the speeches under it, on a day in the House of Representatives.
 */
function seed_debate_day(string $date, int $first_id, bool $legacy = false): void {
    $rows = [
        [$first_id, 10, 'Budget', 0, 0, 1],
        [$first_id + 1, 11, 'Tax', $first_id, 0, 2],
        [$first_id + 2, 12, 'We should cut it.', $first_id, $first_id + 1, 3],
        [$first_id + 3, 12, 'We should not.', $first_id, $first_id + 1, 4],
    ];
    foreach ($rows as [$id, $type, $body, $section, $subsection, $position]) {
        if ($legacy) {
            // The legacy list code reads through its own connection, which only sees rows written through it.
            $db = getSharedTestConnection();
            $db->query("INSERT INTO epobject (epobject_id, body) VALUES ($id, '$body')");
            $db->query("INSERT INTO hansard (epobject_id, gid, htype, major, hdate, hpos, section_id, subsection_id) VALUES ($id, 'uk.org.publicwhip/debate/$date.$first_id.$position', $type, 1, '$date', $position, $section, $subsection)");
            continue;
        }
        Epobject::create(['epobject_id' => $id, 'body' => $body]);
        Hansard::create([
            'epobject_id' => $id,
            'gid' => "uk.org.publicwhip/debate/$date.$first_id.$position",
            'htype' => $type,
            'major' => 1,
            'hdate' => $date,
            'hpos' => $position,
            'section_id' => $section,
            'subsection_id' => $subsection,
        ]);
    }
}

describe('DEBATELIST::_get_data_by_column()', function () {
    beforeEach(function () {
        global $hansardmajors, $DATA;
        include __DIR__ . '/../../../../www/includes/dbtypes.php';
        $DATA = new DATA();
        seed_debate_day('2026-03-02', 930010);
        $this->data = (new DEBATELIST())->_get_data_by_column(['date' => '2026-03-02', 'column' => '930010']);
    });

    it('finds the rows in the column', function () {
        expect(array_column($this->data, 'body'))->toBe(['Budget', 'Tax', 'We should cut it.', 'We should not.']);
    });

    it('leaves out other days', function () {
        $other = (new DEBATELIST())->_get_data_by_column(['date' => '2026-03-03', 'column' => '930010']);

        expect($other)->toBe([]);
    });
});

describe('DEBATELIST::_get_data_by_biggest_debates()', function () {
    beforeEach(function () {
        global $hansardmajors, $DATA;
        include __DIR__ . '/../../../../www/includes/dbtypes.php';
        $DATA = new DATA();
        seed_debate_day('2026-03-02', 930020, true);
        $this->data = (new DEBATELIST())->_get_data_by_biggest_debates(['num' => 1]);
    });

    it('counts the speeches in the debate', function () {
        expect($this->data['data'][0]['contentcount'])->toBe('2');
    });

    it('names the section and the debate', function () {
        expect($this->data['data'][0]['body'])->toBe('Budget | Tax');
    });
});
