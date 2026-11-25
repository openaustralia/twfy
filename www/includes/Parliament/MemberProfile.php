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

    private const SHORT_DATE = 'j M Y';

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
     * The heading, naming the seats they hold and then those they have left.
     */
    public function heading(): string {
        $heading = ($this->person->sitsIn(House::Senate) ? 'Senator ' : '') . $this->name();
        if ($this->person->sitsIn(House::Representatives)) {
            $heading .= ' MP';
        }
        foreach ($this->person->houses() as $house) {
            if (!$this->person->sitsIn($house)) {
                $heading .= ', former ' . $house->memberTitle();
            }
        }

        return $heading;
    }

    /**
     * One line for each house, giving their party and seat there.
     *
     * @return list<string>
     *   Such as "Former Example Party Senator for Tasmania".
     */
    public function roles(): array {
        return array_map(function (House $house): string {
            $term = $this->person->latestTerm($house);
            $party = $term->party_name;
            $before = $this->person->partyChanges()->last();
            if ($term->isPresidingOfficer() && $before) {
                $party .= ', and ' . $before->party_name;
            }

            return ($this->person->sitsIn($house) ? '' : 'Former ') . "$party {$house->memberTitle()} for $term->display_constituency";
        }, $this->person->houses());
    }

    /**
     * The parties they have left, unless they now preside, as roles() then names the last one.
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
     * @return list<string>
     *   Such as "Minister for Examples (since 1 Jun 2022)".
     */
    public function currentOffices(): array {
        return $this->offices()
          ->filter(fn (Moffice $office): bool => $office->to_date->year === 9999)
          ->map(fn (Moffice $office): string => "$office->description (since {$office->from_date->format(self::SHORT_DATE)})")
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
          ->reject(fn (Moffice $office): bool => $office->to_date->year === 9999)
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
     * A term's start. Some Senate terms are only known to the year, and are stored as 1 January.
     *
     * @return array{text: string, reason: string}
     *   The event.
     */
    private function joined(string $what, Member $term): array {
        $date = $term->entered_house;
        $when = $term->house === House::Senate->value && $date->format('m-d') === '01-01'
          ? 'in ' . $date->year
          : 'on ' . $date->format(self::LONG_DATE);

        return ['text' => "$what $when", 'reason' => self::REASONS[$term->entered_reason] ?? (string) $term->entered_reason];
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
            'reason' => self::REASONS[$term->left_reason] ?? (string) $term->left_reason,
        ];
    }

}
