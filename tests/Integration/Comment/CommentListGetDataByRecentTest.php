<?php

/**
 * @file
 * Integration tests for COMMENTLIST::_get_data_by_recent().
 */

use OpenAustralia\TWFY\Models\Comments;
use OpenAustralia\TWFY\Models\Epobject;
use OpenAustralia\TWFY\Models\Hansard;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\User;

if (!defined('ALLOWCOMMENTS')) {
    define('ALLOWCOMMENTS', false);
}

include_once EASYPARLIAMENTPATH . 'commentlist.php';

/**
 * Tests for COMMENTLIST::_get_data_by_recent() ORM conversion.
 */
class CommentListGetDataByRecentTest extends TransactionalTestCase {

    private int $epobjectId = 80001;

    private function insertMember(int $member_id, int $person_id, string $first, string $last): void {
        Member::create([
            'member_id' => $member_id,
            'house' => 1,
            'first_name' => $first,
            'last_name' => $last,
            'constituency' => 'Test Seat',
            'party' => 'ALP',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => $person_id,
        ]);
    }

    private function insertUser(int $user_id, string $firstname, string $lastname): void {
        User::query()->insert([
            'user_id' => $user_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => "test{$user_id}@example.com",
            'password' => 'hashed',
            'confirmed' => 1,
            'registrationtime' => '2024-01-01 00:00:00',
        ]);
    }

    private function insertComment(int $user_id, int $epobject_id, string $body, string $posted): void {
        Comments::query()->insert([
            'user_id' => $user_id,
            'epobject_id' => $epobject_id,
            'body' => $body,
            'posted' => $posted,
            'visible' => 1,
        ]);
    }

    private function insertEpobjectAndHansard(int $epobject_id, int $speaker_id): void {
        Epobject::query()->insert([
            'epobject_id' => $epobject_id,
            'body' => "Hansard body for {$epobject_id}",
            'type' => 1,
        ]);
        Hansard::create([
            'epobject_id' => $epobject_id,
            'gid' => "uk.org.publicwhip/debate/2024-01-01.{$epobject_id}.0",
            'htype' => 12,
            'speaker_id' => $speaker_id,
            'major' => 1,
            'section_id' => 1,
            'subsection_id' => 1,
            'hpos' => 0,
            'hdate' => '2024-01-01',
            'source_url' => 'https://example.com',
        ]);
    }

    private function makeCOMMENTLIST(): COMMENTLIST {
        $GLOBALS['this_page'] = 'debate';
        return new COMMENTLIST();
    }

    public function test_returns_total_results_without_pid(): void {
        $this->insertUser(80001, 'Alice', 'A');
        $this->insertMember(80001, 80001, 'Speaker', 'One');
        $this->insertEpobjectAndHansard(80001, 80001);
        $this->insertComment(80001, 80001, 'First comment', '2024-06-01 10:00:00');
        $this->insertEpobjectAndHansard(80002, 80001);
        $this->insertComment(80001, 80002, 'Second comment', '2024-06-02 10:00:00');

        $cl = $this->makeCOMMENTLIST();
        $data = $cl->_get_data_by_recent(['num' => 10]);

        $this->assertSame(2, $data['total_results']);
    }

    public function test_returns_total_results_with_pid(): void {
        $this->insertUser(80002, 'Bob', 'B');
        $this->insertMember(80002, 80002, 'Member', 'Two');
        $this->insertMember(80003, 80003, 'Member', 'Three');

        // Comments on speeches by person 80002.
        $this->insertEpobjectAndHansard(80003, 80002);
        $this->insertComment(80002, 80003, 'Comment on person 80002', '2024-06-03 10:00:00');

        // Comments on speeches by person 80003 (should not be counted)
        $this->insertEpobjectAndHansard(80004, 80003);
        $this->insertComment(80002, 80004, 'Comment on person 80003', '2024-06-04 10:00:00');

        $cl = $this->makeCOMMENTLIST();
        $data = $cl->_get_data_by_recent(['num' => 10, 'pid' => 80002]);

        $this->assertSame(1, $data['total_results']);
        $this->assertSame(80002, (int) $data['pid']);
        $this->assertArrayHasKey('full_name', $data);
    }

    public function test_defaults_to_25_results(): void {
        $cl = $this->makeCOMMENTLIST();
        $data = $cl->_get_data_by_recent([]);

        $this->assertSame(25, $data['results_per_page']);
        $this->assertSame(1, $data['page']);
    }

    public function test_respects_num_and_page_args(): void {
        $cl = $this->makeCOMMENTLIST();
        $data = $cl->_get_data_by_recent(['num' => 5, 'page' => 3]);

        $this->assertSame(5, $data['results_per_page']);
        $this->assertSame(3, $data['page']);
    }

    public function test_excludes_invisible_comments_from_count(): void {
        $this->insertUser(80003, 'Carol', 'C');
        $this->insertMember(80004, 80004, 'Speaker', 'Four');
        $this->insertEpobjectAndHansard(80005, 80004);

        // Visible comment.
        $this->insertComment(80003, 80005, 'Visible', '2024-06-05 10:00:00');

        // Invisible comment.
        Comments::query()->insert([
            'user_id' => 80003,
            'epobject_id' => 80005,
            'body' => 'Hidden',
            'posted' => '2024-06-06 10:00:00',
            'visible' => 0,
        ]);

        $cl = $this->makeCOMMENTLIST();
        $data = $cl->_get_data_by_recent(['num' => 10]);

        $this->assertSame(1, $data['total_results']);
    }

}
