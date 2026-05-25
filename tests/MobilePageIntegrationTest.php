<?php

/**
 * @file
 * Integration test for www/docs/mobile.php page rendering.
 */

require_once __DIR__ . '/PageRenderingIntegrationTestCase.php';

/**
 * Integration tests for mobile homepage rendering.
 */
class MobilePageIntegrationTest extends PageRenderingIntegrationTestCase {

    /**
     * Test that the mobile page renders without errors.
     */
    public function test_mobile_page_renders(): void {
        $this->assertPageRenders(__DIR__ . '/../www/docs/mobile.php', 'mobile');
    }

}
