<?php

namespace OpenAustralia\TWFY\Auth;

use Illuminate\Http\Request;

/**
 * The password reset pages, drawn by the legacy PAGE class, for Fortify's reset routes to return.
 */
class PasswordPages {

    /**
     * The form for asking for a reset link.
     */
    public function requestLink(Request $request): string {
        $status = $request->session()->get('status');
        $email = (string) $request->old('email', $request->query('email', ''));

        return $this->draw(function () use ($request, $status, $email): void {
            if ($status) {
                echo '<p><strong>' . e($status) . "</strong></p>\n";
            } else {
                echo "<p>If you can't remember your password we can email you a link to choose a new one.</p>\n";
            }
            ?>
            <form method="post" action="<?php echo e(route('password.email')); ?>">
                <?php echo $this->tokenField($request); ?>
                <?php $this->error($request, 'email'); ?>
                <div class="row">
                    <span class="label"><label for="email">Email address:</label></span>
                    <span class="formw"><input type="text" name="email" id="email" value="<?php echo e($email); ?>" maxlength="100" size="30" class="form"></span>
                </div>
                <div class="row">
                    <span class="label">&nbsp;</span>
                    <span class="formw"><input type="submit" value="Email me a link" class="submit"></span>
                </div>
            </form>
            <?php
        });
    }

    /**
     * The form for choosing a new password, from the link in the reset email.
     */
    public function resetForm(Request $request): string {
        $email = (string) $request->old('email', $request->query('email', ''));

        return $this->draw(function () use ($request, $email): void {
            ?>
            <p>Choose a new password.</p>
            <form method="post" action="<?php echo e(route('password.update')); ?>">
                <?php echo $this->tokenField($request); ?>
                <input type="hidden" name="token" value="<?php echo e((string) $request->route('token')); ?>">
                <input type="hidden" name="email" value="<?php echo e($email); ?>">
                <?php $this->error($request, 'email'); ?>
                <?php $this->error($request, 'password'); ?>
                <div class="row">
                    <span class="label"><label for="password">New password:</label></span>
                    <span class="formw"><input type="password" name="password" id="password" maxlength="30" size="20" class="form"></span>
                </div>
                <div class="row">
                    <span class="label"><label for="password_confirmation">Repeat password:</label></span>
                    <span class="formw"><input type="password" name="password_confirmation" id="password_confirmation" maxlength="30" size="20" class="form"></span>
                </div>
                <div class="row">
                    <span class="label">&nbsp;</span>
                    <span class="formw"><input type="submit" value="Change password" class="submit"></span>
                </div>
            </form>
            <?php
        });
    }

    /**
     * The page around whatever the callback prints.
     */
    private function draw(callable $body): string {
        global $PAGE, $this_page;

        $this_page = 'userpassword';

        ob_start();
        $PAGE->page_start();
        $PAGE->stripe_start();
        $body();
        $PAGE->stripe_end();
        $PAGE->page_end();

        return (string) ob_get_clean();
    }

    /**
     * The hidden field Laravel's CSRF check looks for.
     */
    private function tokenField(Request $request): string {
        return '<input type="hidden" name="_token" value="' . e($request->session()->token()) . '">';
    }

    /**
     * Prints the first error for a field, if the last request left one.
     */
    private function error(Request $request, string $field): void {
        global $PAGE;

        $errors = $request->session()->get('errors');
        if ($errors && $errors->has($field)) {
            $PAGE->error_message($errors->first($field));
        }
    }

}
