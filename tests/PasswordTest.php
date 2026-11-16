<?php

/**
 * @file
 * Unit tests for password hashing.
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests for password hashing and verification.
 */
class PasswordTest extends TestCase {

    /**
     * Test bcrypt password hashing.
     */
    public function test_bcrypt_password_hash(): void {
        $password = "testpassword123";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->assertTrue(password_verify($password, $hash));
    }

    /**
     * Test bcrypt rejects wrong password.
     */
    public function test_bcrypt_rejects_wrong_password(): void {
        $password = "testpassword123";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->assertFalse(password_verify("wrongpassword", $hash));
    }

    /**
     * Test bcrypt hash starts with $2.
     */
    public function test_bcrypt_hash_format(): void {
        $password = "testpassword123";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->assertStringStartsWith('$2', $hash);
    }

}
