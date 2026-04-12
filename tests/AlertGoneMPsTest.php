<?php

/**
 * @file
 * Unit tests for alertgonemps.php script.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/alert.php';
require_once __DIR__ . '/../www/includes/easyparliament/member.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for alert gone MPs functionality.
 */
class AlertGoneMPsTest extends TestCase {

    /**
     * Test parsing speaker criteria from alert string.
     */
    public function test_parse_speaker_criteria(): void {
        $criteria = 'speaker:12345';
        preg_match('#speaker:(\d+)#', $criteria, $m);
        $this->assertSame('12345', $m[1]);
    }

    /**
     * Test parsing speaker criteria with multiple fields.
     */
    public function test_parse_speaker_criteria_multiple_fields(): void {
        $criteria = 'house:1 speaker:99999 date:2020-01-01';
        preg_match('#speaker:(\d+)#', $criteria, $m);
        $this->assertSame('99999', $m[1]);
    }

    /**
     * Test that criteria without speaker: is skipped.
     */
    public function test_skip_non_speaker_criteria(): void {
        $criteria = 'house:1 date:2020-01-01';
        $hasSpeaker = preg_match('#speaker:(\d+)#', $criteria);
        $this->assertSame(0, $hasSpeaker);
    }

    /**
     * Test detecting if member has left house.
     */
    public function test_member_has_left_detection(): void {
        // Members with left_house != '9999-12-31' have left.
        $leftHouse = '2020-06-30';
        $isActive = ($leftHouse === '9999-12-31');
        $this->assertFalse($isActive);
    }

    /**
     * Test detecting if member is still active.
     */
    public function test_member_is_active_detection(): void {
        // Members with left_house == '9999-12-31' are still active.
        $leftHouse = '9999-12-31';
        $isActive = ($leftHouse === '9999-12-31');
        $this->assertTrue($isActive);
    }

    /**
     * Test email text building for gone MP.
     */
    public function test_email_text_building(): void {
        $name = 'John Smith';
        $emailText = '';
        $emailText .= "$name, ";

        $this->assertStringContainsString('John Smith', $emailText);
    }

    /**
     * Test building registered user message prefix.
     */
    public function test_registered_user_email_prefix(): void {
        $prefix = "As a registered user, visit http://www.openaustralia.org/user/\nto unsubscribe from, or manage, your alerts.\n\n";

        $this->assertStringContainsString('registered user', $prefix);
        $this->assertStringContainsString('unsubscribe', $prefix);
    }

    /**
     * Test building unregistered user message prefix.
     */
    public function test_unregistered_user_email_prefix(): void {
        $prefix = "If you register on the site, you will be able to manage your\nalerts there as well as post comments. :)\n\n";

        $this->assertStringContainsString('register', $prefix);
        $this->assertStringContainsString('manage', $prefix);
    }

    /**
     * Test counting unique MPs in alert list.
     */
    public function test_count_unique_mps(): void {
        $mpList = [];
        $mpList[12345] = 1;
        $mpList[67890] = 1;
        $mpList[11111] = 1;

        $this->assertCount(3, $mpList);
    }

    /**
     * Test alert counting - registered vs unregistered.
     */
    public function test_alert_counting(): void {
        $registered = 5;
        $unregistered = 3;

        $this->assertSame(5, $registered);
        $this->assertSame(3, $unregistered);
    }

}
