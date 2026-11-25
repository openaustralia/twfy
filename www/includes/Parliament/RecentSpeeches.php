<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Carbon;

/**
 * A person's latest speeches, from the legacy search.
 */
class RecentSpeeches {

    /**
     * The latest speeches, or null if the search failed, so a broken index doesn't take the whole page with it.
     *
     * @return list<array{url: string, title: string, date: string, iso: string, extract: string}>|null
     *   The title and extract are HTML from the database; the rest is plain text. The iso date is Y-m-d.
     */
    public function for(int $personId): ?array {
        return rescue(fn (): array => array_map(fn (array $row): array => [
            'url' => html_entity_decode((string) $row['listurl']),
            'title' => (string) ($row['parent']['body'] ?? ''),
            'date' => Carbon::parse($row['hdate'])->format(MemberProfile::SHORT_DATE),
            'iso' => Carbon::parse($row['hdate'])->toDateString(),
            'extract' => (string) $row['body'],
        ], $this->search($personId)));
    }

    /**
     * The search's rows for the latest three speeches.
     *
     * @return list<array<string, mixed>>
     *   Rows as HANSARDLIST gives them.
     */
    protected function search(int $personId): array {
        global $SEARCHENGINE;

        $query = "speaker:$personId";
        $SEARCHENGINE = new \SEARCHENGINE($query);
        // "pop" keeps this search out of the search log.
        $data = (new \HANSARDLIST())->display('search_min', ['s' => $query, 'p' => 1, 'num' => 3, 'pop' => 1, 'o' => 'd'], 'none');

        return $data['rows'] ?? [];
    }

}
