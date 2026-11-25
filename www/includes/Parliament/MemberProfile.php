<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\Moffice;

/**
 * What a person's profile says about their time in parliament, in words.
 */
final class MemberProfile {

    private const LONG_DATE = 'j F Y';

    public const SHORT_DATE = 'j M Y';

    /**
     * The reasons a term starts or ends. Others show as the database has them.
     */
    private const REASONS = [
        'by_election' => 'Byelection',
        'changed_party' => 'Changed party',
        'declared_void' => 'Declared void',
        'died' => 'Died',
        'disqualified' => 'Disqualified',
        'general_election' => 'Federal election',
        'general_election_standing' => 'Federal election (stood again)',
        'general_election_not_standing' => 'did not stand for re-election',
        'reinstated' => 'Reinstated',
        'resigned' => 'Resigned',
        'still_in_office' => 'Still in office',
    ];

    /**
     * The person's offices, once read.
     *
     * @var \Illuminate\Support\Collection<int, Moffice>|null
     */
    private ?Collection $offices = null;

    /**
     * Describes a person.
     */
    public function __construct(public readonly Person $person) {
    }

    /**
     * Their name, with any title.
     */
    public function name(): string {
        return ucfirst($this->person->latest()->full_name);
    }

    /**
     * The text for the browser's title, such as "Senator Jane Citizen, Tasmania".
     */
    public function pageTitle(): string {
        $constituency = $this->person->latest()->display_constituency;
        if ($this->person->sitsIn(House::Senate)) {
            return 'Senator ' . $this->name() . ', ' . $constituency;
        }

        $title = $this->name();
        if ($this->person->latestTerm(House::Representatives)) {
            $title .= $this->person->sitsIn(House::Representatives) ? ' MP' : ', former Representative';
            $title .= ', ' . $constituency;
        }
        if ($this->person->latestTerm(House::Senate)) {
            $title .= ', former Senator, ' . $constituency;
        }

        return $title;
    }

    /**
     * The name as the page's heading, with "Senator" or "MP" for a sitting member.
     */
    public function heading(): string {
        if ($this->person->sitsIn(House::Senate)) {
            return 'Senator ' . $this->name();
        }

        return $this->person->sitsIn(House::Representatives) ? $this->name() . ' MP' : $this->name();
    }

    /**
     * The houses they have left, such as "Former Senator", or null.
     */
    public function formerly(): ?string {
        $titles = [];
        foreach ($this->person->houses() as $house) {
            if (!$this->person->sitsIn($house)) {
                $titles[] = $house->memberTitle();
            }
        }

        return $titles ? 'Former ' . implode(' and ', $titles) : null;
    }

    /**
     * Their party now, or last. A presiding officer's office comes first, then the party they came from.
     */
    public function party(): string {
        $term = $this->person->latest();
        $before = $this->person->partyChanges()->last();

        return $term->isPresidingOfficer() && $before ? "$term->party_name, and $before->party_name" : $term->party_name;
    }

    /**
     * When a sitting member with one house and one party to their name started, and why. Others need history().
     *
     * @return array{date: string, reason: string}|null
     *   The date is a year alone for a Senate term only known to the year.
     */
    public function since(): ?array {
        $house = House::from($this->person->latest()->house);
        $simple = $this->person->sitsIn($house)
          && count($this->person->houses()) === 1
          && $this->person->partyChanges()->isEmpty();
        if (!$simple) {
            return null;
        }

        $first = $this->person->firstTerm($house);

        return ['date' => $this->startDate($first), 'reason' => $this->reason($first->entered_reason)];
    }

    /**
     * The parties they have left, unless they now preside, as party() then names the last one.
     */
    public function partyChanges(): ?string {
        $changes = $this->person->partyChanges();
        if ($changes->isEmpty() || $this->person->latest()->isPresidingOfficer()) {
            return null;
        }

        return 'Changed party ' . $changes
          ->map(fn (Member $term): string => "from $term->party_name on " . $term->left_house->format(self::SHORT_DATE))
          ->implode('; ');
    }

    /**
     * When they entered and left each house, and why.
     *
     * @return list<array{text: string, reason: string}>
     *   The events, in the order the page shows them.
     */
    public function history(): array {
        $representative = $this->person->firstTerm(House::Representatives);
        $senator = $this->person->firstTerm(House::Senate);
        $leftHouse = $representative && !$this->person->sitsIn(House::Representatives)
          ? $this->person->latestTerm(House::Representatives)
          : null;
        $events = [];

        if ($senator && $representative) {
            $events[] = $this->joined('Entered the Senate', $senator);
            if ($leftHouse) {
                $events[] = $this->left("Previously Representative for $leftHouse->display_constituency until", $leftHouse);
            }
        } elseif ($senator) {
            $events[] = $this->joined('Became a Senator', $senator);
        } elseif ($leftHouse) {
            $events[] = $this->left('Left House of Representatives on', $leftHouse);
        }

        if ($representative) {
            $events[] = $this->joined('Entered House of Representatives', $representative);
        }
        if ($senator && !$this->person->sitsIn(House::Senate)) {
            $events[] = $this->left('Left Senate on', $this->person->latestTerm(House::Senate));
        }

        return $events;
    }

    /**
     * The offices they hold now.
     *
     * @return list<array{title: string, since: string}>
     *   The latest to start first.
     */
    public function currentRoles(): array {
        return $this->offices()
          ->filter(fn (Moffice $office): bool => $office->isCurrent())
          ->map(fn (Moffice $office): array => [
              'title' => $office->description,
              'since' => $office->from_date->format(self::SHORT_DATE),
          ])
          ->values()
          ->all();
    }

    /**
     * The offices they used to hold.
     *
     * @return list<string>
     *   Such as "Minister for Examples (1 Jun 2019 to 1 Jun 2022)".
     */
    public function pastOffices(): array {
        return $this->offices()
          ->reject(fn (Moffice $office): bool => $office->isCurrent())
          ->map(fn (Moffice $office): string => "$office->description ({$office->from_date->format(self::SHORT_DATE)} to {$office->to_date->format(self::SHORT_DATE)})")
          ->values()
          ->all();
    }

    /**
     * Whether they sit now, so there will be speeches to email about.
     */
    public function canBeFollowed(): bool {
        return $this->person->sitsIn(House::Representatives) || $this->person->sitsIn(House::Senate);
    }

    /**
     * Who held their electorate before them, the latest first.
     *
     * @return \Illuminate\Support\Collection<int, Member>
     *   One term for each person.
     */
    public function predecessors(): Collection {
        return $this->electorateNeighbours('<', 'desc');
    }

    /**
     * Who has held their electorate since they arrived, the earliest first.
     *
     * @return \Illuminate\Support\Collection<int, Member>
     *   One term for each person.
     */
    public function successors(): Collection {
        return $this->electorateNeighbours('>', 'asc');
    }

    /**
     * Others in the electorate who entered the House before or after this person first did.
     *
     * @return \Illuminate\Support\Collection<int, Member>
     *   One term for each person. Empty for someone who never sat in the House.
     */
    private function electorateNeighbours(string $comparison, string $direction): Collection {
        $first = $this->person->firstTerm(House::Representatives);
        if (!$first) {
            return new Collection();
        }

        return Member::query()
          ->inHouse(House::Representatives)
          // The constituency heading the profile, so a profile headed by a Senate term lists nobody.
          ->where('constituency', $this->person->latest()->constituency)
          ->where('person_id', '!=', $this->person->id)
          ->where('entered_house', $comparison, $first->entered_house->toDateString())
          ->orderBy('entered_house', $direction)
          ->get()
          ->unique('person_id')
          ->values()
          ->toBase();
    }

    /**
     * All of their offices, the latest to start first.
     *
     * @return \Illuminate\Support\Collection<int, Moffice>
     *   Office rows.
     */
    private function offices(): Collection {
        return $this->offices ??= Moffice::query()
          ->where('person', $this->person->id)
          ->orderByDesc('from_date')
          ->get()
          ->toBase();
    }

    /**
     * When a term started. Some Senate terms are only known to the year, and are stored as 1 January.
     */
    private function startDate(Member $term): string {
        $date = $term->entered_house;
        $yearOnly = $term->house === House::Senate->value && $date->format('m-d') === '01-01';

        return $yearOnly ? (string) $date->year : $date->format(self::LONG_DATE);
    }

    /**
     * A reason a term started or ended, in words.
     */
    private function reason(?string $reason): string {
        return self::REASONS[$reason] ?? (string) $reason;
    }

    /**
     * A term's start.
     *
     * @return array{text: string, reason: string}
     *   The event.
     */
    private function joined(string $what, Member $term): array {
        $date = $this->startDate($term);
        $when = (strlen($date) === 4 ? 'in ' : 'on ') . $date;

        return ['text' => "$what $when", 'reason' => $this->reason($term->entered_reason)];
    }

    /**
     * A term's end.
     *
     * @return array{text: string, reason: string}
     *   The event.
     */
    private function left(string $what, Member $term): array {
        return [
            'text' => "$what " . $term->left_house->format(self::LONG_DATE),
            'reason' => $this->reason($term->left_reason),
        ];
    }

}
