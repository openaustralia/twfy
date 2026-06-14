<?php

/**
 * @file
 * Unit tests for MEMBER::current_member().
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MEMBER::current_member().
 */
class MemberCurrentMemberTest extends TestCase {

    /**
     * Create MEMBER without constructor side effects.
     */
    private function makeMemberWithoutConstructor(): MEMBER {
        $reflection = new \ReflectionClass(MEMBER::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Should return current/non-current status for all houses.
     */
    public function test_current_member_returns_status_for_all_houses(): void {
        $member = $this->makeMemberWithoutConstructor();
        $member->left_house = [
            1 => ['date' => '9999-12-31'],
            2 => ['date' => '2020-01-01'],
        ];

        $this->assertSame([
            0 => false,
            1 => true,
            2 => false,
            3 => false,
            4 => false,
        ], $member->current_member());
    }

    /**
     * Should return current/non-current status for a requested house.
     */
    public function test_current_member_returns_status_for_specific_house(): void {
        $member = $this->makeMemberWithoutConstructor();
        $member->left_house = [
            1 => ['date' => '9999-12-31'],
            2 => ['date' => '2019-05-01'],
        ];

        $this->assertTrue($member->current_member(1));
        $this->assertFalse($member->current_member(2));
        $this->assertFalse($member->current_member(4));
    }

}
