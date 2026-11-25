<?php

namespace OpenAustralia\TWFY\Pages;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * What the public pages share: the site layout around a Blade view, in the front page's style.
 *
 * A subclass fetches what a page shows and hands it to view(). Blade escapes every {{ }}, so a page never builds
 * HTML out of data.
 */
abstract class PageController {

    /**
     * Draws a whole page: the site menu, the view in a padded container, and the footer.
     *
     * There is no side stripe or sidebar, so a page uses the whole width on a small screen. The page is marked
     * responsive, which gives it a viewport that fits the device.
     *
     * @param string $thisPage
     *   The legacy page key, which the layout uses for its title and the menu.
     * @param \Closure(): \Illuminate\Contracts\View\View $action
     *   Builds the view to show.
     */
    protected function page(string $thisPage, \Closure $action): void {
        global $PAGE, $DATA, $this_page;

        $this_page = $thisPage;
        $DATA->set_page_metadata($thisPage, 'responsive', true);

        $PAGE->page_start();
        echo '<div class="mx-4 pb-12 pt-6 md:mx-8">';
        echo $action()->render();
        echo '</div>';
        $PAGE->page_end();
    }

    /**
     * The request, from PHP's globals.
     */
    protected function request(): Request {
        return Request::capture();
    }

    /**
     * A value from the posted form or else the query string, as text. Anything but a single value is empty.
     */
    protected function input(string $name): string {
        $request = $this->request();
        $value = $request->post($name) ?? $request->query($name);
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * A Blade view from www/resources/views.
     *
     * @param string $name
     *   The view's name, such as glossary.index.
     * @param array<string, mixed> $data
     *   What the view can use.
     */
    protected function view(string $name, array $data = []): View {
        return twfy_laravel()->make('view')->make($name, $data);
    }

}
