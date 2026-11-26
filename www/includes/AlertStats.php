<?php

namespace OpenAustralia\TWFY;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use OpenAustralia\TWFY\Models\Alert;
use OpenAustralia\TWFY\Models\User;

/**
 * The figures behind the admin alert statistics page.
 */
class AlertStats {

    /**
     * How many alerts there are in each state.
     *
     * @return list<array{string, int}>
     *   A label and a count for each state.
     */
    public static function totals(): array {
        return [
            ['Total', Alert::count()],
            ['Active', Alert::active()->count()],
            ['Deleted', Alert::deletedAlerts()->count()],
            ['Unconfirmed', Alert::unconfirmedAlerts()->count()],
        ];
    }

    /**
     * The rows of an admin list of alerts.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $alerts
     *   The alerts to list.
     * @param string $order
     *   The word "created" to list oldest first, anything else to sort by email.
     * @param callable(string): string $describe
     *   Given criteria, returns a description of what they search for.
     * @param callable(int): string $user_url
     *   Given a user id, returns the address of that user's page.
     *
     * @return list<array{who: string, url: string|null, description: string, created: string}>
     *   Who made each alert (their name when they have an account, else their email), the address of their user
     *   page (null without an account), what the alert searches for, and when it was created (empty when unknown).
     */
    public static function listRows(Builder $alerts, string $order, callable $describe, callable $user_url): array {
        $alerts = $alerts->orderBy($order === 'created' ? 'created' : 'email')
          ->orderBy('alert_id')
          ->get(['email', 'criteria', 'created']);
        $users = User::whereIn('email', $alerts->pluck('email')->unique())
          ->get(['user_id', 'email', 'firstname', 'lastname'])
          ->keyBy(fn (User $user): string => strtolower($user->email));

        $rows = [];
        foreach ($alerts as $alert) {
            $user = $users->get(strtolower($alert->email));
            $created = (string) $alert->created;
            $rows[] = [
                'who' => $user === null ? $alert->email : $user->fullName(),
                'url' => $user === null ? null : $user_url($user->user_id),
                'description' => $describe($alert->criteria),
                'created' => str_starts_with($created, '0000-') ? '' : $created,
            ];
        }
        return $rows;
    }

    /**
     * The probably-separate signups as table rows.
     *
     * @return list<array{int, string}>
     *   The id and criteria of each alert.
     */
    public static function probablySeparateSignupRows(): array {
        return self::probablySeparateSignups()
          ->map(fn (Alert $alert): array => [$alert->alert_id, $alert->criteria])
          ->values()
          ->all();
    }

    /**
     * The follower counts for each person as table rows.
     *
     * @param callable(int): ?string $name_for
     *   Given a person id, returns that person's name, or null if there is no
     *   such person.
     *
     * @return list<array{string, int, int}>
     *   The name, confirmed count and unconfirmed count for each person.
     */
    public static function speakerRows(callable $name_for): array {
        return array_map(
            fn (array $row): array => [$row['name'], $row['confirmed'], $row['unconfirmed']],
            self::bySpeaker($name_for)
        );
    }

    /**
     * The shared keyword counts as table rows.
     *
     * @return list<array{string, int, int}>
     *   The criteria, confirmed count and unconfirmed count for each keyword.
     */
    public static function keywordRows(): array {
        return array_map(
            fn (array $row): array => [$row['criteria'], $row['confirmed'], $row['unconfirmed']],
            self::byKeyword()
        );
    }

    /**
     * Active alerts that list several keywords separated by commas, which
     * usually means the person wanted one alert for each.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Alert>
     *   Each alert with its id and criteria.
     */
    public static function probablySeparateSignups(): Collection {
        return Alert::active()
          ->keywordAlerts()
          ->where('criteria', 'like', '%,%')
          ->get(['alert_id', 'criteria']);
    }

    /**
     * How many people follow each person, for people the lookup recognises.
     *
     * @param callable(int): ?string $name_for
     *   Given a person id, returns that person's name, or null if there is no
     *   such person.
     *
     * @return list<array{person_id: int, name: string, confirmed: int, unconfirmed: int}>
     *   Most followed first. Unconfirmed counts include deleted alerts.
     */
    public static function bySpeaker(callable $name_for): array {
        $confirmed = self::countsByPerson(Alert::active());
        $unconfirmed = self::countsByPerson(Alert::unconfirmedAlerts());

        $rows = [];
        foreach ($confirmed as $person_id => $count) {
            $name = $name_for($person_id);
            if ($name !== null) {
                $rows[] = [
                    'person_id' => $person_id,
                    'name' => $name,
                    'confirmed' => $count,
                    'unconfirmed' => $unconfirmed[$person_id] ?? 0,
                ];
            }
        }
        return $rows;
    }

    /**
     * How many alerts share each keyword criteria, counting only criteria
     * that more than one alert shares.
     *
     * @return list<array{criteria: string, confirmed: int, unconfirmed: int}>
     *   Criteria shared by active alerts first, then those only unconfirmed
     *   alerts share.
     */
    public static function byKeyword(): array {
        $confirmed = self::countsByCriteria(Alert::active());
        $unconfirmed = self::countsByCriteria(Alert::unconfirmedAlerts());

        $rows = [];
        foreach (array_unique([...array_keys($confirmed), ...array_keys($unconfirmed)]) as $criteria) {
            $rows[] = [
                'criteria' => (string) $criteria,
                'confirmed' => $confirmed[$criteria] ?? 0,
                'unconfirmed' => $unconfirmed[$criteria] ?? 0,
            ];
        }
        return $rows;
    }

    /**
     * Speaker alerts per person id.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $alerts
     *   The alerts to count.
     *
     * @return array<int, int>
     *   Alert counts keyed by person id, most followed first.
     */
    private static function countsByPerson(Builder $alerts): array {
        $counts = [];
        foreach ($alerts->speakerAlerts()->countedByCriteria()->get() as $row) {
            if (preg_match('#^speaker:(\d+)#', $row->criteria, $matches)) {
                $person_id = (int) $matches[1];
                $counts[$person_id] = ($counts[$person_id] ?? 0) + (int) $row->c;
            }
        }
        return $counts;
    }

    /**
     * Keyword alerts per criteria, for criteria more than one alert shares.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Alert> $alerts
     *   The alerts to count.
     *
     * @return array<string|int, int>
     *   Alert counts keyed by criteria, most shared first.
     */
    private static function countsByCriteria(Builder $alerts): array {
        return $alerts->keywordAlerts()
          ->countedByCriteria()
          ->having('c', '>', 1)
          ->pluck('c', 'criteria')
          ->map(fn ($count): int => (int) $count)
          ->all();
    }

}
