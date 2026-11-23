<?php

/**
 * @file
 */

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

        /**
         *
         */
public function insert(array $params): void {
            $this->params = array_merge($this->params, $params);
}

        /**
         *
         */
public function generate(): string {
            return '/' . $this->page;
}

    }
}

if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.org');
}

use OpenAustralia\TWFY\EmailSuppressions;
use OpenAustralia\TWFY\Models\EmailSuppression;
use OpenAustralia\TWFY\Models\Member as MemberModel;
use OpenAustralia\TWFY\Models\User as UserModel;

/**
 * Test helper that suppresses side effects from THEUSER::login while capturing call count.
 */
class MockTheUser extends THEUSER {

    private int $loginCallCount = 0;

    /**
     *
     */
public function login(string $returl = ''): void {
    $this->loginCallCount++;
}

    /**
     *
     */
public function loginCallCount(): int {
    return $this->loginCallCount;
}

}

/**
 * Integration tests for USER/THEUSER methods.
 */
class UserIntegrationTest extends TransactionalTestCase {

    /** @var string[] */
    private array $createdEmails = [];
    /** @var int[] */
    private array $createdMemberIds = [];

    /**
     *
     */
protected function useMysqliTransaction(): bool {
        return false;
}

    /**
     *
     */
protected function useEloquentTransaction(): bool {
        return false;
}

    /**
     *
     */
protected function tearDown(): void {
        if ($this->createdEmails !== []) {
            parlDBQuery('DELETE FROM alerts WHERE email IN (' . implode(',', array_fill(0, count($this->createdEmails), '?')) . ')', ...$this->createdEmails);
            UserModel::whereIn('email', $this->createdEmails)->delete();
            // This class turns transactions off, so remove suppression rows
            // that tests leave behind.
            EmailSuppression::whereIn('email', array_map('strtolower', $this->createdEmails))->delete();
        }
        if ($this->createdMemberIds !== []) {
            MemberModel::whereIn('member_id', $this->createdMemberIds)->delete();
        }
        $this->createdEmails = [];
        $this->createdMemberIds = [];
        parent::tearDown();
}

    /**
     * Insert a user suitable for THEUSER::confirm() tests.
     */
    private function insertConfirmUser(
      string $email,
      string $registrationtoken,
      int $confirmed = 0,
      string $constituency = '',
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
    private function makeMockTheUser(): MockTheUser {
        return new MockTheUser();
    }

    // =========================================================================
    // confirm() tests

    /**
     * =========================================================================
     */
    public function test_confirm_returns_false_when_user_does_not_exist(): void {
        $THEUSER = $this->makeMockTheUser();

        $result = $THEUSER->confirm('999999-token-does-not-exist');

        $this->assertFalse($result);
        $this->assertSame(0, $THEUSER->loginCallCount());
    }

    /**
     *
     */
public function test_confirm_succeeds_for_existing_unconfirmed_user(): void {
        $uniq = (string) microtime(true);
        $email = 'confirm.unconfirmed.' . $uniq . '@example.com';
        $token = substr(sha1('unconfirmed-' . $uniq), 0, 16);
        $userId = $this->insertConfirmUser($email, $token, 0);

        $THEUSER = $this->makeMockTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) UserModel::where('user_id', $userId)->value('confirmed'));
        $this->assertTrue($THEUSER->confirmed());
        $this->assertSame(1, $THEUSER->loginCallCount());
}

    /**
     * Confirmation tokens survive email changes, so following one is not
     * fresh proof that the address receives mail.
     */
    public function test_confirm_leaves_a_suppression_on_the_users_address_in_place(): void {
        $uniq = (string) microtime(true);
        $email = 'confirm.suppressed.' . $uniq . '@example.com';
        $token = substr(sha1('suppressed-' . $uniq), 0, 16);
        $userId = $this->insertConfirmUser($email, $token, 0);
        EmailSuppressions::suppress($email, 'hard_bounce');

        $THEUSER = $this->makeMockTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) UserModel::where('user_id', $userId)->value('confirmed'));
        $this->assertTrue(EmailSuppressions::isSuppressed($email));
    }

    /**
     *
     */
public function test_confirm_succeeds_for_existing_already_confirmed_user(): void {
        $uniq = (string) microtime(true);
        $email = 'confirm.already.' . $uniq . '@example.com';
        $token = substr(sha1('already-' . $uniq), 0, 16);
        $userId = $this->insertConfirmUser($email, $token, 1);

        $THEUSER = $this->makeMockTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) UserModel::where('user_id', $userId)->value('confirmed'));
        $this->assertTrue($THEUSER->confirmed());
        $this->assertSame(1, $THEUSER->loginCallCount());
}

    /**
     *
     */
public function test_confirm_with_constituency_confirms_matching_speaker_alert(): void {
        $uniq = (string) microtime(true);
        $email = 'confirm.constituency.' . $uniq . '@example.com';
        $token = substr(sha1('constituency-' . $uniq), 0, 16);
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

        $THEUSER = $this->makeMockTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(1, (int) parlDBQuery('SELECT confirmed FROM alerts WHERE email = ? AND criteria = ?', $email, 'speaker:' . $personId)->field(0, 'confirmed'));
}

    /**
     *
     */
public function test_confirm_without_constituency_does_not_confirm_speaker_alerts(): void {
        $uniq = (string) microtime(true);
        $email = 'confirm.noconstituency.' . $uniq . '@example.com';
        $token = substr(sha1('noconstituency-' . $uniq), 0, 16);
        $userId = $this->insertConfirmUser($email, $token, 0, '');

        parlDBQuery(
            'INSERT INTO alerts (email, criteria, deleted, registrationtoken, confirmed, created, recommended) VALUES (?, ?, 0, ?, 0, NOW(), 0)',
            $email,
            'speaker:12345',
            'alert-token-2'
        );

        $THEUSER = $this->makeMockTheUser();
        $result = $THEUSER->confirm($userId . '-' . $token);

        $this->assertNotFalse($result);
        $this->assertSame(0, (int) parlDBQuery('SELECT confirmed FROM alerts WHERE email = ? AND criteria = ?', $email, 'speaker:12345')->field(0, 'confirmed'));
}

}
