<?php

/**
 * @file
 * Integration test for www/docs/mobile.php page rendering.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for mobile homepage rendering.
 */
class MobilePageIntegrationTest extends TestCase {

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
     * Test that the mobile page renders without errors.
     */
    public function test_mobile_page_renders(): void {
        $_SERVER['DEVICE_TYPE'] = 'mobile';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET = [];
        $_POST = [];

        ob_start();
        include __DIR__ . '/../www/docs/mobile.php';
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('<html', $output);
        $this->assertStringContainsString('OpenAustralia', $output);
    }

}
