<?php

/**
 * @file
 * The database keeps one account for each email address. Uses fictional users.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\UniqueConstraintViolationException;
use OpenAustralia\TWFY\Models\User;

uses(TransactionalTestCase::class);

/**
 * Makes an account with this address.
 */
function account_with(string $email): User {
    return User::create(['firstname' => 'Pat', 'lastname' => 'Uniquespec', 'email' => $email, 'password' => 'stored-hash']);
}

describe('users.email', function () {
    beforeEach(function () {
        $this->first = account_with('pat.uniquespec@example.test');
    });

    it('refuses a second account with the address', function () {
        expect(fn () => account_with('pat.uniquespec@example.test'))->toThrow(UniqueConstraintViolationException::class);
    });

    it('refuses it in other letters', function () {
        expect(fn () => account_with('PAT.uniquespec@example.test'))->toThrow(UniqueConstraintViolationException::class);
    });

    it('refuses it once the first account is deleted', function () {
        $this->first->update(['deleted' => true]);

        expect(fn () => account_with('pat.uniquespec@example.test'))->toThrow(UniqueConstraintViolationException::class);
    });

    it('refuses changing an account to a taken address', function () {
        $second = account_with('sam.uniquespec@example.test');

        expect(fn () => $second->update(['email' => 'pat.uniquespec@example.test']))->toThrow(UniqueConstraintViolationException::class);
    });

    it('allows a different address', function () {
        expect(account_with('sam.uniquespec@example.test')->exists)->toBeTrue();
    });
});
