<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenAustralia\TWFY\Account\AlertListing;
use OpenAustralia\TWFY\Account\EmailChange;
use OpenAustralia\TWFY\EmailSuppressions;
use OpenAustralia\TWFY\Http\Requests\UpdateAccountRequest;
use OpenAustralia\TWFY\Models\User;

/**
 * Someone's own details, and the alerts they have set up.
 */
class AccountController {

    /**
     * The details, and the email alerts at the account's address.
     */
    public function show(Request $request): View {
        $user = $request->user();
        $suppression = EmailSuppressions::current($user->email);

        return view('account.show', [
            'page' => 'userviewself',
            'user' => $user,
            'alerts' => AlertListing::for($user->email),
            'suppressedSince' => $suppression ? Carbon::parse($suppression->suppressed_at) : null,
        ]);
    }

    /**
     * The form for changing the details.
     */
    public function edit(Request $request): View {
        return view('account.edit', ['page' => 'useredit', 'user' => $request->user()]);
    }

    /**
     * Saves the form.
     *
     * A new email address waits for its owner to follow the link emailed to it. A new password signs every other
     * browser out, and the current address hears about it.
     */
    public function update(UpdateAccountRequest $request, EmailChange $emailChange): RedirectResponse {
        $user = $request->user();
        $input = $request->validated();

        $user->forceFill([
            'firstname' => $input['first_name'] ?? '',
            'lastname' => $input['last_name'] ?? '',
            'optin' => $input['news'],
        ]);

        $newPassword = filled($input['password'] ?? null);
        if ($newPassword) {
            $user->password = User::hashPassword($input['password']);
        }
        $user->save();

        if ($newPassword) {
            $user->signOutEverywhere($request->session()->getId());
            $user->sendTemplate('password_changed_notice', ['RESETURL' => route('password.request')]);
        }

        $changingEmail = strcasecmp($input['email'], $user->email) !== 0;
        $limited = false;
        if ($changingEmail && strcasecmp($input['email'], (string) $user->pending_email) !== 0) {
            $limited = !$emailChange->request($user, $input['email']);
        }

        $status = 'Your details have been saved.';
        if ($limited) {
            $status .= " We haven't changed your email address, because you've asked for a few emails lately. Please try again in " . EmailChange::SEND_MINUTES . ' minutes.';
        } elseif ($changingEmail) {
            $status .= " To change your email address, follow the link we've emailed to {$input['email']}.";
        } elseif ($user->pending_email !== null) {
            // Saved with the current address back in the box: the waiting change stays until they cancel it.
            $status .= " Your new email address is still waiting for you to confirm it.";
        }

        return redirect()->route('account.edit')->with('status', $status);
    }

}
