<?php

/**
 * @file
 * Every user's role, and a form for Superusers to change them.
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";

use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\UserRoles;
use OpenAustralia\TWFY\UsersAdminView;

$this_page = "admin_users";

// From the query string only, as get_http_var() would also read the form's fields.
$page = max(1, (int) ($_GET['page'] ?? 1));
$role = in_array($_GET['role'] ?? '', User::ROLES, true) ? $_GET['role'] : '';

// The change is handled before page_start() so it can redirect, which means checking admin access here as well.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $THEUSER->isloggedin() && $THEUSER->is_able_to('viewadminsection')) {
    $result = UserRoles::change(
        (int) $THEUSER->user_id(),
        (string) $THEUSER->status(),
        (string) $THEUSER->password(),
        (string) ($_POST['token'] ?? ''),
        (int) ($_POST['user_id'] ?? 0),
        (string) ($_POST['new_role'] ?? '')
    );
    header('Location: ' . UsersAdminView::listLink($role, $page, ['result' => $result]));
    exit;
}

// page_start() turns away anyone who is not an admin, so the list is only built after it.
$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'users', 'title' => 'Users and their roles']);
$is_superuser = $THEUSER->isloggedin() && $THEUSER->status() === 'Superuser';
print UsersAdminView::page(
    UserRoles::page($page, $role),
    $role,
    $page,
    UserRoles::pages($role),
    $is_superuser ? (int) $THEUSER->user_id() : null,
    $is_superuser ? UserRoles::token((int) $THEUSER->user_id(), (string) $THEUSER->password()) : '',
    (string) get_http_var('result'),
    UserRoles::recentChanges()
);
$PAGE->block_end();

$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $PAGE->admin_menu(),
    ],
]);

$PAGE->page_end();
