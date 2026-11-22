<?php

/**
 * @file
 * Listing users and changing their roles. Uses fictional people.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Models\UserRoleChange;
use OpenAustralia\TWFY\UserRoles;

uses(TransactionalTestCase::class);

/**
 * Creates a fictional user with a role.
 */
function user_roles_spec_user(string $first, string $role, string $hash = 'hash-of-a-password'): User {
    return User::create(['firstname' => $first, 'lastname' => 'Aaa-rolespec', 'email' => strtolower($first) . '@example.test', 'status' => $role, 'password' => $hash]);
}

beforeEach(function () {
    $this->boss = user_roles_spec_user('Bea', 'Superuser');
    $this->admin = user_roles_spec_user('Ari', 'Administrator');
    $this->member = user_roles_spec_user('Max', 'User');
    $this->change = fn (User $actor, int $user_id, string $role, ?string $token = null): string => UserRoles::change(
        $actor->user_id,
        $actor->status,
        $actor->password,
        $token ?? UserRoles::token($actor->user_id, $actor->password),
        $user_id,
        $role
    );
});

describe('UserRoles::change()', function () {
    describe('by a Superuser', function () {
        it('says the role changed', function () {
            expect(($this->change)($this->boss, $this->member->user_id, 'Administrator'))->toBe(UserRoles::CHANGED);
        });

        it('changes the role', function () {
            ($this->change)($this->boss, $this->member->user_id, 'Administrator');

            expect(User::find($this->member->user_id)->status)->toBe('Administrator');
        });

        it('records who changed what', function () {
            ($this->change)($this->boss, $this->member->user_id, 'Moderator');

            expect(UserRoleChange::where('user_id', $this->member->user_id)->first()->only(['changed_by', 'from_role', 'to_role']))
              ->toBe(['changed_by' => $this->boss->user_id, 'from_role' => 'User', 'to_role' => 'Moderator']);
        });

        it('can revoke a role', function () {
            ($this->change)($this->boss, $this->admin->user_id, 'User');

            expect(User::find($this->admin->user_id)->status)->toBe('User');
        });
    });

    describe('with the role it already has', function () {
        it('records nothing', function () {
            ($this->change)($this->boss, $this->member->user_id, 'User');

            expect(UserRoleChange::where('user_id', $this->member->user_id)->count())->toBe(0);
        });
    });

    describe('by an Administrator', function () {
        it('is refused', function () {
            expect(($this->change)($this->admin, $this->member->user_id, 'Superuser'))->toBe(UserRoles::FORBIDDEN);
        });
    });

    describe('of your own role', function () {
        it('is refused', function () {
            expect(($this->change)($this->boss, $this->boss->user_id, 'User'))->toBe(UserRoles::OWN_ROLE);
        });
    });

    describe('with a wrong token', function () {
        it('is refused', function () {
            expect(($this->change)($this->boss, $this->member->user_id, 'Superuser', 'forged'))->toBe(UserRoles::BAD_TOKEN);
        });

        it('leaves the role alone', function () {
            ($this->change)($this->boss, $this->member->user_id, 'Superuser', 'forged');

            expect(User::find($this->member->user_id)->status)->toBe('User');
        });
    });

    describe('by a user with no password hash', function () {
        it('is refused', function () {
            $blank = user_roles_spec_user('Nia', 'Superuser', '');

            expect(($this->change)($blank, $this->member->user_id, 'Superuser'))->toBe(UserRoles::BAD_TOKEN);
        });
    });

    describe('with an unknown role or user', function () {
        it('is refused', function (string $role, bool $known_user) {
            expect(($this->change)($this->boss, $known_user ? $this->member->user_id : 0, $role))->toBe(UserRoles::INVALID);
        })->with([
            'a made-up role' => ['Overlord', true],
            'no such user' => ['Moderator', false],
        ]);
    });
});

describe('UserRoles::token()', function () {
    it('differs between users', function () {
        expect(UserRoles::token(1, 'same-hash'))->not->toBe(UserRoles::token(2, 'same-hash'));
    });

    it('changes with the password', function () {
        expect(UserRoles::token(1, 'old-hash'))->not->toBe(UserRoles::token(1, 'new-hash'));
    });
});

describe('UserRoles::page()', function () {
    it('orders users by name', function () {
        $names = UserRoles::page(1)->where('lastname', 'Aaa-rolespec')->pluck('firstname')->all();

        expect($names)->toBe(['Ari', 'Bea', 'Max']);
    });

    it('leaves out password hashes', function () {
        expect(UserRoles::page(1)->first()->getAttributes())->not->toHaveKey('password');
    });
});

describe('UserRoles::page() for one role', function () {
    it('lists only that role', function () {
        expect(UserRoles::page(1, 'Superuser')->pluck('status')->unique()->all())->toBe(['Superuser']);
    });

    describe('with an unknown role', function () {
        it('lists everyone', function () {
            expect(UserRoles::page(1, 'Overlord')->count())->toBe(UserRoles::page(1)->count());
        });
    });
});

describe('UserRoles::pages()', function () {
    it('counts pages of 100', function () {
        expect(UserRoles::pages())->toBe(max(1, (int) ceil(User::count() / 100)));
    });
});

describe('UserRoles::recentChanges()', function () {
    beforeEach(function () {
        ($this->change)($this->boss, $this->member->user_id, 'Moderator');
        ($this->change)($this->boss, $this->member->user_id, 'Administrator');
        $this->latest = UserRoles::recentChanges()->first();
    });

    it('puts the newest first', function () {
        expect($this->latest->to_role)->toBe('Administrator');
    });

    it('loads who made it', function () {
        expect($this->latest->changedBy->firstname)->toBe('Bea');
    });
});
