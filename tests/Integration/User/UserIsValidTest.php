<?php

/**
 * @file
 * Tests for THEUSER::isvalid() method.
 */

use OpenAustralia\TWFY\Models\User as UserModel;

/**
 * Tests for user credential validation.
 */
class UserIsValidTest extends TransactionalTestCase {

    /**
     * Insert a legacy md5-crypt user record.
     */
    private function insertLegacyHashUser(int $user_id, string $email, string $plaintextPassword): void {
        $legacyHash = crypt($plaintextPassword, '$1$testsalt$');

        UserModel::query()->insert([
            'user_id' => $user_id,
            'firstname' => 'Legacy',
            'lastname' => 'User',
            'email' => $email,
            'emailpublic' => 0,
            'constituency' => '',
            'url' => '',
            'password' => $legacyHash,
            'optin' => 0,
            'status' => 'User',
            'registrationtime' => gmdate('YmdHis'),
            'registrationip' => '127.0.0.1',
            'deleted' => 0,
            'confirmed' => 1,
            'registrationtoken' => '',
            'lastvisit' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Legacy md5-crypt passwords should authenticate and be upgraded to bcrypt.
     */
    public function test_isvalid_upgrades_legacy_md5_crypt_hash_to_bcrypt(): void {
        $email = 'legacyhash@example.com';
        $plaintextPassword = 'LegacyPass123';

        $this->insertLegacyHashUser(99080, $email, $plaintextPassword);

        $THEUSER = new THEUSER();
        $result = $THEUSER->isvalid($email, $plaintextPassword);

        $this->assertTrue($result);

        $upgradedHash = UserModel::where('email', $email)->value('password');

        $this->assertIsString($upgradedHash);
        $this->assertFalse(str_starts_with($upgradedHash, '$1$'));
        $this->assertTrue(password_verify($plaintextPassword, $upgradedHash));
    }

}
