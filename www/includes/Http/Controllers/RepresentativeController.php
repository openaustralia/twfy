<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;

/**
 * Pages about members of the House of Representatives.
 */
class RepresentativeController extends MemberController {

    /**
     * {@inheritdoc}
     */
    protected function house(): House {
        return House::Representatives;
    }

    /**
     * {@inheritdoc}
     */
    protected function profilePage(): string {
        return 'mp';
    }

    /**
     * {@inheritdoc}
     */
    protected function indexView(): string {
        return 'representatives.index';
    }

    /**
     * {@inheritdoc}
     */
    protected function indexData(): array {
        return ['yours' => $this->visitorsRepresentative()];
    }

    /**
     * {@inheritdoc}
     */
    protected function csvName(): string {
        return 'representatives.csv';
    }

    /**
     * {@inheritdoc}
     */
    protected function csvHeadings(): array {
        return ['Person ID', 'First name', 'Last name', 'Party', 'Division', 'URI'];
    }

    /**
     * {@inheritdoc}
     */
    protected function csvRow(Member $member): array {
        return [
            $member->person_id,
            $member->first_name,
            $member->last_name,
            $member->party_label,
            $member->constituency,
            $this->absoluteProfileUrl($member),
        ];
    }

    /**
     * The current or latest Representative for the electorate the visitor has given, if any.
     */
    private function visitorsRepresentative(): ?Member {
        $electorate = (string) $GLOBALS['THEUSER']->constituency();
        if ($electorate === '') {
            return null;
        }

        return Member::query()
          ->inHouse($this->house())
          ->where('constituency', $electorate)
          ->orderByDesc('left_house')
          ->first();
    }

}
