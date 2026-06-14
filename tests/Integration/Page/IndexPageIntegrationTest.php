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
        $this->assertPageRenders(BASEDIR . '/docs/index.php', 'desktop');
    }

}
