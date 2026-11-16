<?php

/**
 * @file
 */

use OpenAustralia\TWFY\Models\Alert as AlertModel;
use OpenAustralia\TWFY\Models\Member as MemberModel;

/**
 * Rows the alert specs need, created inside the test's transaction.
 */
final class AlertFixtures {

    /**
     * An alert with a fictional address.
     */
    public static function alert(
      string $email,
      string $criteria,
      bool $confirmed = true,
      bool $deleted = false,
      string $token = 'tokenexample',
    ): AlertModel {
        return AlertModel::create([
            'email' => $email,
            'criteria' => $criteria,
            'registrationtoken' => $token,
            'confirmed' => $confirmed,
            'deleted' => $deleted,
            'created' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Details for ALERT::add(): a speaker alert unless overridden.
     *
     * @param array<string, mixed> $overrides
     *   Details to change.
     *
     * @return array<string, mixed>
     *   Details in the shape ALERT::add() takes.
     */
    public static function details(array $overrides = []): array {
        return $overrides + [
            'email' => 'alice@example.invalid',
            'pid' => '10001',
            'recommended' => 0,
        ];
    }

    /**
     * A current member of the House of Representatives.
     */
    public static function member(int $person_id, string $first_name = 'Alex', string $last_name = 'Example'): MemberModel {
        return MemberModel::create([
            'member_id' => $person_id,
            'house' => 1,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'constituency' => 'Testville ' . $person_id,
            'entered_house' => '2020-01-01',
            'left_house' => '9999-12-31',
            'person_id' => $person_id,
        ]);
    }

}
