<?php

require_once __DIR__ . '/bootstrap.php';

if (!class_exists('URL')) {
    /**
     * Minimal URL class for user confirmation tests.
     */
    class URL {

        /** @var string */
        private string $page;

        /**
         * @var array<string,string>
         */
        private array $params = [];

        public function __construct(string $page) {
            $this->page = $page;
        }

        public function insert(array $params): void {
            $this->params = array_merge($this->params, $params);
        }

        public function generate(): string {
            return '/' . $this->page;
        }
    }
}

use OpenAustralia\TWFY\Models\Member as MemberModel;
use OpenAustralia\TWFY\Models\User as UserModel;

/**
 * Integration tests for USER/THEUSER methods.
 */
class UserIntegrationTest extends TransactionalTestCase {

    /** @var string[] */
    private array $createdEmails = [];
    /** @var int[] */
    private array $createdMemberIds = [];

    protected function useMysqliTransaction(): bool {
        return false;
    }

    protected function useEloquentTransaction(): bool {
        return false;
    }

    protected function tearDown(): void {
        if ($this->createdEmails !== []) {
            parlDBQuery('DELETE FROM alerts WHERE email IN (' . implode(',', array_fill(0, count($this->createdEmails), '?')) . ')', ...$this->createdEmails);
            UserModel::whereIn('email', $this->createdEmails)->delete();
        }
        if ($this->createdMemberIds !== []) {
            MemberModel::whereIn('member_id', $this->createdMemberIds)->delete();
        }
        $this->createdEmails = [];
        $this->createdMemberIds = [];
        parent::tearDown();
    }

    /**
     * Insert a test user into the database.
     */
    private function insertTestUser(string $email, string $firstname = 'Test'): int {
        $this->createdEmails[] = $email;

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

    /**
     * Insert a user suitable for THEUSER::confirm() tests.
     */
    private function insertConfirmUser(
        string $email,
        string $registrationtoken,
        int $confirmed = 0,
        string $constituency = ''
    ): int {
        $this->createdEmails[] = $email;

        UserModel::query()->insert([
            'firstname' => 'Confirm',
            'lastname' => 'User',
            'email' => $email,
            'emailpublic' => 0,
            'constituency' => $constituency,
            'url' => '',
            'password' => password_hash('oldpassword123', PASSWORD_DEFAULT),
            'optin' => 0,
            'status' => 'User',
            'registrationtime' => gmdate('Y-m-d H:i:s'),
            'registrationip' => '127.0.0.1',
            'deleted' => 0,
            'confirmed' => $confirmed,
            'registrationtoken' => $registrationtoken,
            'lastvisit' => gmdate('Y-m-d H:i:s'),
        ]);

        return (int) UserModel::where('email', $email)->value('user_id');
    }

    /**
     * Insert a minimal member row for constituency -> person lookup.
     */
    private function insertMemberForConstituency(string $constituency, int $personId): int {
        $memberId = (int) UserModel::max('user_id') + random_int(1000, 9000);
        $this->createdMemberIds[] = $memberId;

        MemberModel::query()->insert([
            'member_id' => $memberId,
            'house' => 1,
            'first_name' => 'Test',
            'last_name' => 'Member',
            'constituency' => $constituency,
            'party' => 'Test Party',
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
            'person_id' => $personId,
            'title' => '',
        ]);

        return $memberId;
    }

    /**
     * Build a THEUSER test double that avoids real redirect/cookie side effects.
     */
    private function makeTestableTheUser(): THEUSER {
        return new class extends THEUSER {
            /** @var array<int,array{returl:string,expire:string}> */
            public array $loginCalls = [];

            public function login(string $returl = '', $expire = 'session') {
                $this->loginCalls[] = [
                    'returl' => $returl,
                    'expire' => (string) $expire,
                ];
            }
        };
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

    // =========================================================================
    // confirm() tests
    // =========================================================================

    public function test_confirm_returns_false_when_user_does_not_exist(): void
    {
        $THEUSER = $this->makeTestableTheUser();

        $result = $THEUSER->confirm('999999-token-does-not-exist');

        $this->assertFalse($result);
        $this->assertSame([], $THEUSER->loginCalls);
    }

    public function test_confirm_succeeds_for_existing_unconfirmed_user(): void
    {
        $uniq = (string) microtime(true);
        $email = 'confirm.unconfirmed.' . $uniq . '@example.com';
        $token = 'tokenunconfirmed' . str_replace('.', '', $uniq);
        $userId = $this->insertConfirmUser($email, $token, 0);

        $THEUSER = $this->makeTestableTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) UserModel::where('user_id', $userId)->value('confirmed'));
        $this->assertTrue($THEUSER->confirmed());
        $this->assertCount(1, $THEUSER->loginCalls);
    }

    public function test_confirm_succeeds_for_existing_already_confirmed_user(): void
    {
        $uniq = (string) microtime(true);
        $email = 'confirm.already.' . $uniq . '@example.com';
        $token = 'tokenalready' . str_replace('.', '', $uniq);
        $userId = $this->insertConfirmUser($email, $token, 1);

        $THEUSER = $this->makeTestableTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) UserModel::where('user_id', $userId)->value('confirmed'));
        $this->assertTrue($THEUSER->confirmed());
        $this->assertCount(1, $THEUSER->loginCalls);
    }

    public function test_confirm_with_constituency_confirms_matching_speaker_alert(): void
    {
        $uniq = (string) microtime(true);
        $email = 'confirm.constituency.' . $uniq . '@example.com';
        $token = 'tokenwithconst' . str_replace('.', '', $uniq);
        $constituency = 'Test Constituency Confirm ' . $uniq;
        $personId = 98765;

        $this->insertMemberForConstituency($constituency, $personId);
        $userId = $this->insertConfirmUser($email, $token, 0, $constituency);

        parlDBQuery(
            'INSERT INTO alerts (email, criteria, deleted, registrationtoken, confirmed, created, recommended) VALUES (?, ?, 0, ?, 0, NOW(), 0)',
            $email,
            'speaker:' . $personId,
            'alert-token-1'
        );

        $THEUSER = $this->makeTestableTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) parlDBQuery('SELECT confirmed FROM alerts WHERE email = ? AND criteria = ?', $email, 'speaker:' . $personId)->field(0, 'confirmed'));
    }

    public function test_confirm_without_constituency_does_not_confirm_speaker_alerts(): void
    {
        $uniq = (string) microtime(true);
        $email = 'confirm.noconstituency.' . $uniq . '@example.com';
        $token = 'tokennoconst' . str_replace('.', '', $uniq);
        $userId = $this->insertConfirmUser($email, $token, 0, '');

        parlDBQuery(
            'INSERT INTO alerts (email, criteria, deleted, registrationtoken, confirmed, created, recommended) VALUES (?, ?, 0, ?, 0, NOW(), 0)',
            $email,
            'speaker:12345',
            'alert-token-2'
        );

        $THEUSER = $this->makeTestableTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(0, (int) parlDBQuery('SELECT confirmed FROM alerts WHERE email = ? AND criteria = ?', $email, 'speaker:12345')->field(0, 'confirmed'));
    }

}
