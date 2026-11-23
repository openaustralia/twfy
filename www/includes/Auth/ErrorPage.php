<?php

namespace OpenAustralia\TWFY\Auth;

/**
 * An error page drawn by the legacy PAGE class, for Laravel's routes to return.
 *
 * Laravel's own error pages are Blade views, which need a compiled-views folder Apache can't write to.
 */
class ErrorPage extends LegacyPage {

    /**
     * The page for an HTTP status.
     */
    public function render(int $status): string {
        $message = match ($status) {
            404 => ['title' => 'Page not found', 'text' => "Sorry, we couldn't find that page."],
            419 => ['title' => 'This page has expired', 'text' => 'Please go back, reload the page and try again.'],
            default => ['title' => 'Sorry, something went wrong', 'text' => 'Please try again in a moment.'],
        };

        return $this->draw('home', fn (object $page) => $page->message($message));
    }

}
