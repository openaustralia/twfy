<?php

namespace OpenAustralia\TWFY\Auth;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

/**
 * Plugs the legacy password check and login page into Fortify.
 */
class AuthServiceProvider extends ServiceProvider {

    /**
     * Registers the redirect after signing in.
     */
    public function register(): void {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Tells Fortify how to find a user and what the login page looks like.
     */
    public function boot(): void {
        Fortify::authenticateUsing(new Authenticator());
        Fortify::loginView(fn (): string => $this->app->make(LoginPage::class)->render());
    }

}
