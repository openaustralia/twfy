<?php

/**
 * @file
 * Unit tests for postcode.php (Australian postal code handling).
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/postcode.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for postcode canonicalization (Australian format).
 */
class PostcodeTest extends TestCase {

    /**
     * Test single digit postcode is trimmed and spaces removed.
     */
    public function test_single_digit_trimmed(): void {
        $result = canonicalise_postcode('1');
        $this->assertSame('1', $result);
    }

    /**
     * Test two digit postcode is trimmed and spaces removed.
     */
    public function test_two_digit_trimmed(): void {
        $result = canonicalise_postcode('12');
        $this->assertSame('12', $result);
    }

    /**
     * Test three digit postcode is trimmed and spaces removed.
     */
    public function test_three_digit_trimmed(): void {
        $result = canonicalise_postcode('123');
        $this->assertSame('123', $result);
    }

    /**
     * Test four digit postcode remains unchanged.
     */
    public function test_four_digit_unchanged(): void {
        $result = canonicalise_postcode('2000');
        $this->assertSame('2000', $result);
    }

    /**
     * Test Darwin postcode (800).
     */
    public function test_darwin_postcode(): void {
        $result = canonicalise_postcode('800');
        $this->assertSame('800', $result);
    }

    /**
     * Test Melbourne postcode (3000).
     */
    public function test_melbourne_postcode(): void {
        $result = canonicalise_postcode('3000');
        $this->assertSame('3000', $result);
    }

    /**
     * Test Brisbane postcode (4000).
     */
    public function test_brisbane_postcode(): void {
        $result = canonicalise_postcode('4000');
        $this->assertSame('4000', $result);
    }

    /**
     * Test Perth postcode (6000).
     */
    public function test_perth_postcode(): void {
        $result = canonicalise_postcode('6000');
        $this->assertSame('6000', $result);
    }

    /**
     * Test Adelaide postcode (5000).
     */
    public function test_adelaide_postcode(): void {
        $result = canonicalise_postcode('5000');
        $this->assertSame('5000', $result);
    }

    /**
     * Test Hobart postcode (7000).
     */
    public function test_hobart_postcode(): void {
        $result = canonicalise_postcode('7000');
        $this->assertSame('7000', $result);
    }

    /**
     * Test whitespace is trimmed.
     */
    public function test_whitespace_trimmed(): void {
        $result = canonicalise_postcode('  2000  ');
        $this->assertSame('2000', $result);
    }

}
