<?php

/**
 * @file
 * Tests for USER::id_exists() method.
 */

require_once __DIR__ . '/../../bootstrap.php';

use OpenAustralia\TWFY\Models\User as UserModel;

/**
 * Tests for USER id existence checks.
 */
class UserIdExistsTest extends TransactionalTestCase {

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
     * Test that id_exists() returns true for a user created in the test transaction.
     */
    public function test_id_exists_returns_true_for_user_in_transaction() {
        $this->insertTestUser(99060, 'Ivy', 'Exists', 'ivy.exists@example.com');

        $user = new USER();

        $this->assertTrue($user->id_exists(99060));
    }

}
