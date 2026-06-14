<?php

/**
 * @file
 * Unit tests for USER class functionality.
 */

require_once EASYPARLIAMENTPATH . 'user.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for USER class accessor and permission methods.
 */
class UserTest extends TestCase {

    /**
     * Test id_exists with system user (method exists).
     */
    public function test_id_exists_method_exists(): void {
        $user = new USER();
        $this->assertTrue(method_exists($user, 'id_exists'));
    }

    /**
     * Test id_exists returns boolean.
     */
    public function test_id_exists_returns_bool(): void {
        $user = new USER();
        // Just verify the method exists and returns a boolean
        $result = $user->id_exists(999999);
        $this->assertIsBool($result);
    }

    /**
     * Test email_exists with invalid email returns false.
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
     * Test firstname accessor default.
     */
    public function test_firstname_default(): void {
        $user = new USER();
        $this->assertSame('Guest', $user->firstname());
    }

    /**
     * Test lastname accessor default.
     */
    public function test_lastname_default(): void {
        $user = new USER();
        $this->assertSame('', $user->lastname());
    }

    /**
     * Test email accessor default.
     */
    public function test_email_default(): void {
        $user = new USER();
        $this->assertSame('', $user->email());
    }

    /**
     * Test constituency accessor default.
     */
    public function test_constituency_default(): void {
        $user = new USER();
        $this->assertSame('', $user->constituency());
    }

    /**
     * Test status accessor default.
     */
    public function test_status_default(): void {
        $user = new USER();
        $this->assertSame('Viewer', $user->status());
    }

}
