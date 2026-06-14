<?php

/**
 * @file
 * Unit tests for pc.php gadget script.
 */

require_once INCLUDESPATH . 'easyparliament/member.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for pc.php gadget functionality.
 */
class PcGadgetTest extends TestCase {

    /**
     * Test normalise_constituency_name is called (function exists).
     */
    public function test_normalise_constituency_name_exists(): void {
        $this->assertTrue(function_exists('normalise_constituency_name'));
    }

}
