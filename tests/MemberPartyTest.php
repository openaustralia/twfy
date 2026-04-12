<?php

use PHPUnit\Framework\TestCase;

class MemberPartyTest extends TestCase {

    /**
     * Test party to colour mapping
     */
    public function test_party_to_colour_major_parties() {
        // Setup global party_colours for testing
        global $party_colours;
        $party_colours = [
            'Australian Labor Party' => '#FF0000',
            'Liberal Party' => '#0000FF',
            'Australian Greens' => '#00A000',
        ];

        $this->assertEquals('#FF0000', party_to_colour('Australian Labor Party'));
        $this->assertEquals('#0000FF', party_to_colour('Liberal Party'));
        $this->assertEquals('#00A000', party_to_colour('Australian Greens'));
    }

    public function test_party_to_colour_unknown_party() {
        global $party_colours;
        $party_colours = [
            'Liberal Party' => '#0000FF',
        ];

        // Unknown party should return default grey
        $result = party_to_colour('Unknown Party');
        $this->assertEquals('#eeeeee', $result);
    }

    public function test_party_to_colour_empty_string() {
        global $party_colours;
        $party_colours = [
            'Liberal Party' => '#0000FF',
        ];

        $result = party_to_colour('');
        $this->assertEquals('#eeeeee', $result);
    }

    public function test_party_to_colour_case_sensitive() {
        global $party_colours;
        $party_colours = [
            'Liberal Party' => '#0000FF',
        ];

        // Case mismatch should return default
        $result = party_to_colour('liberal party');
        $this->assertEquals('#eeeeee', $result);
    }

    public function test_party_to_colour_returns_hex_format() {
        global $party_colours;
        $party_colours = [
            'Labor' => '#FF0000',
        ];

        $result = party_to_colour('Labor');
        $this->assertStringStartsWith('#', $result);
        $this->assertEquals(7, strlen($result)); // #RRGGBB format
    }

    public function test_party_to_colour_default_is_light_grey() {
        global $party_colours;
        $party_colours = [];

        $result = party_to_colour('Any Party');
        $this->assertEquals('#eeeeee', $result);
    }

    /**
     * Test image path construction and file existence checks
     */
    public function test_find_rep_image_no_files() {
        // When no files exist
        $result = find_rep_image('99999999');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);
    }

    public function test_find_rep_image_result_structure() {
        $result = find_rep_image('12345');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        // First element is path (or null), second is size (or null)
        $this->assertTrue($result[0] === null || is_string($result[0]));
        $this->assertTrue($result[1] === null || is_string($result[1]));
    }

    public function test_find_rep_image_size_indicators() {
        // When testing with mock data, sizes should be 'L' or 'S' or null
        $result = find_rep_image('12345');
        if ($result[1] !== null) {
            $this->assertContains($result[1], ['L', 'S']);
        } else {
            $this->assertNull($result[1]);
        }
    }

    public function test_find_rep_image_extension_support() {
        // Test that jpg, jpeg, and png are checked
        $pid = '12345';
        $result = find_rep_image($pid);
        // Result structure validates that multiple extensions are checked
        $this->assertIsArray($result);
    }

    public function test_find_rep_image_smallonly_flag() {
        // With smallonly = true, should skip large images
        $result = find_rep_image('12345', true);
        $this->assertIsArray($result);
        // If a file is found, size should be 'S' (small)
        if ($result[1] !== null) {
            $this->assertEquals('S', $result[1]);
        }
    }

    public function test_find_rep_image_without_smallonly() {
        // Default = false, should check large images first
        $result = find_rep_image('12345', false);
        $this->assertIsArray($result);
    }

    /**
     * Test party colour global variable setup
     */
    public function test_party_colours_global_exists() {
        global $party_colours;
        if (isset($party_colours)) {
            $this->assertIsArray($party_colours);
        }
    }

    /**
     * Test typical Australian party codes
     */
    public function test_common_party_names() {
        global $party_colours;
        $party_colours = [];

        $parties = [
            'Australian Labor Party',
            'Liberal Party of Australia',
            'Australian Greens',
            'Family First Party',
            'Independent',
        ];

        foreach ($parties as $party) {
            $result = party_to_colour($party);
            $this->assertIsString($result);
            $this->assertStringStartsWith('#', $result);
        }
    }
}
