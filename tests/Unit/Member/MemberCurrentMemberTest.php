<?php

/**
 * @file
 * Unit tests for MEMBER::current_member().
 */


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
            HOUSE::REPRESENTATIVES => ['date' => '9999-12-31'],
            HOUSE::SENATE => ['date' => '2020-01-01'],
        ];

        $this->assertSame([
            HOUSE::REPRESENTATIVES => true,
            HOUSE::SENATE => false,
        ], $member->current_member());
    }

    /**
     * Should return current/non-current status for a requested house.
     */
    public function test_current_member_returns_status_for_specific_house(): void {
        $member = $this->makeMemberWithoutConstructor();
        $member->left_house = [
            HOUSE::REPRESENTATIVES => ['date' => '9999-12-31'],
            HOUSE::SENATE => ['date' => '2019-05-01'],
        ];

        $this->assertTrue($member->current_member(HOUSE::REPRESENTATIVES));
        $this->assertFalse($member->current_member(HOUSE::SENATE));
        $this->assertNull($member->current_member(4));
    }

}
