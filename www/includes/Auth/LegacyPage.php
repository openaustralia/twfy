<?php

namespace OpenAustralia\TWFY\Auth;

/**
 * The page-drawing steps the login, password and error pages share, around the legacy PAGE class.
 */
abstract class LegacyPage {

    /**
     * Draws a page and returns its HTML.
     *
     * @param string $thisPage
     *   The legacy page key, which the layout uses for its menus.
     * @param callable(object): void $body
     *   Prints what goes inside the page. It gets the PAGE.
     */
    protected function draw(string $thisPage, callable $body): string {
        global $PAGE, $this_page;

        $this_page = $thisPage;

        ob_start();
        $PAGE->page_start();
        $body($PAGE);
        $PAGE->page_end();

        return (string) ob_get_clean();
    }

    /**
     * Draws a page whose body sits in the legacy content stripe, and returns its HTML.
     *
     * @param string $thisPage
     *   The legacy page key.
     * @param callable(object): void $body
     *   Prints what goes inside the stripe. It gets the PAGE.
     * @param array<int, array<string, string>> $sidebar
     *   What goes beside the stripe.
     */
    protected function drawInStripe(string $thisPage, callable $body, array $sidebar = []): string {
        return $this->draw($thisPage, function (object $page) use ($body, $sidebar): void {
            $page->stripe_start();
            $body($page);
            $page->stripe_end($sidebar);
        });
    }

}
