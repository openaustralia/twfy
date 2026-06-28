<?php

/**
 * @file
 * Unit tests for MEMBER::entered_house_text() and MEMBER::left_house_text().
 */

use PHPUnit\Framework\TestCase;

class MemberHouseDateTextTest extends TestCase {

    private function makeMemberWithoutConstructor(): MEMBER {
        $reflection = new \ReflectionClass(MEMBER::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    public function test_entered_house_text_accepts_datetime_interface_for_senate_jan_first(): void {
        $member = $this->makeMemberWithoutConstructor();
        $member->houses = [HOUSE::SENATE];

        $text = $member->entered_house_text(new DateTimeImmutable('2024-01-01'));

        $this->assertSame('2024', $text);
    }

    public function test_entered_house_text_accepts_datetime_interface_for_non_jan_first(): void {
        $member = $this->makeMemberWithoutConstructor();
        $member->houses = [HOUSE::REPRESENTATIVES];

        $text = $member->entered_house_text(new DateTimeImmutable('2024-02-03'));

        $this->assertSame(format_date('2024-02-03', LONGDATEFORMAT), $text);
    }

    public function test_left_house_text_accepts_datetime_interface(): void {
        $member = $this->makeMemberWithoutConstructor();

        $text = $member->left_house_text(new DateTimeImmutable('2024-03-04'));

        $this->assertSame(format_date('2024-03-04', LONGDATEFORMAT), $text);
    }
}
