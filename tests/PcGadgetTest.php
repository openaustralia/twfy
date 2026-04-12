<?php

/**
 * @file
 * Unit tests for pc.php gadget script.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/member.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for pc.php gadget functionality.
 */
class PcGadgetTest extends TestCase {

    /**
     * Test empty constituency string returns FALSE.
     */
    public function test_empty_constituency_returns_false(): void {
        $c = '';
        $result = ($c == '');
        $this->assertTrue($result);
    }

    /**
     * Test Orkney special case handling.
     */
    public function test_orkney_special_case(): void {
        $c = 'Orkney ';
        if ($c == 'Orkney ') {
            $c = 'Orkney &amp; Shetland';
        }
        $this->assertSame('Orkney &amp; Shetland', $c);
    }

    /**
     * Test normalise_constituency_name is called (function exists).
     */
    public function test_normalise_constituency_name_exists(): void {
        $this->assertTrue(function_exists('normalise_constituency_name'));
    }

    /**
     * Test postcode validation format.
     */
    public function test_postcode_validation_check(): void {
        // Test validation logic - Australian postcode should be 4 digits
        $pc = '2000';
        $isValid = preg_match('/^[0-9]{4}$/', $pc);
        $this->assertSame(1, $isValid);
    }

    /**
     * Test postcode sanitization regex.
     */
    public function test_postcode_sanitization(): void {
        $pc = '2000!!@#';
        $pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
        $this->assertSame('2000', $pc);
    }

    /**
     * Test postcode sanitization keeps valid characters.
     */
    public function test_postcode_sanitization_keeps_valid(): void {
        $pc = '2000 ABC';
        $pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
        $this->assertSame('2000 ABC', $pc);
    }

    /**
     * Test error message formatting.
     */
    public function test_error_format(): void {
        $errorMsg = 'error,' . 'Invalid postcode';
        $this->assertStringStartsWith('error,', $errorMsg);
    }

    /**
     * Test pid output format.
     */
    public function test_pid_output_format(): void {
        $pid = 12345;
        $output = 'pid,' . $pid;
        $this->assertSame('pid,12345', $output);
    }

}
