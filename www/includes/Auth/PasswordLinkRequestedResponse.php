<?php

namespace OpenAustralia\TWFY\Auth;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * The answer to asking for a reset link: the same whether or not an email was sent (lang/en/passwords.php).
 */
class PasswordLinkRequestedResponse implements SuccessfulPasswordResetLinkRequestResponse, FailedPasswordResetLinkRequestResponse {

    /**
     * @param string $status
     *   The language key for what the password broker did.
     */
    public function __construct(protected string $status) {
    }

    /**
     * @param \Illuminate\Http\Request $request
     *   The request.
     */
    public function toResponse($request): RedirectResponse {
        return back()->with('status', trans('passwords.sent'));
    }

}
