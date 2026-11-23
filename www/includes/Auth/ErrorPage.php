<?php

namespace OpenAustralia\TWFY\Auth;

/**
 * An error page drawn by the legacy PAGE class, for Laravel's routes to return.
 *
 * Laravel's own error pages are Blade views, which need a compiled-views folder Apache can't write to.
 */
class ErrorPage {

    /**
     * The page for an HTTP status.
     */
    public function render(int $status): string {
        global $PAGE, $this_page;

        $this_page = 'home';

        $message = match ($status) {
            404 => ['title' => 'Page not found', 'text' => "Sorry, we couldn't find that page."],
            419 => ['title' => 'This page has expired', 'text' => 'Please go back, reload the page and try again.'],
            default => ['title' => 'Sorry, something went wrong', 'text' => 'Please try again in a moment.'],
        };

        ob_start();
        $PAGE->page_start();
        $PAGE->message($message);
        $PAGE->page_end();

        return (string) ob_get_clean();
    }

}
