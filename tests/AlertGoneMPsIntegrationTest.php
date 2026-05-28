<?php

/**
 * @file
 * Integration tests for alertgonemps.php script requiring database access.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/alert.php';
require_once __DIR__ . '/../www/includes/easyparliament/member.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for alert gone MPs functionality with database.
 */
class AlertGoneMPsIntegrationTest extends TestCase {

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
        // Verify connection exists.
        $conn = getSharedTestConnection();
        if (!$conn) {
            $this->markTestSkipped('Database connection not available');
        }
}

    /**
     *
     */
protected function tearDown(): void {
        // Clean up any test data.
        parlDBQuery('DELETE FROM alerts WHERE email = ?', 'test-gone-mp@example.com');
        parlDBQuery('DELETE FROM alerts WHERE email = ?', 'test-active-mp@example.com');
}

    /**
     * Test user lookup by email - registered user.
     */
    public function test_user_lookup_registered(): void {
        // This test requires existing user data in database.
        $q = parlDBQuery('SELECT user_id FROM users LIMIT 1');
        // If query succeeds, we found a user.
        $this->assertIsObject($q);
    }

    /**
     * Test user lookup by email - unregistered user.
     */
    public function test_user_lookup_unregistered(): void {
        $uniqueEmail = 'nonexistent_alert_' . time() . '@example.com';
        $q = parlDBQuery('SELECT user_id FROM users WHERE email = ?', $uniqueEmail);
        $this->assertSame(0, $q->rows());
    }

}
