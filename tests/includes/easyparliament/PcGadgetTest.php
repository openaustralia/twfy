<?php

/**
 * @file
 * Unit tests for pc.php gadget script.
 */

namespace includes\easyparliament;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../www/includes/easyparliament/member.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for pc.php gadget functionality.
 */
class PcGadgetTest extends TestCase
{

    /**
     * Test normalise_constituency_name is called (function exists).
     */
    public function test_normalise_constituency_name_exists(): void
    {
        $this->assertTrue(function_exists('normalise_constituency_name'));
    }

}
