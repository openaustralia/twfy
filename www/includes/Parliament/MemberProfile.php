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
     * The reasons a term starts. An unknown one says nothing, and others show as the database has them.
     */
    private const REASONS = [
        'by_election' => 'Byelection',
        'changed_party' => 'Changed party',
        'general_election' => 'Federal election',
        'reinstated' => 'Reinstated',
        'unknown' => '',
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
        if (!$term->isPresidingOfficer()) {
            return $term->party_name;
        }
        // The latest term that isn't itself an office, as they may have held another office first.
        $before = $this->person->terms->first(fn (Member $earlier): bool => !$earlier->isPresidingOfficer());

        return $before ? "$term->party_name, and $before->party_name" : $term->party_name;
    }

    /**
     * When a sitting member with one unbroken seat and no change of party started, and why. Others need the timelines.
     *
     * @return array{date: string, reason: string}|null
     *   The date is a year alone for a Senate term only known to the year.
     */
    public function since(): ?array {
        $house = House::from($this->person->latest()->house);
        $history = new ServiceHistory($this->person);
        // A second seat, house or break in service each add a seat entry.
        $simple = $this->person->sitsIn($house) && count($history->seats()) === 1 && $history->partyNotice() === null;
        if (!$simple) {
            return null;
        }

        $first = $this->person->firstTerm($house);

        $entered = $first->entered_house;
        $date = $first->enteredYearOnly() ? (string) $entered->year : $entered->format(self::LONG_DATE);

        return ['date' => $date, 'reason' => $this->reason($first->entered_reason)];
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
     * @return list<array{title: string, dates: string, house: \OpenAustralia\TWFY\Enums\House|null}>
     *   The latest to start first. The house is set for someone who sat in both, when the title names one.
     */
    public function pastOffices(): array {
        return $this->offices()
          ->reject(fn (Moffice $office): bool => $office->isCurrent())
          ->map(fn (Moffice $office): array => [
              'title' => $office->description,
              'dates' => $office->from_date->format(self::SHORT_DATE) . ' to ' . $office->to_date->format(self::SHORT_DATE),
              'house' => $this->houseNamedBy($office->description),
          ])
          ->values()
          ->all();
    }

    /**
     * The house an office's title names, for someone whose offices could belong to either.
     */
    private function houseNamedBy(string $title): ?House {
        if (count($this->person->houses()) < 2 || !preg_match('/\bin the (House|Senate)\b/', $title, $found)) {
            return null;
        }

        return $found[1] === 'Senate' ? House::Senate : House::Representatives;
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
     * A reason a term started, in words.
     */
    private function reason(?string $reason): string {
        return self::REASONS[$reason] ?? (string) $reason;
    }

}
