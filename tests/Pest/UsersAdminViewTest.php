<?php

/**
 * @file
 * The admin Users page's HTML. Uses fictional people and no database.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Models\UserRoleChange;
use OpenAustralia\TWFY\UserRoles;
use OpenAustralia\TWFY\UsersAdminView;

/**
 * A fictional user, not saved.
 */
function users_view_spec_user(int $id, string $first, string $role): User {
    return (new User())->forceFill(['user_id' => $id, 'firstname' => $first, 'lastname' => '<Spec>', 'email' => "u$id@example.test", 'status' => $role, 'confirmed' => true, 'deleted' => false]);
}

/**
 * The page for some users, as a Superuser with id 1 or read-only.
 */
function users_view_spec_page(bool $editor, string $result = '', int $page = 1, int $pages = 1, ?Collection $changes = null, string $role = ''): string {
    $users = new Collection([users_view_spec_user(1, 'Bea', 'Superuser'), users_view_spec_user(2, 'Max', 'User')]);
    return UsersAdminView::page($users, $role, $page, $pages, $editor ? 1 : null, $editor ? 'tok&en' : '', $result, $changes ?? new Collection());
}

describe('UsersAdminView::page()', function () {
    describe('for a Superuser', function () {
        it('gives other users a form', function () {
            expect(users_view_spec_page(true))->toContain('<input type="hidden" name="user_id" value="2">');
        });

        it('gives no form for their own row', function () {
            expect(users_view_spec_page(true))->not->toContain('name="user_id" value="1"');
        });

        it('marks their own row', function () {
            expect(users_view_spec_page(true))->toContain('(you)');
        });

        it('offers every role', function () {
            expect(substr_count(users_view_spec_page(true), '<option'))->toBe(count(User::ROLES));
        });

        it('selects the current role', function () {
            expect(users_view_spec_page(true))->toContain('<option selected>User</option>');
        });

        it('escapes the token', function () {
            expect(users_view_spec_page(true))->toContain('value="tok&amp;en"');
        });
    });

    describe('for an Administrator', function () {
        it('shows no forms', function () {
            expect(users_view_spec_page(false))->not->toContain('<form');
        });

        it('shows the roles', function () {
            expect(users_view_spec_page(false))->toContain('bg-violet-100 text-violet-800">Superuser</span>');
        });
    });

    it('escapes names', function () {
        expect(users_view_spec_page(false))->toContain('Max &lt;Spec&gt;</a>');
    });

    describe('with one page', function () {
        it('has no pager', function () {
            expect(users_view_spec_page(false))->not->toContain('Pages of users');
        });
    });

    describe('in the middle of several pages', function () {
        beforeEach(function () {
            $this->html = users_view_spec_page(false, '', 2, 3);
        });

        it('links back', function () {
            expect($this->html)->toContain('href="/admin/users.php">&larr; Previous</a>');
        });

        it('links on', function () {
            expect($this->html)->toContain('href="/admin/users.php?page=3">Next &rarr;</a>');
        });
    });

    describe('without changes', function () {
        it('says so', function () {
            expect(users_view_spec_page(false))->toContain('No roles have been changed here yet.');
        });
    });

    describe('with a change', function () {
        it('shows the roles either side', function () {
            $change = (new UserRoleChange())->forceFill(['from_role' => 'User', 'to_role' => 'Administrator', 'created_at' => '2026-11-23 01:02:03']);
            $change->setRelation('user', users_view_spec_user(2, 'Max', 'Administrator'));
            $change->setRelation('changedBy', users_view_spec_user(1, 'Bea', 'Superuser'));

            expect(users_view_spec_page(false, '', 1, 1, new Collection([$change])))
              ->toContain('User &rarr; <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold bg-teal-100 text-teal-800">Administrator</span>');
        });
    });
});

describe('UsersAdminView::roleFilter()', function () {
    it('offers everyone and each role', function () {
        expect(substr_count(UsersAdminView::roleFilter(''), '<li '))->toBe(count(User::ROLES) + 1);
    });

    it('marks the role shown', function () {
        expect(UsersAdminView::roleFilter('Moderator'))->toContain('aria-current="page">Moderator</li>');
    });

    it('links back to everyone', function () {
        expect(UsersAdminView::roleFilter('Moderator'))->toContain('href="/admin/users.php">All</a>');
    });
});

describe('UsersAdminView::listLink()', function () {
    it('leaves out the defaults', function () {
        expect(UsersAdminView::listLink(''))->toBe('/admin/users.php');
    });

    it('keeps the role and page', function () {
        expect(UsersAdminView::listLink('Superuser', 2, ['result' => 'changed']))->toBe('/admin/users.php?role=Superuser&page=2&result=changed');
    });
});

describe('UsersAdminView::page() filtered to a role', function () {
    beforeEach(function () {
        $this->html = users_view_spec_page(true, '', 2, 3, null, 'User');
    });

    it('posts back to the same list', function () {
        expect($this->html)->toContain('action="/admin/users.php?role=User&amp;page=2"');
    });

    it('keeps the role when paging', function () {
        expect($this->html)->toContain('href="/admin/users.php?role=User&amp;page=3">Next');
    });
});

describe('UsersAdminView::result()', function () {
    it('confirms a change', function () {
        expect(UsersAdminView::result(UserRoles::CHANGED))->toContain('bg-teal-100 text-teal-800">The role was changed.</p>');
    });

    it('warns of a refusal', function () {
        expect(UsersAdminView::result(UserRoles::OWN_ROLE))->toContain('bg-rose-100 text-rose-800');
    });

    it('ignores an unknown result', function () {
        expect(UsersAdminView::result('<script>'))->toBe('');
    });
});

describe('UsersAdminView::pill()', function () {
    it('leaves ordinary roles plain', function () {
        expect(UsersAdminView::pill('Viewer'))->toBe('Viewer');
    });
});
