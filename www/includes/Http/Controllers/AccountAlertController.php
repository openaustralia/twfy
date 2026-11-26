<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Models\Alert;

/**
 * Unsubscribing from an alert, and subscribing again, from the account page.
 */
class AccountAlertController {

    /**
     * Unsubscribes from one of the signed-in user's alerts.
     */
    public function destroy(Request $request, int $alert): RedirectResponse {
        $this->own($request, $alert)->update(['deleted' => true]);

        return redirect()->route('account.show')->with('status', "You've unsubscribed from that alert. If you did not mean to, you can subscribe again below.");
    }

    /**
     * Subscribes again to one of the signed-in user's unsubscribed alerts.
     */
    public function restore(Request $request, int $alert): RedirectResponse {
        $this->own($request, $alert)->update(['deleted' => false]);

        return redirect()->route('account.show')->with('status', "You're subscribed again.");
    }

    /**
     * One of the signed-in user's alerts, or a 404 for anyone else's, as alerts belong to an email address.
     */
    private function own(Request $request, int $alert): Alert {
        return Alert::where('email', $request->user()->email)->where('confirmed', true)->findOrFail($alert);
    }

}
