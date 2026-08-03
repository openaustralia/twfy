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

    /**
     * Test that the "What's all this about?" box is rendered on the mobile home page.
     */
    public function test_mobile_page_shows_what_is_this_site_box(): void {
        $output = $this->assertPageRenders(__DIR__ . '/../www/docs/mobile.php', 'mobile');
        $this->assertStringContainsString("What's all this about?", $output);
        $this->assertStringContainsString('public digital online library', $output);
    }

}
