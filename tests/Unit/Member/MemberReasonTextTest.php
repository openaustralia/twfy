<?php

/**
 * @file
 * Unit tests for MEMBER::left_reason_text() and MEMBER::entered_reason_text().
 *
 * These methods only read from $this->reasons and require no database.
 */

require_once __DIR__ . '/../../bootstrap.php';

use OpenAustralia\TWFY\Models\Member as MemberModel;

class MemberReasonTextTest extends \PHPUnit\Framework\TestCase {

    private function makeMemberWithoutConstructor(): MEMBER {
        $reflection = new \ReflectionClass(MEMBER::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    // =========================================================================
    // left_reason_text()

    public function test_left_reason_text_returns_mapped_string_for_known_reason(): void {
        $member = $this->makeMemberWithoutConstructor();
        $this->assertSame('Died', $member->left_reason_text('died'));
    }

    public function test_left_reason_text_returns_past_tense_for_array_reason(): void {
        // 'general_election_standing' maps to ['Federal election (standing again)', 'Federal election (stood again)']
        // Index [1] is the past-tense form always returned now.
        $member = $this->makeMemberWithoutConstructor();
        $this->assertSame('Federal election (stood again)', $member->left_reason_text('general_election_standing'));
    }

    public function test_left_reason_text_returns_raw_value_for_unknown_reason(): void {
        $member = $this->makeMemberWithoutConstructor();
        $this->assertSame('some_unknown_reason', $member->left_reason_text('some_unknown_reason'));
    }

    public function test_left_reason_text_returns_still_in_office_string(): void {
        $member = $this->makeMemberWithoutConstructor();
        $this->assertSame('Still in office', $member->left_reason_text('still_in_office'));
    }

    // =========================================================================
    // entered_reason_text()

    public function test_entered_reason_text_returns_mapped_string_for_known_reason(): void {
        $member = $this->makeMemberWithoutConstructor();
        $this->assertSame('Federal election', $member->entered_reason_text('general_election'));
    }

    public function test_entered_reason_text_returns_raw_value_for_unknown_reason(): void {
        $member = $this->makeMemberWithoutConstructor();
        $this->assertSame('some_unknown_reason', $member->entered_reason_text('some_unknown_reason'));
    }

    public function test_entered_reason_text_returns_byelection_string(): void {
        $member = $this->makeMemberWithoutConstructor();
        $this->assertSame('Byelection', $member->entered_reason_text('by_election'));
    }

}
