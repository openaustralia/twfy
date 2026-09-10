<?php

/**
 * @file
 * Integration test for www/docs/index.php page rendering.
 */

require_once __DIR__ . '/PageRenderingIntegrationTestCase.php';

/**
 * Integration tests for homepage rendering.
 */
class IndexPageIntegrationTest extends PageRenderingIntegrationTestCase {

    /**
     * Test that the index page renders without errors.
     */
    public function test_index_page_renders(): void {
        $this->assertPageRenders(__DIR__ . '/../www/docs/index.php', 'desktop');
    }

    /**
     * Test that the charity/mission callout box is rendered on the home page - was
     * "What's all this about?" (sidebars/whatisthissite.php) before the front-page
     * redesign moved it to the bottom of the page as "We're a small charity with a
     * big mission" (see index.php's about_this_site_card()).
     */
    public function test_index_page_shows_the_charity_mission_box(): void {
        $output = $this->assertPageRenders(__DIR__ . '/../www/docs/index.php', 'desktop');
        $this->assertStringContainsString("We're a small charity with a big mission.", $output);
        // Not 'public digital online library' as one contiguous string - the real
        // markup wraps onto its own line between "public" and "digital", so that
        // string is never literally present as typed.
        $this->assertStringContainsString('OpenAustralia Foundation', $output);
    }

}
