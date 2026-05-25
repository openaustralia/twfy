<?php

/**
 * @file
 * Integration test for www/docs/index.php page rendering.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for homepage rendering.
 */
class IndexPageIntegrationTest extends TestCase {

    /**
     *
     */
    public static function setUpBeforeClass(): void {
        $conn = getSharedTestConnection();
        if (!$conn) {
            self::markTestSkipped('Database connection not available');
        }
    }

    /**
     *
     */
    protected function setUp(): void {
        $conn = getSharedTestConnection();
        if (!$conn) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Test that the index page renders without errors.
     */
    public function test_index_page_renders(): void {
        $_SERVER['DEVICE_TYPE'] = 'desktop';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET = [];
        $_POST = [];

        ob_start();
        include __DIR__ . '/../www/docs/index.php';
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('<html', $output);
        $this->assertStringContainsString('OpenAustralia', $output);
    }

}
