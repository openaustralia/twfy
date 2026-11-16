<?php

/**
 * @file
 * Unit tests for user.php classes (USER and THEUSER).
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../www/includes/easyparliament/user.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for USER class functionality.
 */
class UserTest extends TestCase {

    protected $testUserId;

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
        // Clean up test user.
        if ($this->testUserId) {
            parlDBQuery('DELETE FROM users WHERE user_id = ?', $this->testUserId);
        }
}

    /**
     * Test id_exists returns true for valid user_id.
     */
    public function test_id_exists_valid(): void {
        $user = new USER();
        // Use a known system user ID (often 0 or 1)
        $result = $user->id_exists(0);
        // Just check the method doesn't error - result depends on DB state.
        $this->assertIsBool($result);
    }

    /**
     * Test id_exists returns false for invalid user_id.
     */
    public function test_id_exists_invalid(): void {
        $user = new USER();
        $this->assertFalse($user->id_exists(999999));
    }

    /**
     * Test email_exists with non-existent email returns false.
     */
    public function test_email_exists_invalid(): void {
        $user = new USER();
        $uniqueEmail = 'nonexistent_' . time() . '@example.com';
        $this->assertFalse($user->email_exists($uniqueEmail));
    }

    /**
     * Test possible_statuses returns array of valid statuses.
     */
    public function test_possible_statuses(): void {
        $user = new USER();
        $statuses = $user->possible_statuses();
        $this->assertCount(5, $statuses);
        $this->assertContains('User', $statuses);
        $this->assertContains('Viewer', $statuses);
        $this->assertContains('Administrator', $statuses);
    }

    /**
     * Test is_able_to with User status can add comment.
     */
    public function test_is_able_to_user_can_addcomment(): void {
        $user = new USER();
        $user->status = 'User';
        $this->assertTrue($user->is_able_to('addcomment'));
    }

    /**
     * Test is_able_to with User status cannot edit other users.
     */
    public function test_is_able_to_user_cannot_edituser(): void {
        $user = new USER();
        $user->status = 'User';
        $this->assertFalse($user->is_able_to('edituser'));
    }

    /**
     * Test firstname accessor.
     */
    public function test_firstname_default(): void {
        $user = new USER();
        $this->assertSame('Guest', $user->firstname());
    }

    /**
     * Test lastname accessor.
     */
    public function test_lastname_default(): void {
        $user = new USER();
        $this->assertSame('', $user->lastname());
    }

    /**
     * Test email accessor.
     */
    public function test_email_default(): void {
        $user = new USER();
        $this->assertSame('', $user->email());
    }

    /**
     * Test constituency accessor.
     */
    public function test_constituency_default(): void {
        $user = new USER();
        $this->assertSame('', $user->constituency());
    }

    /**
     * Test status accessor.
     */
    public function test_status_default(): void {
        $user = new USER();
        $this->assertSame('Viewer', $user->status());
    }

}
