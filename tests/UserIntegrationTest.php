<?php

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\Models\User as UserModel;

/**
 * Integration tests for USER/THEUSER methods.
 */
class UserIntegrationTest extends TransactionalTestCase {

    /**
     * Insert a test user into the database.
     */
    private function insertTestUser(string $email, string $firstname = 'Test'): int {
        UserModel::query()->insert([
            'firstname' => $firstname,
            'lastname' => 'User',
            'email' => $email,
            'emailpublic' => 0,
            'constituency' => '',
            'url' => '',
            'password' => password_hash('oldpassword123', PASSWORD_DEFAULT),
            'optin' => 0,
            'status' => 'User',
            'registrationtime' => gmdate('YmdHis'),
            'registrationip' => '127.0.0.1',
            'deleted' => 0,
            'confirmed' => 1,
            'registrationtoken' => '',
            'lastvisit' => gmdate('Y-m-d H:i:s'),
        ]);

        return (int) UserModel::where('email', $email)->value('user_id');
    }

    // =========================================================================
    // change_password() tests
    // =========================================================================

    public function test_change_password_returns_new_password_string(): void
    {
        $email = 'testuser@example.com';
        $this->insertTestUser($email, 'Alice');

        $USER = new USER();
        $newpwd = $USER->change_password($email);

        // Should return a non-empty string (the new plaintext password)
        $this->assertIsString($newpwd);
        $this->assertNotEmpty($newpwd);
        $this->assertNotSame('oldpassword123', $newpwd);
    }

    public function test_change_password_returns_false_for_nonexistent_email(): void
    {
        $USER = new USER();
        $result = $USER->change_password('nonexistent@example.com');

        $this->assertFalse($result);
    }

    public function test_change_password_stores_hashed_password_in_database(): void
    {
        $email = 'bob@example.com';
        $this->insertTestUser($email, 'Bob');

        $USER = new USER();
        $newpwd = $USER->change_password($email);

        // Verify the hashed password was stored
        $hashedPassword = UserModel::where('email', $email)->value('password');
        $this->assertIsString($hashedPassword);

        // Verify it's actually hashed (not plaintext)
        $this->assertNotSame($newpwd, $hashedPassword);

        // Verify the new password matches the hash
        $this->assertTrue(password_verify($newpwd, $hashedPassword));
    }

    public function test_change_password_generated_password_is_14_characters(): void
    {
        $email = 'charlie@example.com';
        $this->insertTestUser($email, 'Charlie');

        $USER = new USER();
        $newpwd = $USER->change_password($email);

        $this->assertSame(14, strlen($newpwd));
    }

    public function test_change_password_generated_password_uses_unambiguous_alphabet(): void
    {
        $email = 'diana@example.com';
        $this->insertTestUser($email, 'Diana');

        $USER = new USER();
        $newpwd = $USER->change_password($email);

        // The unambiguous alphabet excludes i, l, o, u, I, L, O, U, 0, 1
        $unambiguous_alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';

        for ($i = 0; $i < strlen($newpwd); $i++) {
            $this->assertStringContainsString($newpwd[$i], $unambiguous_alphabet,
                "Character '{$newpwd[$i]}' at position $i is not in unambiguous alphabet");
        }
    }

    public function test_change_password_updates_user_object_password_field(): void
    {
        $email = 'eve@example.com';
        $this->insertTestUser($email, 'Eve');

        $USER = new USER();
        $newpwd = $USER->change_password($email);

        // The USER object's password field should now be set to the plaintext password
        $this->assertSame($newpwd, $USER->password());
    }

    public function test_change_password_multiple_calls_generate_different_passwords(): void
    {
        $email1 = 'frank@example.com';
        $email2 = 'grace@example.com';
        $this->insertTestUser($email1, 'Frank');
        $this->insertTestUser($email2, 'Grace');

        $USER = new USER();
        $pwd1 = $USER->change_password($email1);
        $pwd2 = $USER->change_password($email2);

        // Two different passwords should be generated (extremely unlikely to collide)
        $this->assertNotSame($pwd1, $pwd2);
    }

    public function test_change_password_with_special_chars_in_email(): void
    {
        $email = 'user+tag@example.com';
        $this->insertTestUser($email);

        $USER = new USER();
        $newpwd = $USER->change_password($email);

        $this->assertIsString($newpwd);
        $this->assertNotEmpty($newpwd);

        // Verify it was actually stored
        $this->assertTrue(UserModel::where('email', $email)->exists());
    }

    public function test_change_password_can_log_in_with_new_password(): void
    {
        $email = 'hank@example.com';
        $this->insertTestUser($email, 'Hank');

        // Get new password
        $USER = new USER();
        $newpwd = $USER->change_password($email);

        // Create a new USER object and attempt to log in with the new password
        $THEUSER = new THEUSER();
        $result = $THEUSER->isvalid($email, $newpwd);

        // isvalid() should return TRUE on successful password match
        $this->assertTrue($result);
    }

    public function test_change_password_old_password_no_longer_works(): void
    {
        $email = 'iris@example.com';
        $oldpwd = 'oldpassword123';
        $this->insertTestUser($email, 'Iris');

        // Change the password
        $USER = new USER();
        $newpwd = $USER->change_password($email);

        // Try to log in with the old password
        $THEUSER = new THEUSER();
        $result = $THEUSER->isvalid($email, $oldpwd);

        // Should fail
        $this->assertNotTrue($result);
    }

}
