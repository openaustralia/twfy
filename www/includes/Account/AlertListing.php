<?php

namespace OpenAustralia\TWFY\Account;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\User;
use OpenAustralia\TWFY\Parliament\MemberProfile;
use OpenAustralia\TWFY\Parliament\Person;
use OpenAustralia\TWFY\Parliament\SpeakingRecord;

/**
 * The email alerts at an address, as the account page lists them.
 */
class AlertListing {

    /**
     * Every alert at a user's address, oldest first.
     *
     * Each row says whether the alert is active, waiting to be confirmed, or unsubscribed, and what it looks for. An
     * alert about a person alone also gives their office, such as "MP for Wills", their photo if there is one, and for
     * an active alert a chart of how often they spoke in the last twelve months.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, state: string, isKeyword: bool, label: string, initials: string, role: string|null, photoUrl: string|null, chart: array<string, mixed>|null, subtitle: string, searchPath: string, confirmPath: string}>
     *   One row for each alert.
     */
    public static function for(User $user): Collection {
        $alerts = $user->alerts()->orderBy('alert_id')->get();

        // The people are found, and their speeches counted, once for all the alerts, not once for each.
        $people = Person::findMany($alerts->map(fn (Alert $alert): ?int => $alert->personId())->filter());
        $charted = $alerts
          ->filter(fn (Alert $alert): bool => self::hasChart($alert))
          ->map(fn (Alert $alert): ?Person => $people->get($alert->personId()))
          ->filter();
        $months = SpeakingRecord::lastTwelveMonthsFor($charted);

        return $alerts->map(fn (Alert $alert): array => self::row($alert, $people, $months))->values();
    }

    /**
     * Whether an alert gets a chart, which is an active alert about one person.
     */
    private static function hasChart(Alert $alert): bool {
        return $alert->personId() !== null && $alert->keywords() === '' && self::state($alert) === 'active';
    }

    /**
     * Whether an alert is active, waiting to be confirmed, or unsubscribed.
     */
    private static function state(Alert $alert): string {
        if (!$alert->confirmed) {
            return 'unconfirmed';
        }

        return $alert->deleted ? 'unsubscribed' : 'active';
    }

    /**
     * One alert as a row.
     *
     * @param \Illuminate\Support\Collection<int, Person> $people
     *   The people in the user's alerts, keyed by person id.
     * @param array<int, array<string, int>> $months
     *   Each charted person's speeches by month, keyed by person id.
     *
     * @return array{id: int, state: string, isKeyword: bool, label: string, initials: string, role: string|null, photoUrl: string|null, chart: array<string, mixed>|null, subtitle: string, searchPath: string, confirmPath: string}
     *   The row.
     */
    private static function row(Alert $alert, Collection $people, array $months): array {
        $keywords = $alert->keywords();
        $person = $alert->personId() ? $people->get($alert->personId()) : null;
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
        $state = self::state($alert);

        return [
            'id' => $alert->alert_id,
            'state' => $state,
            'isKeyword' => $keywords !== '',
            'label' => $label,
            'role' => $aboutPerson ? (new MemberProfile($person))->office() : null,
            'photoUrl' => $aboutPerson ? $person->latest()->photo_url : null,
            'chart' => self::hasChart($alert) && $person ? MonthlyChart::for($months[$person->id]) : null,
            'initials' => mb_strtoupper(implode('', array_map(fn (string $word): string => mb_substr($word, 0, 1), array_slice(explode(' ', $label), 0, 2)))),
            'subtitle' => $subtitle,
            'searchPath' => $alert->searchPath(),
            'confirmPath' => '/A/' . $alert->token,
        ];
    }

}
