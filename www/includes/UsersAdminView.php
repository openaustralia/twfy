<?php

namespace OpenAustralia\TWFY;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Models\UserRoleChange;

/**
 * The HTML of the admin Users page: every user's role, a form to change it for Superusers, and recent changes.
 */
class UsersAdminView {

    /**
     * The pill for each role that can open or moderate the site. Viewer and User get none.
     */
    private const PILLS = [
        'Superuser' => 'bg-violet-100 text-violet-800',
        'Administrator' => 'bg-teal-100 text-teal-800',
        'Moderator' => 'bg-amber-100 text-amber-800',
    ];

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
     * The page body.
     *
     * @param \Illuminate\Support\Collection<int, User> $users
     *   One page of users.
     * @param string $role
     *   The role the list is filtered to, or '' for everyone.
     * @param int $page
     *   The page shown, from 1.
     * @param int $pages
     *   How many pages there are.
     * @param int|null $editor_id
     *   The signed-in Superuser, who gets a form on every row but their own, or null for a read-only list.
     * @param string $token
     *   The token for the forms, from UserRoles::token().
     * @param string $result
     *   The result of the last change, from UserRoles, or ''.
     * @param \Illuminate\Support\Collection<int, UserRoleChange> $changes
     *   Recent changes, newest first.
     */
    public static function page(Collection $users, string $role, int $page, int $pages, ?int $editor_id, string $token, string $result, Collection $changes): string {
        $html = AdminView::breadcrumbs([['Admin', '/admin/'], ['Users', null]]);
        $html .= self::roleFilter($role);
        $html .= '<p class="' . AdminView::NOTE_CLASSES . ' max-w-3xl">Administrators and Superusers can open the admin pages, and Moderators can moderate comments. '
            . ($editor_id === null ? 'Only a Superuser can change roles.' : 'Nobody can change their own role, so there is always a Superuser.') . '</p>';
        $html .= self::result($result);

        $rows = $users->map(fn (User $user): array => [
            self::name($user),
            htmlspecialchars($user->email),
            $editor_id === null || $user->user_id === $editor_id ? self::pill((string) $user->status) . ($user->user_id === $editor_id ? ' <span class="text-xs text-slate-500">(you)</span>' : '') : self::form($user, $role, $page, $token),
            $user->deleted ? 'Deleted' : ($user->confirmed ? '' : 'Unconfirmed'),
        ])->all();
        $html .= AdminView::table(['Name', 'Email', 'Role', 'Account'], $rows, $role === '' ? 'There are no users.' : 'No users have this role.');
        $html .= self::pager($role, $page, $pages);

        $html .= '<h3 class="' . AdminView::HEADING_CLASSES . '">Recent role changes</h3>';
        $changed = $changes->map(fn (UserRoleChange $change): array => [
            htmlspecialchars((string) $change->created_at),
            $change->user === null ? 'Unknown user' : self::name($change->user),
            self::pill($change->from_role) . ' &rarr; ' . self::pill($change->to_role),
            $change->changedBy === null ? 'Unknown user' : self::name($change->changedBy),
        ])->all();
        return $html . AdminView::table(['When (UTC)', 'User', 'Role', 'Changed by'], $changed, 'No roles have been changed here yet.');
    }

    /**
     * A role, in a pill if it is a staff role.
     */
    public static function pill(string $role): string {
        $role_html = htmlspecialchars($role);
        return isset(self::PILLS[$role])
            ? '<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ' . self::PILLS[$role] . '">' . $role_html . '</span>'
            : $role_html;
    }

    /**
     * The message after a change, or '' when there was none.
     */
    public static function result(string $result): string {
        if (!isset(self::RESULTS[$result])) {
            return '';
        }
        [$text, $good] = self::RESULTS[$result];
        return '<p role="status" class="max-w-4xl rounded px-3 py-2 text-sm ' . ($good ? 'bg-teal-100 text-teal-800' : 'bg-rose-100 text-rose-800') . '">' . htmlspecialchars($text) . '</p>';
    }

    /**
     * Links to show everyone, or only the users with one role.
     */
    public static function roleFilter(string $role): string {
        $choices = [['All', self::listLink(''), $role === '']];
        foreach (User::ROLES as $each) {
            $choices[] = [$each, self::listLink($each), $role === $each];
        }
        return '<div class="mb-4">' . AdminView::filterLinks($choices) . '</div>';
    }

    /**
     * The address of the list, filtered to a role and on a page, leaving out the defaults. Not escaped.
     */
    public static function listLink(string $role, int $page = 1, array $more = []): string {
        return AdminView::link('users.php', ['role' => $role, 'page' => $page > 1 ? $page : null] + $more);
    }

    /**
     * The form to change one user's role. It posts back to the list the user is looking at.
     */
    private static function form(User $user, string $role, int $page, string $token): string {
        $html = '<form method="post" action="' . htmlspecialchars(self::listLink($role, $page)) . '" class="flex items-center gap-2 !m-0">'
            . '<input type="hidden" name="user_id" value="' . $user->user_id . '">'
            . '<input type="hidden" name="token" value="' . htmlspecialchars($token) . '">'
            . '<select name="new_role" aria-label="Role for ' . htmlspecialchars(self::fullName($user)) . '" class="rounded border border-solid border-slate-300 bg-white px-2 py-1 text-sm">';
        foreach (User::ROLES as $role) {
            $html .= '<option' . ($role === $user->status ? ' selected' : '') . '>' . $role . '</option>';
        }
        return $html . '</select><button type="submit" class="rounded bg-teal-700 px-3 py-1 text-xs font-semibold !text-white hover:bg-teal-600">Save</button></form>';
    }

    /**
     * Links to the pages before and after this one.
     */
    private static function pager(string $role, int $page, int $pages): string {
        if ($pages <= 1) {
            return '';
        }
        $link = fn (int $to, string $label): string => '<a class="' . AdminView::LINK_CLASSES . '" href="' . htmlspecialchars(self::listLink($role, $to)) . '">' . $label . '</a>';
        return '<nav aria-label="Pages of users" class="flex max-w-4xl items-center justify-between gap-6 mt-4 text-sm font-semibold">'
            . ($page > 1 ? $link($page - 1, '&larr; Previous') : '<span></span>')
            . '<span class="text-slate-500">Page ' . $page . ' of ' . $pages . '</span>'
            . ($page < $pages ? $link($page + 1, 'Next &rarr;') : '<span></span>')
            . '</nav>';
    }

    /**
     * A user's name, linked to their profile.
     */
    private static function name(User $user): string {
        return '<a class="' . AdminView::LINK_CLASSES . '" href="/user/?u=' . $user->user_id . '">' . htmlspecialchars(self::fullName($user)) . '</a>';
    }

    /**
     * A user's first and last name.
     */
    private static function fullName(User $user): string {
        return trim($user->firstname . ' ' . $user->lastname);
    }

}
