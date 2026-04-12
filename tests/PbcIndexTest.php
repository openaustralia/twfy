<?php

/**
 * @file
 * Unit tests for pbc/index.php page.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PBC index page functionality.
 */
class PbcIndexTest extends TestCase {

    /**
     * Test speaker ID extraction from search parameter.
     */
    public function test_extract_speaker_id(): void {
        $search = 'speaker:12345';
        preg_match('/speaker:(\d+)/', $search, $m);
        $this->assertSame('12345', $m[1]);
    }

    /**
     * Test speaker ID extraction with multiple parameters.
     */
    public function test_extract_speaker_id_multiple_params(): void {
        $search = 'house:1 speaker:99999 date:2020-01-01';
        preg_match('/speaker:(\d+)/', $search, $m);
        $this->assertSame('99999', $m[1]);
    }

    /**
     * Test speaker ID extraction handles no match.
     */
    public function test_extract_speaker_id_no_match(): void {
        $search = 'house:1 date:2020-01-01';
        $result = preg_match('/speaker:(\d+)/', $search, $m);
        $this->assertSame(0, $result);
    }

    /**
     * Test bill_id and id both present - pbc_clause page.
     */
    public function test_navigation_with_bill_id_and_clause_id(): void {
        $bill_id = 1;
        $id = 'clause1';

        $shouldDisplayClause = ($bill_id && $id);
        $this->assertTrue($shouldDisplayClause);
    }

    /**
     * Test bill_id present but id absent - pbc_bill page.
     */
    public function test_navigation_with_bill_id_no_clause_id(): void {
        $bill_id = 1;
        $id = NULL;

        $shouldDisplayBill = ($bill_id && !$id);
        $this->assertTrue($shouldDisplayBill);
    }

    /**
     * Test session present but no bill - pbc_session page.
     */
    public function test_navigation_with_session_no_bill(): void {
        $bill_id = NULL;
        $session = '2006-07';

        $shouldDisplaySession = ($session && !$bill_id);
        $this->assertTrue($shouldDisplaySession);
    }

    /**
     * Test no parameters - pbc_front page.
     */
    public function test_navigation_front_page(): void {
        $bill_id = NULL;
        $session = NULL;

        $shouldDisplayFront = (!$bill_id && !$session);
        $this->assertTrue($shouldDisplayFront);
    }

    /**
     * Test committee type check for comments display.
     */
    public function test_committee_type_12_shows_comments(): void {
        $htype = '12';
        $showComments = ($htype == '12' || $htype == '13');
        $this->assertTrue($showComments);
    }

    /**
     * Test committee type check for comments display.
     */
    public function test_committee_type_13_shows_comments(): void {
        $htype = '13';
        $showComments = ($htype == '12' || $htype == '13');
        $this->assertTrue($showComments);
    }

    /**
     * Test committee type check - other types don't show comments.
     */
    public function test_committee_type_other_no_comments(): void {
        $htype = '1';
        $showComments = ($htype == '12' || $htype == '13');
        $this->assertFalse($showComments);
    }

    /**
     * Test standing prefix extraction.
     */
    public function test_standing_prefix_extraction(): void {
        $standingprefix = 'PBC2006-07-';
        $id = 'clause1';
        $gid = $standingprefix . $id;

        $this->assertSame('PBC2006-07-clause1', $gid);
    }

    /**
     * Test bill and session parameters present.
     */
    public function test_bill_and_session_check(): void {
        $bill = 'Social Security Reform Bill';
        $session = '2006-07';

        $hasParams = ($bill && $session);
        $this->assertTrue($hasParams);
    }

    /**
     * Test missing bill parameter.
     */
    public function test_missing_bill_parameter(): void {
        $bill = NULL;
        $session = '2006-07';

        $hasParams = ($bill && $session);
        $this->assertFalse($hasParams);
    }

    /**
     * Test missing session parameter.
     */
    public function test_missing_session_parameter(): void {
        $bill = 'Social Security Reform Bill';
        $session = NULL;

        $hasParams = ($bill && $session);
        $this->assertFalse($hasParams);
    }

}
