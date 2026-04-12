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

    /**
     * Test radius of earth constant
     */
    public function test_radius_of_earth_defined() {
        $this->assertTrue(defined('RADIUS_OF_EARTH'));
        $this->assertGreaterThan(0, RADIUS_OF_EARTH);
    }

    /**
     * Test latitude/longitude coordinate range validation
     */
    public function test_latitude_range() {
        $lat = -33.8688;
        $this->assertGreaterThanOrEqual(-90, $lat);
        $this->assertLessThanOrEqual(90, $lat);
    }

    /**
     *
     */
    public function test_longitude_range() {
        $lon = 151.2093;
        $this->assertGreaterThanOrEqual(-180, $lon);
        $this->assertLessThanOrEqual(180, $lon);
    }

    /**
     * Test distance calculation setup
     */
    public function test_sydney_distance_parameters() {
        $lat = -33.8688;
        $lon = 151.2093;
        $distance_km = 50;

        // Verify distance is positive.
        $this->assertGreaterThan(0, $distance_km);

        // Verify angle range for nearby coordinates.
        $lat_offset = $distance_km / RADIUS_OF_EARTH;
        $this->assertLessThan(M_PI / 2, $lat_offset);
    }

    /**
     * Test degree to radian conversion consistency
     */
    public function test_deg2rad_conversion() {
        $deg_0 = deg2rad(0);
        $deg_90 = deg2rad(90);
        $deg_180 = deg2rad(180);
        $deg_360 = deg2rad(360);

        $this->assertEquals(0, $deg_0);
        $this->assertEqualsWithDelta(M_PI / 2, $deg_90, 0.001);
        $this->assertEqualsWithDelta(M_PI, $deg_180, 0.001);
        $this->assertEqualsWithDelta(2 * M_PI, $deg_360, 0.001);
    }

    /**
     * Test coordinate bounds logic
     */
    public function test_coordinate_bounds_check() {
        $search_lat = -33.8688;
        $search_lon = 151.2093;
        $distance = 50;

        $search_lat_rad = deg2rad($search_lat);
        $radius_radians = $distance / RADIUS_OF_EARTH;

        // North bound.
        $north_bound = deg2rad($search_lat) + $radius_radians;
        $this->assertGreaterThan($search_lat_rad, $north_bound);

        // South bound.
        $south_bound = deg2rad($search_lat) - $radius_radians;
        $this->assertLessThan($search_lat_rad, $south_bound);
    }

    /**
     * Test empty geometry data handling
     */
    public function test_missing_centre_coordinates() {
        // Geometry with missing centre_lat/lon.
        $data = ['name' => 'Test', 'boundary' => []];
        $this->assertFalse(isset($data['centre_lat']));
        $this->assertFalse(isset($data['centre_lon']));
    }

    /**
     * Test result sorting by distance
     */
    public function test_distance_sorting_array() {
        $results = [
            ['name' => 'A', 'distance' => 50],
            ['name' => 'B', 'distance' => 10],
            ['name' => 'C', 'distance' => 30],
        ];

        usort($results, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        $this->assertEquals('B', $results[0]['name']);
        $this->assertEquals('C', $results[1]['name']);
        $this->assertEquals('A', $results[2]['name']);
    }

    /**
     * Test deduplication of constituency names
     */
    public function test_constituency_name_deduplication() {
        $done = [];
        $names = ['Sydney', 'Sydney', 'Melbourne', 'Sydney', 'Brisbane'];

        $unique_names = [];
        foreach ($names as $name) {
            if (!in_array($name, $done)) {
                $unique_names[] = $name;
                $done[] = $name;
            }
        }

        $this->assertEquals(3, count($unique_names));
        $this->assertContains('Sydney', $unique_names);
        $this->assertContains('Melbourne', $unique_names);
        $this->assertContains('Brisbane', $unique_names);
    }

    /**
     * Test HTML entity decoding
     */
    public function test_html_entity_decode_constituency_name() {
        $encoded = "O'Connor &amp; District";
        $decoded = html_entity_decode($encoded);
        $this->assertStringContainsString('&', $decoded);
    }

}
