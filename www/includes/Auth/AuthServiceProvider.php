<?php

namespace OpenAustralia\TWFY\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

/**
 * Plugs the legacy password check and pages into Fortify.
 */
class AuthServiceProvider extends ServiceProvider {

    /**
     * Registers the redirect after signing in, and the answer to asking for a reset link.
     */
    public function register(): void {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordLinkRequestedResponse::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordLinkRequestedResponse::class);
    }

    /**
     * Tells Fortify how to find a user, set a password, and what the pages look like.
     */
    public function boot(): void {
        Fortify::authenticateUsing(new Authenticator());
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(fn (Request $request): string => $this->app->make(LoginPage::class)->render([], $request->session()->get('status')));
        Fortify::requestPasswordResetLinkView(fn (Request $request): string => $this->app->make(PasswordPages::class)->requestLink($request));
        Fortify::resetPasswordView(fn (Request $request): string => $this->app->make(PasswordPages::class)->resetForm($request));
    }

}
