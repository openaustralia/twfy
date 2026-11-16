<?php

namespace OpenAustralia\TWFY;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use OpenAustralia\TWFY\Models\Alert;

/**
 * The figures behind the admin alert statistics page.
 */
class AlertStats {

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
        $unconfirmed = self::countsByPerson(Alert::where('confirmed', false));

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
        $unconfirmed = self::countsByCriteria(Alert::where('confirmed', false));

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
