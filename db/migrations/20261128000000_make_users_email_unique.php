<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * One account for each email address, kept by the database and not only by the sign-up and edit forms.
 *
 * The column ignores case, so Ann@example.org and ann@example.org are the same address.
 */
final class MakeUsersEmailUnique extends AbstractMigration
{
    public function up(): void
    {
        // Nothing is deleted or merged for you. Accounts that share an address have to be sorted out first.
        $shared = $this->fetchAll(
            'SELECT GROUP_CONCAT(user_id ORDER BY user_id) AS user_ids FROM users
             GROUP BY email HAVING COUNT(*) > 1 ORDER BY MIN(user_id) LIMIT 20'
        );
        if ($shared !== []) {
            throw new RuntimeException(
                "Some accounts share an email address, so it can't be made unique yet. Change or remove the extra "
                . "accounts, then run this again. Each line is the user ids of accounts sharing an address:\n"
                . implode("\n", array_column($shared, 'user_ids'))
            );
        }

        $this->execute('ALTER TABLE users DROP INDEX email, ADD UNIQUE KEY email (email)');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE users DROP INDEX email, ADD KEY email (email)');
    }
}
