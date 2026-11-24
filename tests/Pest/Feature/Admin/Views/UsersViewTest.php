<?php

/**
 * @file
 * The admin users view.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../../Support/ViewTestCase.php';

use OpenAustralia\TWFY\Models\User;

uses(ViewTestCase::class);

/**
 * What the view shows, for a Superuser with id 1 looking at themselves and one other user, or read-only.
 *
 * @param array<string, mixed> $overrides
 *   Data to change.
 */
function users_view_data(array $overrides = []): array {
    $can_edit = $overrides['can_edit'] ?? true;
    return $overrides + [
        'role' => '',
        'roles' => User::ROLES,
        'filters' => [['All', '/admin/users.php', true], ['Moderator', '/admin/users.php?role=Moderator', false]],
        'result' => null,
        'can_edit' => $can_edit,
        'token' => 'tok&en',
        'form_url' => '/admin/users.php?role=User&page=2',
        'users' => [
            ['id' => 1, 'name' => 'Bea <Spec>', 'url' => '/user/?u=1', 'email' => 'bea@example.test', 'role' => 'Superuser', 'is_you' => $can_edit, 'editable' => false, 'account' => ''],
            ['id' => 2, 'name' => 'Max <Spec>', 'url' => '/user/?u=2', 'email' => 'max@example.test', 'role' => 'User', 'is_you' => false, 'editable' => $can_edit, 'account' => 'Unconfirmed'],
        ],
        'page' => 1,
        'pages' => 1,
        'previous_url' => null,
        'next_url' => null,
        'changes' => [],
    ];
}

describe('admin.users for a Superuser', function () {
    beforeEach(function () {
        $this->view = $this->view('admin.users', users_view_data());
    });

    it('gives other users a form', function () {
        $this->view->assertSee('<input type="hidden" name="user_id" value="2">', false);
    });

    it('gives no form for their own row', function () {
        $this->view->assertDontSee('name="user_id" value="1"', false);
    });

    it('marks their own row', function () {
        $this->view->assertSee('(you)');
    });

    it('selects the current role', function () {
        $this->view->assertSee('<option selected>User</option>', false);
    });

    it('escapes the token', function () {
        $this->view->assertSee('value="tok&amp;en"', false);
    });

    it('posts back to the same list', function () {
        $this->view->assertSee('action="/admin/users.php?role=User&amp;page=2"', false);
    });

    it('escapes names', function () {
        $this->view->assertSee('Max &lt;Spec&gt;', false);
    });

    it('shows an unconfirmed account', function () {
        $this->view->assertSee('Unconfirmed');
    });
});

describe('admin.users when read-only', function () {
    beforeEach(function () {
        $this->view = $this->view('admin.users', users_view_data(['can_edit' => false]));
    });

    it('shows no forms', function () {
        $this->view->assertDontSee('<form', false);
    });

    it('shows a staff role as a pill', function () {
        $this->view->assertSee('bg-violet-100 text-violet-800', false);
    });

    it('leaves an ordinary role plain', function () {
        $this->view->assertSee('>User</td>', false);
    });

    it('says only a Superuser can change roles', function () {
        $this->view->assertSee('Only a Superuser can change roles.');
    });
});

describe('admin.users with a result', function () {
    it('confirms a change in teal', function () {
        $this->view('admin.users', users_view_data(['result' => ['text' => 'The role was changed.', 'good' => true]]))
          ->assertSee('bg-teal-100 text-teal-800">The role was changed.</p>', false);
    });

    it('warns of a refusal in rose', function () {
        $this->view('admin.users', users_view_data(['result' => ['text' => 'No.', 'good' => false]]))
          ->assertSee('bg-rose-100 text-rose-800', false);
    });
});

describe('admin.users with one page', function () {
    it('has no pager', function () {
        $this->view('admin.users', users_view_data())->assertDontSee('Pages of users');
    });
});

describe('admin.users in the middle of several pages', function () {
    beforeEach(function () {
        $this->view = $this->view('admin.users', users_view_data(['page' => 2, 'pages' => 3, 'previous_url' => '/admin/users.php', 'next_url' => '/admin/users.php?page=3']));
    });

    it('links back', function () {
        $this->view->assertSee('href="/admin/users.php">&larr; Previous</a>', false);
    });

    it('links on', function () {
        $this->view->assertSee('href="/admin/users.php?page=3">Next &rarr;</a>', false);
    });
});

describe('admin.users filters', function () {
    it('marks the current choice', function () {
        $this->view('admin.users', users_view_data())->assertSee('aria-current="page">All</li>', false);
    });

    it('links to the others', function () {
        $this->view('admin.users', users_view_data())->assertSee('href="/admin/users.php?role=Moderator">Moderator</a>', false);
    });
});

describe('admin.users with no users', function () {
    it('says there are none', function () {
        $this->view('admin.users', users_view_data(['users' => []]))->assertSee('There are no users.');
    });

    it('says none have the role when filtered', function () {
        $this->view('admin.users', users_view_data(['users' => [], 'role' => 'Moderator']))->assertSee('No users have this role.');
    });
});

describe('admin.users role changes', function () {
    it('says there are none', function () {
        $this->view('admin.users', users_view_data())->assertSee('No roles have been changed here yet.');
    });

    it('shows the roles either side', function () {
        $change = ['when' => '2026-11-23 01:02:03', 'user' => ['name' => 'Max', 'url' => '/user/?u=2'], 'from' => 'User', 'to' => 'Administrator', 'by' => null];

        $this->view('admin.users', users_view_data(['changes' => [$change]]))->assertSee('User &rarr;', false);
    });

    it('names an unknown user', function () {
        $change = ['when' => '2026-11-23 01:02:03', 'user' => null, 'from' => 'User', 'to' => 'Moderator', 'by' => null];

        $this->view('admin.users', users_view_data(['changes' => [$change]]))->assertSee('Unknown user');
    });
});
