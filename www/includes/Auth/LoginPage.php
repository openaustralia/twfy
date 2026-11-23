<?php

namespace OpenAustralia\TWFY\Auth;

/**
 * The login page, drawn by the legacy PAGE class, for Laravel's login routes to return.
 */
class LoginPage {

    /**
     * The page's HTML.
     *
     * @param array<string, list<string>> $errors
     *   Messages by form field, as a ValidationException holds them.
     */
    public function render(array $errors = []): string {
        global $PAGE, $this_page;

        $this_page = 'userlogin';

        $messages = array_map(fn (array $field): string => (string) reset($field), $errors);

        ob_start();
        $PAGE->page_start();
        $PAGE->stripe_start();
        ?>
        <p>Not yet a member? <a href="<?php echo (new \URL('userjoin'))->generate(); ?>">Join now</a>!</p>
        <?php
        $PAGE->login_form($messages);
        $PAGE->stripe_end([['type' => 'include', 'content' => 'userlogin']]);
        $PAGE->page_end();

        return (string) ob_get_clean();
    }

}
