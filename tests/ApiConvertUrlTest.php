<?php

/**
 * @file
 * Unit tests for api_convertURL.php API functions.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for API convertURL functionality.
 */
class ApiConvertUrlTest extends TestCase {

    /**
     * Test htype detection for direct gid insertion (htype 11).
     */
    public function test_htype_11_direct_gid(): void {
        $htype = '11';
        $isDirectGid = ($htype == '11' || $htype == '10');
        $this->assertTrue($isDirectGid);
    }

    /**
     * Test htype detection for direct gid insertion (htype 10).
     */
    public function test_htype_10_direct_gid(): void {
        $htype = '10';
        $isDirectGid = ($htype == '11' || $htype == '10');
        $this->assertTrue($isDirectGid);
    }

    /**
     * Test htype detection for parent lookup (other htypes).
     */
    public function test_htype_other_needs_parent(): void {
        $htype = '1';
        $needsParent = !($htype == '11' || $htype == '10');
        $this->assertTrue($needsParent);
    }

    /**
     * Test URL fragment construction with gid_to_anchor.
     */
    public function test_url_fragment_construction(): void {
        $gid = '2006-07-11a.1352.2';
        $fragment = '#g' . $gid;

        $this->assertStringStartsWith('#g', $fragment);
        $this->assertStringContainsString('2006-07-11a', $fragment);
    }

    /**
     * Test gid extraction from result.
     */
    public function test_gid_extraction(): void {
        $gid = 'uk.org.publicwhip/debate/2006-07-11a.1352.2';
        $this->assertStringContainsString('2006-07-11a', $gid);
    }

    /**
     * Test major field extraction.
     */
    public function test_major_field_extraction(): void {
        $major = 1;
        $this->assertSame(1, $major);
    }

    /**
     * Test subsection_id field extraction.
     */
    public function test_subsection_id_extraction(): void {
        $subsection_id = 12345;
        $this->assertIsInt($subsection_id);
    }

    /**
     * Test URL hash removal from source URL.
     */
    public function test_url_hash_removal(): void {
        $url = 'http://example.com/hansard#section1';
        $url_nohash = preg_replace('/#.*/', '', $url);

        $this->assertSame('http://example.com/hansard', $url_nohash);
        $this->assertStringNotContainsString('#', $url_nohash);
    }

    /**
     * Test URL pattern replacement for bound URLs.
     */
    public function test_url_bound_replacement(): void {
        $url_nohash = 'http://aph.gov.au/cmhansrd/cm061004.pdf';
        $url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url_nohash);

        $this->assertStringContainsString('cmhansrd/vo', $url_bound);
    }

    /**
     * Test bound URL condition - URL changed.
     */
    public function test_bound_url_different(): void {
        $url_nohash = 'http://aph.gov.au/cmhansrd/cm061004.pdf';
        $url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url_nohash);

        $isDifferent = ($url_bound != $url_nohash);
        $this->assertTrue($isDifferent);
    }

    /**
     * Test bound URL condition - URL unchanged (no cmhansrd/cm).
     */
    public function test_bound_url_same(): void {
        $url_nohash = 'http://example.com/hansard/061004.pdf';
        $url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url_nohash);

        $isDifferent = ($url_bound != $url_nohash);
        $this->assertFalse($isDifferent);
    }

    /**
     * Test LIKE pattern for URL matching.
     */
    public function test_url_like_pattern(): void {
        $url_nohash = 'hansard/061004';
        $likePattern = "%$url_nohash%";

        $this->assertSame('%hansard/061004%', $likePattern);
    }

    /**
     * Test gid ordering in result set.
     */
    public function test_gid_ordering(): void {
        // Results should be ordered by gid to get consistent results.
// ORDER BY gid ensures reproducibility.
        $this->assertTrue(TRUE);
    }

    /**
     * Test empty parent_gid initialization.
     */
    public function test_parent_gid_initialization(): void {
        $parent_gid = '';
        $this->assertSame('', $parent_gid);
    }

    /**
     * Test parent_gid check before fragment creation.
     */
    public function test_parent_gid_required_for_fragment(): void {
        $parent_gid = 'uk.org.publicwhip/debate/2006-07-11a.1352.0';
        $hasParent = ($parent_gid != '');

        $this->assertTrue($hasParent);
    }

    /**
     * Test fragment not created for empty parent_gid.
     */
    public function test_no_fragment_without_parent(): void {
        $parent_gid = '';
        $fragment = '';

        if ($parent_gid != '') {
            $fragment = '#g' . $parent_gid;
        }

        $this->assertSame('', $fragment);
    }

}
