<?php

/**
 * @file
 * The admin users page and role changes, from the controller to the HTML. Uses fictional people.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';

use OpenAustralia\TWFY\Admin\UsersController;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Models\UserRoleChange;
use OpenAustralia\TWFY\UserRoles;

uses(AdminControllerTestCase::class);

/**
 * Creates a fictional user with a role.
 */
function users_page_user(string $first, string $role): User {
    return User::create(['firstname' => $first, 'lastname' => '<Aaa-pagespec>', 'email' => strtolower($first) . '@example.test', 'status' => $role, 'password' => 'password-hash', 'confirmed' => 1]);
}

describe('UsersController::index()', function () {
    beforeEach(function () {
        $this->boss = users_page_user('Bea', 'Superuser');
        $this->member = users_page_user('Max', 'User');
        $this->signInAs('Superuser', $this->boss->user_id);
    });

    describe('for a Superuser', function () {
        beforeEach(function () {
            $this->html = $this->render(fn () => (new UsersController())->index());
        });

        it('draws the page around the list', function () {
            expect($this->html)->toStartWith('[page_start][stripe_start]');
        });

        it('gives another user a form', function () {
            expect($this->html)->toContain('<input type="hidden" name="user_id" value="' . $this->member->user_id . '">');
        });

        it('gives no form for their own row', function () {
            expect($this->html)->not->toContain('name="user_id" value="' . $this->boss->user_id . '"');
        });

        it('escapes names', function () {
            expect($this->html)->toContain('Max &lt;Aaa-pagespec&gt;');
        });

        it('sets the page key for the menu', function () {
            expect($GLOBALS['this_page'])->toBe('admin_users');
        });
    });

    describe('filtered to a role', function () {
        it('leaves out the other roles', function () {
            $_GET['role'] = 'Superuser';

            $html = $this->render(fn () => (new UsersController())->index());

            expect($html)->not->toContain('Max &lt;Aaa-pagespec&gt;');
        });
    });

    describe('after a change', function () {
        it('says so', function () {
            $_GET['result'] = UserRoles::CHANGED;

            $html = $this->render(fn () => (new UsersController())->index());

            expect($html)->toContain('The role was changed.');
        });
    });

    describe('with a role change on record', function () {
        it('lists it', function () {
            UserRoleChange::create(['user_id' => $this->member->user_id, 'changed_by' => $this->boss->user_id, 'from_role' => 'User', 'to_role' => 'Moderator']);

            $html = $this->render(fn () => (new UsersController())->index());

            expect($html)->toContain('Moderator');
        });
    });
});

describe('UsersController::update()', function () {
    beforeEach(function () {
        $this->boss = users_page_user('Bea', 'Superuser');
        $this->member = users_page_user('Max', 'User');
        $this->signInAs('Superuser', $this->boss->user_id);
    });

    describe('without a posted form', function () {
        it('does nothing', function () {
            expect((new UsersController())->update())->toBeNull();
        });
    });

    describe('with a posted form', function () {
        beforeEach(function () {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['token' => UserRoles::token($this->boss->user_id, 'password-hash'), 'user_id' => (string) $this->member->user_id, 'new_role' => 'Moderator'];
        });

        it('changes the role', function () {
            (new UsersController())->update();

            expect(User::find($this->member->user_id)->status)->toBe('Moderator');
        });

        it('goes back to the list with the result', function () {
            expect((new UsersController())->update())->toBe('/admin/users.php?result=changed');
        });

        it('keeps the role filter and page', function () {
            $_GET = ['role' => 'User', 'page' => '2'];

            expect((new UsersController())->update())->toBe('/admin/users.php?role=User&page=2&result=changed');
        });
    });
});
