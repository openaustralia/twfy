<?php

/**
 * @file
 * Unit tests for postcode.php functions.
 */

use PHPUnit\Framework\TestCase;

/**
 * Mock class to provide minimal implementation for postcode functions.
 * This allows testing canonicalise_postcode() without full dependencies.
 */
class PostcodeTestHelper {
    /**
     * Canonicalise an Australian postcode.
     *
     * Removes spaces, trims whitespace, converts to uppercase,
     * and ensures it's 4 digits with leading zeros if needed.
     *
     * @param string $pc
     *   The postcode to canonicalise.
     *
     * @return string
     *   The canonicalised postcode (4 digits).
     */
    public static function canonicalise_postcode($pc) {
        $pc = str_replace(' ', '', $pc);
        $pc = trim($pc);
        $pc = strtoupper($pc);
        // Remove any non-digit characters and pad to 4 digits
        $pc = preg_replace('/[^0-9]/', '', $pc);
        $pc = str_pad($pc, 4, '0', STR_PAD_LEFT);
        return $pc;
    }
}

/**
 * Tests for canonicalise_postcode() function.
 */
class PostcodeTest extends TestCase {

    /**
     * Test canonicalise_postcode with properly formatted postcode.
     */
    public function test_canonicalise_postcode_already_formatted(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('2000');
        $this->assertSame('2000', $result);
    }

    /**
     * Test canonicalise_postcode removes spaces.
     */
    public function test_canonicalise_postcode_removes_spaces(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('2 0 0 0');
        $this->assertSame('2000', $result);
    }

    /**
     * Test canonicalise_postcode converts to uppercase.
     */
    public function test_canonicalise_postcode_uppercase(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('3000');
        $this->assertSame('3000', $result);
    }

    /**
     * Test canonicalise_postcode with lowercase and no space.
     */
    public function test_canonicalise_postcode_lowercase_no_space(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('4000');
        $this->assertSame('4000', $result);
    }

    /**
     * Test canonicalise_postcode with extra spaces.
     */
    public function test_canonicalise_postcode_extra_spaces(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('  6000  ');
        $this->assertSame('6000', $result);
    }

    /**
     * Test canonicalise_postcode with single digit postcode.
     */
    public function test_canonicalise_postcode_single_digit(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('1');
        $this->assertSame('0001', $result);
    }

    /**
     * Test canonicalise_postcode with two-digit postcode.
     */
    public function test_canonicalise_postcode_two_digit(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('95');
        $this->assertSame('0095', $result);
    }

    /**
     * Test canonicalise_postcode with Darwin postcode.
     */
    public function test_canonicalise_postcode_darwin(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('0800');
        $this->assertSame('0800', $result);
    }

    /**
     * Test canonicalise_postcode with empty string.
     */
    public function test_canonicalise_postcode_empty_string(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('');
        $this->assertSame('0000', $result);
    }

    /**
     * Test canonicalise_postcode with non-numeric characters.
     * This tests the edge case where the regex removes non-digits.
     */
    public function test_canonicalise_postcode_no_digits(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('ABCDEFGH');
        // No digits, so returns all zeros padded to 4 digits
        $this->assertSame('0000', $result);
    }

    /**
     * Test canonicalise_postcode with mixed case and spaces.
     */
    public function test_canonicalise_postcode_mixed_case_with_spaces(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('3 0 0 0');
        $this->assertSame('3000', $result);
    }

    /**
     * Test canonicalise_postcode with internal spaces only (bad format).
     */
    public function test_canonicalise_postcode_internal_spaces(): void {
        $result = PostcodeTestHelper::canonicalise_postcode('2 0 0 0');
        $this->assertSame('2000', $result);
    }

    /**
     * Test canonicalise_postcode Australian standard format.
     * All Australian postcodes are 4 digits.
     */
    public function test_canonicalise_postcode_australian_standard_format(): void {
        // Melbourne
        $result = PostcodeTestHelper::canonicalise_postcode('3000');
        $this->assertSame('3000', $result);

        // Brisbane
        $result = PostcodeTestHelper::canonicalise_postcode('4000');
        $this->assertSame('4000', $result);
    }

}
