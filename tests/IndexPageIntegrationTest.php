<?php

/**
 * @file
 * Integration test for www/docs/index.php page rendering.
 */

require_once __DIR__ . '/PageRenderingIntegrationTestCase.php';

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Integration tests for homepage rendering.
 */
class IndexPageIntegrationTest extends PageRenderingIntegrationTestCase {

    /**
     * Test that the index page renders without errors.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_index_page_renders(): void {
        $this->assertPageRenders(__DIR__ . '/../www/docs/index.php', 'desktop');
    }

}
