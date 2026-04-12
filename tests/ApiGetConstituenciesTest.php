<?php

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ApiGetConstituenciesTest extends TestCase {

    /**
     * Test angle calculation for nearby constituencies
     */
    public function test_angle_between_zero() {
        $result = _api_angle_between(0, 0);
        $this->assertEquals(0, $result);
    }

    /**
     *
     */
    public function test_angle_between_small_angle() {
        $result = _api_angle_between(0.1, 0.3);
        $this->assertEqualsWithDelta(0.2, $result, 0.001);
    }

    /**
     *
     */
    public function test_angle_between_wraps_around_pi() {
        // Angles on opposite sides of the circle should wrap.
// ~2π - 0.283.
        $result = _api_angle_between(0.1, 6.0);
        $this->assertLessThan(M_PI / 2, $result);
    }

    /**
     *
     */
    public function test_angle_between_pi() {
        // Opposite sides of circle.
        $result = _api_angle_between(0, M_PI);
        $this->assertEquals(M_PI, $result);
    }

}
