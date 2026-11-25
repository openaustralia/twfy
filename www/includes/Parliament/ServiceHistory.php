<?php

namespace OpenAustralia\TWFY\Parliament;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;

/**
 * A person's time in parliament as timelines: their parties, their seats, and any change of party.
 */
final class ServiceHistory {

    /**
     * Terms this close together read as unbroken service.
     */
    private const UNREMARKED_DAYS = 7;

    private const JOINED = [
        'general_election' => 'Entered at a federal election',
        'by_election' => 'Entered at a by-election',
        'appointed' => 'Appointed',
    ];

    private const LEFT = [
        'resigned' => 'resigned',
        'disqualified' => 'disqualified',
        'died' => 'died',
        'retired' => 'retired',
        'declared_void' => 'election declared void',
        'general_election_not_standing' => 'did not stand for re-election',
    ];

    /**
     * Reads one person's terms.
     */
    public function __construct(private readonly Person $person) {
    }

    /**
     * The parties they have belonged to, the earliest first. A presiding officer's term counts to the party before it.
     *
     * @return list<array{name: string, dates: string, detail: string|null, current: bool, gap: bool, house: \OpenAustralia\TWFY\Enums\House|null}>
     *   Timeline entries. Time outside parliament shows only between two different parties.
     */
    public function parties(): array {
        $runs = [];
        foreach ($this->terms() as [$term, $party]) {
            $last = array_key_last($runs);
            if ($last !== null && $runs[$last]['party'] === $party) {
                $runs[$last]['end'] = $term->left_house;
                continue;
            }
            $runs[] = ['party' => $party, 'start' => $term->entered_house, 'end' => $term->left_house, 'first' => $term];
        }

        return $this->timeline($runs, fn (array $run): array => [$run['party'], null, null]);
    }

    /**
     * The seats they have held, the earliest first, with any time outside parliament between them.
     *
     * @return list<array{name: string, dates: string, detail: string|null, current: bool, gap: bool, house: \OpenAustralia\TWFY\Enums\House|null}>
     *   Timeline entries.
     */
    public function seats(): array {
        $runs = [];
        foreach ($this->terms() as [$term]) {
            $seat = $term->house . '|' . $term->constituency;
            $last = array_key_last($runs);
            if ($last !== null && $runs[$last]['seat'] === $seat && !$this->isGap($runs[$last]['end'], $term->entered_house)) {
                $runs[$last]['end'] = $term->left_house;
                $runs[$last]['last'] = $term;
                continue;
            }
            $runs[] = ['seat' => $seat, 'start' => $term->entered_house, 'end' => $term->left_house, 'first' => $term, 'last' => $term];
        }

        return $this->timeline($runs, fn (array $run): array => [
            $run['first']->display_constituency,
            $this->seatDetail($run['first'], $run['last']),
            House::from($run['first']->house),
        ]);
    }

    /**
     * What to say about their changes of party, to follow "Changed party", or null if they never have.
     */
    public function partyNotice(): ?string {
        $changes = [];
        $before = null;
        foreach ($this->terms() as [$term, $party]) {
            if ($before && $before[0]->left_reason === 'changed_party' && $before[1] !== $party) {
                $changes[] = ['on' => $this->shortDate($term->entered_house), 'from' => $before[1], 'to' => $party];
            }
            $before = [$term, $party];
        }
        if (!$changes) {
            return null;
        }

        $latest = array_pop($changes);
        $notice = $latest['to'] === 'Independent' && $this->person->latest()->isSitting()
          ? "on {$latest['on']}: left the {$latest['from']} and now sits as an Independent."
          : "on {$latest['on']}, from {$latest['from']}.";
        foreach (array_reverse($changes) as $change) {
            $notice .= $change['from'] === 'Independent'
              ? " Stopped sitting as an Independent on {$change['on']}."
              : " Left the {$change['from']} on {$change['on']}.";
        }

        return $notice;
    }

    /**
     * Their terms, the earliest first, each with the party it counts to.
     *
     * @return \Illuminate\Support\Collection<int, array{0: Member, 1: string}>
     *   Pairs of term and party.
     */
    private function terms(): Collection {
        $party = null;

        return $this->person->terms->sortBy('entered_house')->values()->map(function (Member $term) use (&$party): array {
            if (!$term->isPresidingOfficer() || $party === null) {
                $party = $term->party_name;
            }
            return [$term, $party];
        });
    }

    /**
     * Runs of terms as timeline entries, with an entry for each break between them.
     *
     * @param list<array<string, mixed>> $runs
     *   Each with a start and end date.
     * @param \Closure $describe
     *   Gives a run's name, detail and house.
     *
     * @return list<array{name: string, dates: string, detail: string|null, current: bool, gap: bool, house: \OpenAustralia\TWFY\Enums\House|null}>
     *   Timeline entries.
     */
    private function timeline(array $runs, \Closure $describe): array {
        $entries = [];
        $previousEnd = null;
        foreach ($runs as $run) {
            if ($previousEnd && $this->isGap($previousEnd, $run['start'])) {
                $entries[] = ['name' => 'Not in Parliament', 'dates' => $this->span($previousEnd, $run['start']), 'detail' => null, 'current' => false, 'gap' => true, 'house' => null];
            }
            [$name, $detail, $house] = $describe($run);
            $current = $run['end']->toDateString() === Member::NO_END_DATE;
            $start = $run['first']->enteredYearOnly() ? (string) $run['start']->year : null;
            $entries[] = [
                'name' => $name,
                'dates' => $this->span($run['start'], $current ? null : $run['end'], $start),
                'detail' => $detail,
                'current' => $current,
                'gap' => false,
                'house' => $house,
            ];
            $previousEnd = $run['end'];
        }

        return $entries;
    }

    /**
     * Dates and how long they cover, such as "1 Jul 2005 to 8 Aug 2013 (8 years)". No end means now. A start given as
     * text is too vague to measure from.
     */
    private function span(Carbon $start, ?Carbon $end, ?string $startAs = null): string {
        $dates = ($startAs ?? $this->shortDate($start)) . ' to ' . ($end ? $this->shortDate($end) : 'present');

        return $startAs ? $dates : $dates . ' (' . $this->duration($start, $end ?? Carbon::today()) . ')';
    }

    /**
     * A length of time in its largest whole unit.
     */
    private function duration(Carbon $start, Carbon $end): string {
        foreach (['year' => $start->diffInYears($end), 'month' => $start->diffInMonths($end)] as $unit => $count) {
            if ($count >= 1) {
                return (int) $count . ' ' . Str::plural($unit, (int) $count);
            }
        }
        $days = (int) $start->diffInDays($end);

        return $days . ' ' . Str::plural('day', $days);
    }

    /**
     * Whether the time between two terms is worth showing.
     */
    private function isGap(Carbon $end, Carbon $start): bool {
        return $end->diffInDays($start) > self::UNREMARKED_DAYS;
    }

    /**
     * A date as the timelines write it.
     */
    private function shortDate(Carbon $date): string {
        return $date->format(MemberProfile::SHORT_DATE);
    }

    /**
     * The house of a seat, and how they came to it and left it.
     */
    private function seatDetail(Member $first, Member $last): string {
        $how = array_filter([self::JOINED[$first->entered_reason] ?? null, self::LEFT[$last->left_reason] ?? null]);

        return House::from($first->house)->title() . '.' . ($how ? ' ' . ucfirst(implode('; ', $how)) . '.' : '');
    }

}
