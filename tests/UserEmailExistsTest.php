<?php

/**
 * @file
 * Tests for USER::email_exists() method.
 */

require_once __DIR__ . '/bootstrap.php';

use OpenAustralia\TWFY\Models\User as UserModel;

/**
 * Tests for USER email existence checks.
 */
class UserEmailExistsTest extends TransactionalTestCase {

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
     * Test that email_exists() returns true for a user created in the test transaction.
     */
    public function test_email_exists_returns_true_for_user_in_transaction() {
        $this->insertTestUser(99070, 'Elle', 'Exists', 'elle.exists@example.com');

        $user = new USER();

        $this->assertTrue($user->email_exists('elle.exists@example.com'));
    }

}
