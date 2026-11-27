<?php

namespace OpenAustralia\TWFY\Account;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Parliament\MemberProfile;
use OpenAustralia\TWFY\Parliament\Person;

/**
 * The email alerts at an address, as the account page lists them.
 */
class AlertListing {

    /**
     * Every alert at an address, oldest first.
     *
     * Each row says whether the alert is active, waiting to be confirmed, or unsubscribed, and what it looks for. An
     * alert about a person alone also gives their office, such as "MP for Wills", and their photo if there is one.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, state: string, isKeyword: bool, label: string, initials: string, role: string|null, photoUrl: string|null, subtitle: string, searchPath: string, confirmPath: string}>
     *   One row for each alert.
     */
    public static function for(string $email): Collection {
        return Alert::where('email', $email)
          ->orderBy('alert_id')
          ->get()
          ->map(fn (Alert $alert): array => self::row($alert))
          ->values();
    }

    /**
     * One alert as a row.
     *
     * @return array{id: int, state: string, isKeyword: bool, label: string, initials: string, role: string|null, photoUrl: string|null, subtitle: string, searchPath: string, confirmPath: string}
     *   The row.
     */
    private static function row(Alert $alert): array {
        $keywords = $alert->keywords();
        $person = $alert->personId() ? Person::find($alert->personId()) : null;
        $name = $person ? ucfirst($person->latest()->full_name) : 'a person we no longer list';

        if ($keywords === '') {
            $label = $name;
            $subtitle = 'Emails you when they speak';
        } else {
            $label = $keywords;
            $subtitle = $person ? "Emails you when $name says this in Parliament" : 'Emails you when anyone says this in Parliament';
        }

        // The office and photo are for alerts about one person, as an alert for a word is about the word.
        $aboutPerson = $person !== null && $keywords === '';

        return [
            'id' => $alert->alert_id,
            'state' => !$alert->confirmed ? 'unconfirmed' : ($alert->deleted ? 'unsubscribed' : 'active'),
            'isKeyword' => $keywords !== '',
            'label' => $label,
            'role' => $aboutPerson ? (new MemberProfile($person))->office() : null,
            'photoUrl' => $aboutPerson ? $person->latest()->photo_url : null,
            'initials' => mb_strtoupper(implode('', array_map(fn (string $word): string => mb_substr($word, 0, 1), array_slice(explode(' ', $label), 0, 2)))),
            'subtitle' => $subtitle,
            'searchPath' => $alert->searchPath(),
            'confirmPath' => '/A/' . $alert->token,
        ];
    }

}
