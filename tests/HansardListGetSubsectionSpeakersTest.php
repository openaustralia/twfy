<?php

/**
 * @file
 * Integration tests for HANSARDLIST::_get_subsection_speakers() - the query
 * behind resources/views/hansard/section-index.php's "who's in this one" line
 * (see hansardlist.php's own docblock on the method).
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/hansardlist.php';

if (!class_exists('URL')) {
    /**
     * Minimal URL stub, same shape as MemberUrlTest.php's - _get_speaker()
     * (called internally) builds one via global $DATA/METADATAPATH, which
     * pulls in real page-routing config this test has no need to depend on.
     */
    class URL {

        private string $page;

        public function __construct(string $page) {
            $this->page = $page;
        }

        /**
         *
         */
        public function insert(array $params): void {
        }

        /**
         *
         */
        public function generate(string $type = ''): string {
            return '/' . $this->page . '/';
        }

    }
}

/**
 *
 */
class HansardListGetSubsectionSpeakersTest extends TransactionalTestCase {

    private static $autoId = 91500;

    /**
     * Insert a real member row so _get_speaker() (called internally) has
     * something to join against.
     */
    private function insertTestMember($member_id, $person_id, $first_name, $last_name) {
        parlDBQuery(
            'INSERT INTO member (member_id, person_id, house, title, first_name, last_name, constituency, party, entered_house, left_house, entered_reason, left_reason)
             VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $member_id,
            $person_id,
            '',
            $first_name,
            $last_name,
            'TestVille',
            'Test Party',
            '2020-01-01',
            '9999-12-31',
            'general_election',
            'still_in_office'
        );
    }

    /**
     * Insert a real hansard row (a htype=12 speech row within a subsection).
     */
    private function insertTestSpeech($subsection_id, $speaker_id, $hpos, $hdate = '2026-05-28') {
        $epobject_id = self::$autoId++;
        parlDBQuery(
            'INSERT INTO hansard (epobject_id, gid, htype, speaker_id, subsection_id, hpos, hdate)
             VALUES (?, ?, 12, ?, ?, ?, ?)',
            $epobject_id,
            'test.subsection.' . $epobject_id,
            $speaker_id,
            $subsection_id,
            $hpos,
            $hdate
        );
    }

    /**
     * The core case: two people speak within the same subsection, in a
     * particular order - the method should return both, in the order they
     * first spoke, without duplicates.
     */
    public function test_returns_distinct_speakers_in_first_spoken_order() {
        $subsectionId = 91501;
        $this->insertTestMember(91502, 91503, 'Larissa', 'Waters');
        $this->insertTestMember(91504, 91505, 'Katy', 'Gallagher');

        // Katy's row comes first in insertion order but speaks *later* within
        // the subsection (higher hpos) - the result should still be ordered
        // by hpos, not by insertion/row order.
        $this->insertTestSpeech($subsectionId, 91504, 20);
        $this->insertTestSpeech($subsectionId, 91502, 10);
        // Larissa speaks again later in the same subsection - must not be
        // returned twice.
        $this->insertTestSpeech($subsectionId, 91502, 30);

        $hansardlist = new HANSARDLIST();
        $speakers = $hansardlist->_get_subsection_speakers($subsectionId);

        $this->assertCount(2, $speakers);
        $this->assertSame('Larissa', $speakers[0]['first_name']);
        $this->assertSame('Katy', $speakers[1]['first_name']);
    }

    /**
     * A subsection with no htype=12 rows (eg an empty/heading-only one)
     * returns no speakers, not an error.
     */
    public function test_returns_empty_array_when_subsection_has_no_speeches() {
        $hansardlist = new HANSARDLIST();

        $this->assertSame([], $hansardlist->_get_subsection_speakers(91599));
    }

}
