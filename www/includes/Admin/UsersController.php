<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Models\UserRoleChange;
use OpenAustralia\TWFY\UserRoles;

/**
 * The admin page of every user's role, with a form for Superusers to change them.
 */
class UsersController extends AdminController {

    /**
     * What to say after a role change, by UserRoles result, and whether it went well.
     */
    private const RESULTS = [
        UserRoles::CHANGED => ['The role was changed.', true],
        UserRoles::UNCHANGED => ['That user already has that role.', true],
        UserRoles::FORBIDDEN => ['Only a Superuser can change roles.', false],
        UserRoles::OWN_ROLE => ['You can\'t change your own role.', false],
        UserRoles::BAD_TOKEN => ['The form had expired. Please try again.', false],
        UserRoles::INVALID => ['That isn\'t a user and role we can set.', false],
    ];

    /**
     * Changes a user's role when the form was posted, and says where to go next.
     *
     * Checks admin access itself, as the change happens before the page is drawn and so before PAGE::page_start()
     * turns anyone away.
     *
     * @return string|null
     *   The address to redirect to, or null when no change was posted.
     */
    public function update(): ?string {
        global $THEUSER;

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$THEUSER->isloggedin() || !$THEUSER->is_able_to('viewadminsection')) {
            return null;
        }

        $result = UserRoles::change(
            (int) $THEUSER->user_id(),
            (string) $THEUSER->status(),
            (string) $THEUSER->password(),
            (string) ($_POST['token'] ?? ''),
            (int) ($_POST['user_id'] ?? 0),
            (string) ($_POST['new_role'] ?? '')
        );
        return $this->listUrl(self::role(), self::pageNumber(), ['result' => $result]);
    }

    /**
     * The users on one page, filtered to a role, and the latest role changes.
     */
    public function index(): void {
        $this->page('admin_users', function () {
            global $THEUSER;

            $role = self::role();
            $page = self::pageNumber();
            $pages = UserRoles::pages($role);
            $superuser = $THEUSER->isloggedin() && $THEUSER->status() === 'Superuser';
            $editor_id = $superuser ? (int) $THEUSER->user_id() : null;

            $users = UserRoles::page($page, $role)->map(fn (User $user): array => [
                'id' => $user->user_id,
                'name' => self::fullName($user),
                'url' => $this->userUrl($user->user_id),
                'email' => $user->email,
                'role' => (string) $user->status,
                'is_you' => $user->user_id === $editor_id,
                'editable' => $editor_id !== null && $user->user_id !== $editor_id,
                'account' => $user->deleted ? 'Deleted' : ($user->confirmed ? '' : 'Unconfirmed'),
            ])->all();

            $changes = UserRoles::recentChanges()->map(fn (UserRoleChange $change): array => [
                'when' => (string) $change->created_at,
                'user' => $change->user === null ? null : ['name' => self::fullName($change->user), 'url' => $this->userUrl($change->user->user_id)],
                'from' => $change->from_role,
                'to' => $change->to_role,
                'by' => $change->changedBy === null ? null : ['name' => self::fullName($change->changedBy), 'url' => $this->userUrl($change->changedBy->user_id)],
            ])->all();

            $result = self::RESULTS[(string) ($_GET['result'] ?? '')] ?? null;

            return $this->view('admin.users', [
                'role' => $role,
                'roles' => User::ROLES,
                'filters' => $this->filters($role),
                'result' => $result === null ? null : ['text' => $result[0], 'good' => $result[1]],
                'can_edit' => $editor_id !== null,
                'token' => $editor_id === null ? '' : UserRoles::token($editor_id, (string) $THEUSER->password()),
                'form_url' => $this->listUrl($role, $page),
                'users' => $users,
                'page' => $page,
                'pages' => $pages,
                'previous_url' => $page > 1 ? $this->listUrl($role, $page - 1) : null,
                'next_url' => $page < $pages ? $this->listUrl($role, $page + 1) : null,
                'changes' => $changes,
            ]);
        });
    }

    /**
     * Links to show everyone, or only the users with one role.
     *
     * @return list<array{string, string, bool}>
     *   Each choice's label, address and whether it is the current one.
     */
    private function filters(string $role): array {
        $choices = [['All', $this->listUrl(''), $role === '']];
        foreach (User::ROLES as $each) {
            $choices[] = [$each, $this->listUrl($each), $role === $each];
        }
        return $choices;
    }

    /**
     * The address of the list, filtered to a role and on a page, leaving out the defaults.
     *
     * @param string $role
     *   The role to filter to, or '' for everyone.
     * @param int $page
     *   The page of the list, from 1.
     * @param array<string, string> $more
     *   More query parameters.
     */
    private function listUrl(string $role, int $page = 1, array $more = []): string {
        return $this->adminUrl('users.php', ['role' => $role, 'page' => $page > 1 ? $page : null] + $more);
    }

    /**
     * The role the list is filtered to, from the query string only, as get_http_var() would also read the form's fields.
     */
    private static function role(): string {
        return in_array($_GET['role'] ?? '', User::ROLES, true) ? $_GET['role'] : '';
    }

    /**
     * The page of the list, from 1.
     */
    private static function pageNumber(): int {
        return max(1, (int) ($_GET['page'] ?? 1));
    }

    /**
     * A user's first and last name.
     */
    private static function fullName(User $user): string {
        return trim($user->firstname . ' ' . $user->lastname);
    }

}
