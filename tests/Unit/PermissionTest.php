<?php

/**
 * @file
 * Unit tests for USER permission checks and role validation.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once EASYPARLIAMENTPATH . 'user.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for permission and role-based access control.
 */
class PermissionTest extends TestCase {

    /**
     * Test valid user statuses exist.
     */
    public function test_valid_statuses(): void {
        $statuses = ["Viewer", "User", "Moderator", "Administrator", "Superuser"];
        $this->assertCount(5, $statuses);
        $this->assertContains("User", $statuses);
        $this->assertContains("Viewer", $statuses);
        $this->assertContains("Administrator", $statuses);
    }

    /**
     * Test viewer user cannot add comment.
     */
    public function test_viewer_cannot_addcomment(): void {
        $user = new USER();
        $user->status = 'Viewer';
        $this->assertFalse($user->is_able_to('addcomment'));
    }

    /**
     * Test moderator can delete comment.
     */
    public function test_moderator_can_deletecomment(): void {
        $user = new USER();
        $user->status = 'Moderator';
        $this->assertTrue($user->is_able_to('deletecomment'));
    }

    /**
     * Test administrator cannot edit other users.
     */
    public function test_administrator_cannot_edituser(): void {
        $user = new USER();
        $user->status = 'Administrator';
        $this->assertFalse($user->is_able_to('edituser'));
    }

    /**
     * Test superuser can edit users.
     */
    public function test_superuser_can_edituser(): void {
        $user = new USER();
        $user->status = 'Superuser';
        $this->assertTrue($user->is_able_to('edituser'));
    }

    /**
     * Test viewer can report comment.
     */
    public function test_viewer_can_reportcomment(): void {
        $user = new USER();
        $user->status = 'Viewer';
        $this->assertTrue($user->is_able_to('reportcomment'));
    }

}
