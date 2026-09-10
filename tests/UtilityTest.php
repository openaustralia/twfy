<?php

/**
 * @file
 */

use League\Plates\Engine;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/utility.php';

/**
 *
 */
class UtilityTest extends TestCase {

    /**
     *
     */
    public function test_get_plates_engine_returns_a_plates_engine() {
        $this->assertInstanceOf(Engine::class, get_plates_engine());
    }

    /**
     * page.php's footer render and a page-specific template (hansard_gid.php,
     * www/docs/index.php, ...) both call this in the same request - sharing one
     * instance is the whole point, not just an implementation detail.
     */
    public function test_get_plates_engine_returns_the_same_instance_on_repeat_calls() {
        $this->assertSame(get_plates_engine(), get_plates_engine());
    }

}
