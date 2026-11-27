<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Account\EmailChange;

/**
 * Confirming, resending and cancelling a change of email address.
 */
class EmailChangeController {

    /**
     * Follows the link emailed to the new address.
     *
     * Only the signed-in user it is for may confirm, as with Laravel's own email verification. Anyone else is
     * refused, and whoever is signed out signs in first and comes back.
     */
    public function confirm(Request $request, EmailChange $emailChange, int $user, string $hash): RedirectResponse {
        abort_unless((int) $request->user()->getKey() === $user, 403);

        $changed = $emailChange->confirm($request->user(), $hash, $request->session()->getId());

        return redirect()->route('account.show')->with(
            'status',
            $changed
              ? "Your email address is now {$request->user()->fresh()->email}. We'll send your alerts there."
              : 'That link has stopped working, or the change was cancelled. You can ask for a new one by saving your details again.'
        );
    }

    /**
     * Emails the link to the waiting address again.
     */
    public function resend(Request $request, EmailChange $emailChange): RedirectResponse {
        $sent = $emailChange->resend($request->user());

        return redirect()->route('account.edit')->with(
            'status',
            $sent
              ? "We've sent the link again. It may take a few minutes to arrive."
              : "We haven't sent it, because you've asked for a few emails lately. Please try again in " . EmailChange::SEND_MINUTES . ' minutes.'
        );
    }

    /**
     * Cancels the change, so the account keeps its address.
     */
    public function cancel(Request $request, EmailChange $emailChange): RedirectResponse {
        $emailChange->cancel($request->user());

        return redirect()->route('account.edit')->with('status', "We've cancelled the change. We'll keep sending your alerts to {$request->user()->email}.");
    }

}
