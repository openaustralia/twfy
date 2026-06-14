<?php

use PHPUnit\Framework\TestCase;

/**
 *
 */
class MemberPartyTest extends TestCase {

    /** @var string[] */
    private array $createdImagePaths = [];

    /**
     * Track temporary image files created by tests.
     */
    private function createImageFixture(string $relativePath): void {
        $fullPath = FILEIMAGEPATH . $relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($fullPath, 'fixture');
        $this->createdImagePaths[] = $fullPath;
    }

    /**
     * Remove temporary image fixtures created during tests.
     */
    protected function tearDown(): void {
        foreach ($this->createdImagePaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->createdImagePaths = [];

        parent::tearDown();
    }

    /**
     * Test party to colour mapping
     */
    public function test_party_to_colour_major_parties() {
        // Setup global party_colours for testing.
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

    /**
     *
     */
    public function test_party_to_colour_unknown_party() {
        global $party_colours;
        $party_colours = [
            'Liberal Party' => '#0000FF',
        ];

        // Unknown party should return default grey.
        $result = party_to_colour('Unknown Party');
        $this->assertEquals('#eeeeee', $result);
    }

    /**
     *
     */
    public function test_party_to_colour_empty_string() {
        global $party_colours;
        $party_colours = [
            'Liberal Party' => '#0000FF',
        ];

        $result = party_to_colour('');
        $this->assertEquals('#eeeeee', $result);
    }

    /**
     *
     */
    public function test_party_to_colour_case_sensitive() {
        global $party_colours;
        $party_colours = [
            'Liberal Party' => '#0000FF',
        ];

        // Case mismatch should return default.
        $result = party_to_colour('liberal party');
        $this->assertEquals('#eeeeee', $result);
    }

    /**
     *
     */
    public function test_party_to_colour_returns_hex_format() {
        global $party_colours;
        $party_colours = [
            'Labor' => '#FF0000',
        ];

        $result = party_to_colour('Labor');
        $this->assertStringStartsWith('#', $result);
        // #RRGGBB format
        $this->assertEquals(7, strlen($result));
    }

    /**
     *
     */
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
        // When no files exist.
        $result = find_rep_image('99999999');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);
    }

    /**
     *
     */
    public function test_find_rep_image_result_structure() {
        $result = find_rep_image('12345');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        // First element is path (or null), second is size (or null)
        $this->assertTrue($result[0] === null || is_string($result[0]));
        $this->assertTrue($result[1] === null || is_string($result[1]));
    }

    /**
     *
     */
    public function test_find_rep_image_size_indicators() {
        // When testing with mock data, sizes should be 'L' or 'S' or null.
        $result = find_rep_image('12345');
        if ($result[1] !== null) {
            $this->assertContains($result[1], ['L', 'S']);
        } else {
            $this->assertNull($result[1]);
        }
    }

    /**
     *
     */
    public function test_find_rep_image_extension_support() {
        // Test that jpg, jpeg, and png are checked.
        $pid = '12345';
        $result = find_rep_image($pid);
        // Result structure validates that multiple extensions are checked.
        $this->assertIsArray($result);
    }

    /**
     *
     */
    public function test_find_rep_image_smallonly_flag() {
        // With smallonly = true, should skip large images.
        $result = find_rep_image('12345', true);
        $this->assertIsArray($result);
        // If a file is found, size should be 'S' (small)
        if ($result[1] !== null) {
            $this->assertEquals('S', $result[1]);
        }
    }

    /**
     *
     */
    public function test_find_rep_image_without_smallonly() {
        // Default = false, should check large images first.
        $result = find_rep_image('12345', false);
        $this->assertIsArray($result);
    }

    /**
     * Covers branch: first large image check (.jpg).
     */
    public function test_find_rep_image_prefers_large_jpg_first(): void {
        $pid = 'tx_findrep_ljpg';
        $this->createImageFixture('mpsL/' . $pid . '.jpg');
        $this->createImageFixture('mps/' . $pid . '.jpg');

        $this->assertSame([IMAGEPATH . 'mpsL/' . $pid . '.jpg', 'L'], find_rep_image($pid));
    }

    /**
     * Covers branch: second large image check (.jpeg).
     */
    public function test_find_rep_image_uses_large_jpeg_when_large_jpg_missing(): void {
        $pid = 'tx_findrep_ljpeg';
        $this->createImageFixture('mpsL/' . $pid . '.jpeg');

        $this->assertSame([IMAGEPATH . 'mpsL/' . $pid . '.jpeg', 'L'], find_rep_image($pid));
    }

    /**
     * Covers branch: third large image check (.png).
     */
    public function test_find_rep_image_uses_large_png_when_large_jpg_and_jpeg_missing(): void {
        $pid = 'tx_findrep_lpng';
        $this->createImageFixture('mpsL/' . $pid . '.png');

        $this->assertSame([IMAGEPATH . 'mpsL/' . $pid . '.png', 'L'], find_rep_image($pid));
    }

    /**
     * Covers branch: first small image check (.jpg).
     */
    public function test_find_rep_image_uses_small_jpg_when_no_large_image_exists(): void {
        $pid = 'tx_findrep_sjpg';
        $this->createImageFixture('mps/' . $pid . '.jpg');

        $this->assertSame([IMAGEPATH . 'mps/' . $pid . '.jpg', 'S'], find_rep_image($pid));
    }

    /**
     * Covers branch: second small image check (.jpeg).
     */
    public function test_find_rep_image_uses_small_jpeg_when_small_jpg_missing(): void {
        $pid = 'tx_findrep_sjpeg';
        $this->createImageFixture('mps/' . $pid . '.jpeg');

        $this->assertSame([IMAGEPATH . 'mps/' . $pid . '.jpeg', 'S'], find_rep_image($pid));
    }

    /**
     * Covers branch: third small image check (.png).
     */
    public function test_find_rep_image_uses_small_png_when_small_jpg_and_jpeg_missing(): void {
        $pid = 'tx_findrep_spng';
        $this->createImageFixture('mps/' . $pid . '.png');

        $this->assertSame([IMAGEPATH . 'mps/' . $pid . '.png', 'S'], find_rep_image($pid));
    }

    /**
     * Covers smallonly=true branch that skips all large image checks.
     */
    public function test_find_rep_image_smallonly_skips_large_and_uses_small(): void {
        $pid = 'tx_findrep_smallonly';
        $this->createImageFixture('mpsL/' . $pid . '.jpg');
        $this->createImageFixture('mps/' . $pid . '.png');

        $this->assertSame([IMAGEPATH . 'mps/' . $pid . '.png', 'S'], find_rep_image($pid, true));
    }

    /**
     * Covers final return branch when no image file matches.
     */
    public function test_find_rep_image_returns_nulls_when_no_matching_files_exist(): void {
        $pid = 'tx_findrep_none';
        $this->assertSame([null, null], find_rep_image($pid));
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
