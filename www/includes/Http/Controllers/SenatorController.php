<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Parliament\Person;

/**
 * Pages about Senators.
 */
class SenatorController extends MemberController {

    /**
     * {@inheritdoc}
     */
    protected function house(): House {
        return House::Senate;
    }

    /**
     * {@inheritdoc}
     */
    protected function profilePage(Person $person): string {
        return 'peer';
    }

    /**
     * {@inheritdoc}
     */
    protected function indexView(): string {
        return 'senators.index';
    }

    /**
     * {@inheritdoc}
     */
    protected function csvName(): string {
        return 'senators.csv';
    }

    /**
     * {@inheritdoc}
     */
    protected function csvHeadings(): array {
        return ['Person ID', 'Name', 'Party', 'URI'];
    }

    /**
     * {@inheritdoc}
     */
    protected function csvRow(Member $member): array {
        return [
            $member->person_id,
            ucfirst($member->full_name),
            $member->party_label,
            $this->absoluteProfileUrl($member),
        ];
    }

}
