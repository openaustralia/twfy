<?php

namespace OpenAustralia\TWFY\Auth;

/**
 * The login page, drawn by the legacy PAGE class, for Laravel's login routes to return.
 */
class LoginPage extends LegacyPage {

    /**
     * The page's HTML.
     *
     * @param array<string, list<string>> $errors
     *   Messages by form field, as a ValidationException holds them.
     * @param string|null $status
     *   A message to show above the form, such as that a password was changed.
     */
    public function render(array $errors = [], ?string $status = null): string {
        $messages = array_map(fn (array $field): string => (string) reset($field), $errors);

        return $this->drawInStripe('userlogin', function (object $page) use ($messages, $status): void {
            if ($status) {
                echo '<p><strong>' . e($status) . "</strong></p>\n";
            }
            ?>
            <p>Not yet a member? <a href="<?php echo (new \URL('userjoin'))->generate(); ?>">Join now</a>!</p>
            <?php
            $page->login_form($messages);
        }, [['type' => 'include', 'content' => 'userlogin']]);
    }

}
