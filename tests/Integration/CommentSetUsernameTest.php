<?php

/**
 * @file
 * Tests for COMMENT::_set_username() which uses the User model.
 */

use OpenAustralia\TWFY\Models\User;

if (!defined('ALLOWCOMMENTS')) {
    define('ALLOWCOMMENTS', false);
}

include_once EASYPARLIAMENTPATH . 'comment.php';

/**
 * Tests for COMMENT::_set_username() ORM conversion.
 */
class CommentSetUsernameTest extends TransactionalTestCase {

    private function insertUser(int $user_id, string $firstname, string $lastname): void {
        User::query()->insert([
            'user_id' => $user_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => "test{$user_id}@example.com",
            'password' => 'hashed',
            'confirmed' => 1,
            'registrationtime' => '2024-01-01 00:00:00',
        ]);
    }

    public function test_set_username_populates_names(): void {
        $this->insertUser(70001, 'Alice', 'Smith');

        $comment = new COMMENT();
        $comment->user_id = 70001;
        $comment->_set_username();

        $this->assertEquals('Alice', $comment->firstname());
        $this->assertEquals('Smith', $comment->lastname());
    }

    public function test_set_username_does_nothing_for_missing_user(): void {
        $comment = new COMMENT();
        $comment->user_id = 99999;
        $comment->_set_username();

        $this->assertEquals('', $comment->firstname());
        $this->assertEquals('', $comment->lastname());
    }

    public function test_set_username_skips_when_already_set(): void {
        $this->insertUser(70002, 'Bob', 'Jones');

        $comment = new COMMENT();
        $comment->user_id = 70002;
        $comment->firstname = 'Existing';
        $comment->lastname = 'Name';
        $comment->_set_username();

        $this->assertEquals('Existing', $comment->firstname());
        $this->assertEquals('Name', $comment->lastname());
    }

}
