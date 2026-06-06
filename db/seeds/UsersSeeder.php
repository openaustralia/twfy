<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seeds a small set of fake users for local development from
 * db/seeds/data/users.csv. All passwords are bcrypt-hashed "password".
 *
 * Includes one of each role plus a soft-deleted user, so login / permission
 * paths can be exercised without scrubbing production data.
 */
final class UsersSeeder extends AbstractSeed {

    use CsvSeederTrait;

    public function run(): void {
        $this->loadCsv($this, 'users', 'users.csv', ['registrationip', 'status', 'api_key']);
    }

}
