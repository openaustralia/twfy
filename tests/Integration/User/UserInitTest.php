<?php

/**
 * @file
 * Tests for USER::init() method.
 */

require_once __DIR__ . '/../../bootstrap.php';

use OpenAustralia\TWFY\Models\User as UserModel;

/**
 * Tests for USER initialization functionality.
 */
class UserInitTest extends TransactionalTestCase {

    /**
     * Insert a test user record.
     */
    private function insertTestUser($user_id, $firstname, $lastname, $email, $status = 'User', $confirmed = 1, $deleted = 0) {
        UserModel::query()->insert([
            'user_id' => $user_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'emailpublic' => 0,
            'constituency' => '',
            'url' => '',
            'password' => password_hash('testpass', PASSWORD_DEFAULT),
            'optin' => 0,
            'status' => $status,
            'registrationtime' => gmdate('YmdHis'),
            'registrationip' => '127.0.0.1',
            'deleted' => $deleted,
            'confirmed' => $confirmed,
            'registrationtoken' => '',
            'lastvisit' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Test that init() loads user data correctly.
     */
    public function test_init_loads_user_data() {
        $this->insertTestUser(99001, 'Alice', 'Smith', 'alice@example.com');

        $user = new USER();
        $result = $user->init(99001);

        $this->assertTrue($result);
        $this->assertSame(99001, $user->user_id());
        $this->assertSame('Alice', $user->firstname());
        $this->assertSame('Smith', $user->lastname());
        $this->assertSame('alice@example.com', $user->email());
        $this->assertSame('User', $user->status());
        $this->assertTrue($user->confirmed());
        $this->assertFalse($user->deleted());
    }

    /**
     * Test that init() returns false for non-existent user.
     */
    public function test_init_returns_false_for_missing_user() {
        $user = new USER();
        $result = $user->init(999999);

        $this->assertFalse($result);
    }

    /**
     * Test that init() handles boolean fields correctly.
     */
    public function test_init_converts_boolean_fields() {
        $this->insertTestUser(99010, 'Bob', 'Jones', 'bob@example.com', 'Moderator', 0, 1);

        $user = new USER();
        $user->init(99010);

        $this->assertFalse($user->confirmed());
        $this->assertTrue($user->deleted());
        $this->assertSame('Moderator', $user->status());
    }

    /**
     * Test that init() works with various status values.
     */
    public function test_init_preserves_status() {
        $statuses = ['Viewer', 'User', 'Moderator', 'Administrator', 'Superuser'];

        foreach ($statuses as $idx => $status) {
            $user_id = 99020 + $idx;
            $this->insertTestUser($user_id, "User$idx", "Test$idx", "user$idx@example.com", $status);

            $user = new USER();
            $user->init($user_id);

            $this->assertSame($status, $user->status());
        }
    }

    /**
     * Test that init() handles optional fields.
     */
    public function test_init_with_constituency_and_url() {
        UserModel::query()->insert([
            'user_id' => 99030,
            'firstname' => 'Charlie',
            'lastname' => 'Brown',
            'email' => 'charlie@example.com',
            'emailpublic' => 1,
            'constituency' => 'TestConstituency',
            'url' => 'https://example.com/charlie',
            'password' => password_hash('testpass', PASSWORD_DEFAULT),
            'optin' => 1,
            'status' => 'User',
            'registrationtime' => gmdate('YmdHis'),
            'registrationip' => '127.0.0.1',
            'deleted' => 0,
            'confirmed' => 1,
            'registrationtoken' => '',
            'lastvisit' => gmdate('Y-m-d H:i:s'),
        ]);

        $user = new USER();
        $user->init(99030);

        $this->assertSame('TestConstituency', $user->constituency());
        $this->assertSame('https://example.com/charlie', $user->url());
        $this->assertTrue($user->emailpublic());
        $this->assertTrue($user->optin());
    }

    /**
     * Test that init() handles guest user (user_id = 0).
     */
    public function test_init_guest_user() {
        // The guest user (0) should already exist in the database
        $user = new USER();
        $result = $user->init(0);

        // This test verifies init() doesn't break on user_id 0
        // Actual result depends on whether guest user is seeded in test DB
        // If it exists, it should load successfully; if not, init() should return false
        $this->assertIsBool($result);
    }

    /**
     * Test that init() initializes USER object correctly after construction.
     */
    public function test_init_called_after_construction() {
        $this->insertTestUser(99040, 'David', 'Lee', 'david@example.com', 'User', 1, 0);

        $user = new USER();
        // USER constructor doesn't load data - init() must be called
        $this->assertSame('0', $user->user_id());
        $this->assertSame('Guest', $user->firstname());

        // After init, values should be set
        $user->init(99040);
        $this->assertSame(99040, $user->user_id());
        $this->assertSame('David', $user->firstname());
    }

}
