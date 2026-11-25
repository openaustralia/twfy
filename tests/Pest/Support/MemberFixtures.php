<?php

/**
 * @file
 * Fictional members for specs.
 */

use OpenAustralia\TWFY\Models\Member;

/**
 * Rows the member specs need, created inside the test's transaction.
 */
final class MemberFixtures {

    /**
     * A term for a fictional person: a sitting Representative unless told otherwise.
     *
     * @param int $id
     *   The member id, also used for the person id unless another is given.
     * @param array<string, int|string> $overrides
     *   Columns to change.
     */
    public static function term(int $id, array $overrides = []): Member {
        return Member::create($overrides + [
            'member_id' => $id,
            'person_id' => $id,
            'house' => 1,
            'title' => '',
            'first_name' => 'Robin',
            'last_name' => 'Example',
            'constituency' => 'Test Ville',
            'party' => 'Example Party',
            'entered_house' => '2022-05-21',
            'left_house' => Member::NO_END_DATE,
            'entered_reason' => 'general_election',
            'left_reason' => 'still_in_office',
        ]);
    }

}
