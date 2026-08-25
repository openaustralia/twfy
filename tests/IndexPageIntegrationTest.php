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
     * Test that the "What's all this about?" box is rendered on the home page.
     */
    public function test_index_page_shows_what_is_this_site_box(): void {
        $output = $this->assertPageRenders(__DIR__ . '/../www/docs/index.php', 'desktop');
        $this->assertStringContainsString("What's all this about?", $output);
        $this->assertStringContainsString('public digital online library', $output);
    }

}
