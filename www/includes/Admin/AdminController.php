<?php

namespace OpenAustralia\TWFY\Admin;

use Illuminate\Contracts\View\View;

/**
 * What the admin pages share: the legacy page layout around a Blade view.
 *
 * A subclass fetches what a page shows and hands it to view(). Blade escapes every {{ }}, so a page never builds
 * HTML out of data.
 */
abstract class AdminController {

    /**
     * Draws a whole admin page, with the admin menu beside it.
     *
     * @param string $thisPage
     *   The legacy page key, which the layout uses for its title and the menu.
     * @param \Closure(): \Illuminate\Contracts\View\View $action
     *   Builds the view to show. It only runs once the visitor is known to be an admin.
     */
    protected function page(string $thisPage, \Closure $action): void {
        global $PAGE, $this_page;

        $this_page = $thisPage;

        // page_start() turns away anyone who is not an admin, so nothing is read before it.
        $PAGE->page_start();
        $PAGE->stripe_start();
        echo $action()->render();
        $PAGE->stripe_end([
            [
                'type' => 'html',
                'content' => $PAGE->admin_menu(),
            ],
        ]);
        $PAGE->page_end();
    }

    /**
     * A Blade view from www/resources/views.
     *
     * @param string $name
     *   The view's name, such as admin.popularsearches.
     * @param array<string, mixed> $data
     *   What the view can use.
     */
    protected function view(string $name, array $data = []): View {
        return twfy_laravel()->make('view')->make($name, $data);
    }

    /**
     * The address of an admin page, with only the parameters that have a value. Not encoded for HTML.
     *
     * @param string $page
     *   The file name in the admin folder, such as proof_year.php.
     * @param array<string, int|string|null> $params
     *   The query parameters.
     */
    protected function adminUrl(string $page, array $params = []): string {
        $query = http_build_query(array_filter($params, fn ($value): bool => $value !== null && $value !== ''));
        return '/admin/' . $page . ($query === '' ? '' : '?' . $query);
    }

    /**
     * The address of a user's page.
     */
    protected function userUrl(int $user_id): string {
        return WEBPATH . 'user/?u=' . $user_id;
    }

}
