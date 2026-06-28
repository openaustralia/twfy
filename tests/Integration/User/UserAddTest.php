<?php

/**
 * Integration tests for USER::add() method.
 * Tests use real database with transaction rollback isolation.
 */
class UserAddTest extends TransactionalTestCase {

    /**
     * Test basic user creation with required fields.
     */
    public function test_add_creates_user_with_required_fields() {
        $USER = new USER();

        $details = [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'constituency' => 'Leeds',
            'url' => 'http://example.com',
            'password' => 'testpassword123',
        ];

        $result = $USER->add($details, false);

        $this->assertTrue($result);
        $this->assertNotEmpty($USER->user_id());
        $this->assertTrue(is_numeric($USER->user_id()));
    }

    /**
     * Test all user fields are stored correctly.
     */
    public function test_add_stores_all_fields_in_database() {
        $USER = new USER();

        $details = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@test.com',
            'constituency' => 'Westminster',
            'url' => 'https://johndoe.com',
            'password' => 'securepass456',
            'emailpublic' => true,
            'optin' => true,
            'status' => 'User',
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        // Load the user back from the database.
        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $this->assertEquals('John', $retrievedUser->firstname());
        $this->assertEquals('Doe', $retrievedUser->lastname());
        $this->assertEquals('john.doe@test.com', $retrievedUser->email());
        $this->assertEquals('Westminster', $retrievedUser->constituency());
        $this->assertEquals('https://johndoe.com', $retrievedUser->url());
        $this->assertTrue($retrievedUser->emailpublic());
        $this->assertTrue($retrievedUser->optin());
        $this->assertEquals('User', $retrievedUser->status());
    }

    /**
     * Test boolean fields are converted to 1/0 correctly.
     */
    public function test_add_converts_boolean_fields() {
        $USER = new USER();

        $details = [
            'firstname' => 'Test',
            'lastname' => 'Bool',
            'email' => 'bool@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
            'emailpublic' => true,
            'optin' => false,
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $this->assertTrue($retrievedUser->emailpublic());
        $this->assertFalse($retrievedUser->optin());
    }

    /**
     * Test registration token is generated and stored.
     */
    public function test_add_generates_registration_token() {
        $USER = new USER();

        $details = [
            'firstname' => 'Token',
            'lastname' => 'Test',
            'email' => 'token@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];

        $USER->add($details, false);

        // The registration token should be set.
        $this->assertNotEmpty($USER->registrationtoken);
        // Token should be 22 characters as per the code comments.
        $this->assertEquals(22, strlen($USER->registrationtoken));
    }

    /**
     * Test default status is 'User' when not provided.
     */
    public function test_add_defaults_status_to_user() {
        $USER = new USER();

        $details = [
            'firstname' => 'Status',
            'lastname' => 'Default',
            'email' => 'status@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
            // Note: no status provided.
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $this->assertEquals('User', $retrievedUser->status());
    }

    /**
     * Test custom status is preserved.
     */
    public function test_add_preserves_custom_status() {
        $USER = new USER();

        $details = [
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
            'status' => 'Administrator',
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $this->assertEquals('Administrator', $retrievedUser->status());
    }

    /**
     * Test duplicate email is rejected.
     */
    public function test_add_rejects_duplicate_email() {
        $this->markTestIncomplete('Duplicate email rejection is not currently enforced in USER::add().');

        // Create first user.
        $USER1 = new USER();
        $details1 = [
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'duplicate@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];
        $USER1->add($details1, false);

        // Try to create second user with same email.
        $USER2 = new USER();
        $details2 = [
            'firstname' => 'Second',
            'lastname' => 'User',
            'email' => 'duplicate@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];

        $result = $USER2->add($details2, false);

        // Should fail due to unique constraint.
        $this->assertFalse($result);
    }

        /**
         * Test multiple users can be created without issue.
         */
        public function test_add_multiple_users_successfully() {
            $USER1 = new USER();
            $details1 = [
                'firstname' => 'User',
                'lastname' => 'One',
                'email' => 'user1@test.com',
                'constituency' => 'Test',
                'url' => '',
                'password' => 'pass',
            ];
            $result1 = $USER1->add($details1, false);
            $this->assertTrue($result1);
            $this->assertNotEmpty($USER1->user_id());

            $USER2 = new USER();
            $details2 = [
                'firstname' => 'User',
                'lastname' => 'Two',
                'email' => 'user2@test.com',
                'constituency' => 'Test',
                'url' => '',
                'password' => 'pass',
            ];
            $result2 = $USER2->add($details2, false);
            $this->assertTrue($result2);
            $this->assertNotEmpty($USER2->user_id());
            // Verify they have different IDs.
            $this->assertNotEquals($USER1->user_id(), $USER2->user_id());
        }

    /**
     * Test user is not confirmed by default.
     */
    public function test_add_sets_confirmed_to_false() {
        $USER = new USER();

        $details = [
            'firstname' => 'Confirm',
            'lastname' => 'Test',
            'email' => 'confirm@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $this->assertFalse($retrievedUser->confirmed());
    }

    /**
     * Test registration IP is stored.
     */
    public function test_add_stores_registration_ip() {
        $USER = new USER();

        $details = [
            'firstname' => 'IP',
            'lastname' => 'Test',
            'email' => 'iptest@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        // IP should be set to something.
        $this->assertNotEmpty($retrievedUser->registrationip());
    }

    /**
     * Test password is hashed with bcrypt.
     */
    public function test_add_hashes_password_with_bcrypt() {
        $USER = new USER();

        $plainPassword = 'MySecurePassword123!';
        $details = [
            'firstname' => 'Hash',
            'lastname' => 'Test',
            'email' => 'hash@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => $plainPassword,
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        // Password should be hashed, not plaintext.
        $this->assertNotEquals($plainPassword, $retrievedUser->password());
        // Should start with $2 (bcrypt prefix)
        $this->assertStringStartsWith('$2', $retrievedUser->password());
    }

    /**
     * Test registration time is set in GMT.
     */
    public function test_add_sets_registration_time() {
        $USER = new USER();

        $details = [
            'firstname' => 'Time',
            'lastname' => 'Test',
            'email' => 'time@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];

        $timeBefore = gmdate("Y-m-d H:i:s");
        $USER->add($details, false);
        $timeAfter = gmdate("Y-m-d H:i:s");

        $user_id = $USER->user_id();
        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $regTime = $retrievedUser->registrationtime();
        // Convert Carbon object to string if needed.
        if (is_object($regTime)) {
            $regTime = $regTime->format('Y-m-d H:i:s');
        }
        $this->assertGreaterThanOrEqual($timeBefore, $regTime);
        $this->assertLessThanOrEqual($timeAfter, $regTime);
    }

    /**
     * Test user is not deleted by default.
     */
    public function test_add_sets_deleted_to_false() {
        $USER = new USER();

        $details = [
            'firstname' => 'Active',
            'lastname' => 'User',
            'email' => 'active@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];

        $USER->add($details, false);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $this->assertFalse($retrievedUser->deleted());
    }

    /**
     * Test confirmation_required=false skips email sending.
     */
    public function test_add_with_confirmation_required_false_returns_true() {
        $USER = new USER();

        $details = [
            'firstname' => 'No',
            'lastname' => 'Confirm',
            'email' => 'noconfirm@test.com',
            'constituency' => 'Test',
            'url' => '',
            'password' => 'pass',
        ];

        // With confirmation_required=false, should still return true.
        $result = $USER->add($details, false);

        $this->assertTrue($result);
        $this->assertNotEmpty($USER->user_id());
    }

    /**
     * Test empty optional fields are handled.
     */
    public function test_add_handles_empty_optional_fields() {
        $USER = new USER();

        $details = [
            'firstname' => 'Minimal',
            'lastname' => 'User',
            'email' => 'minimal@test.com',
            'constituency' => '',
            'url' => '',
            'password' => 'pass',
        ];

        $result = $USER->add($details, false);

        $this->assertTrue($result);
        $user_id = $USER->user_id();

        $retrievedUser = new USER();
        $retrievedUser->init($user_id);

        $this->assertEquals('', $retrievedUser->constituency());
        $this->assertEquals('', $retrievedUser->url());
    }

}
