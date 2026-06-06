<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds Eloquent-style `created_at` / `updated_at` columns to `users` and
 * `member` so the new Eloquent models can rely on the framework's default
 * timestamp handling.
 *
 * Existing rows are backfilled from the closest existing columns so values
 * are not all NULL:
 *   - users: created_at <- registrationtime, updated_at <- lastvisit
 *   - member: created_at <- lastupdate,     updated_at <- lastupdate
 */
final class AddTimestampsToUsersAndMember extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('created_at', 'datetime', ['null' => true, 'after' => 'api_key'])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'after' => 'created_at'])
            ->update();

        $this->table('member')
            ->addColumn('created_at', 'datetime', ['null' => true, 'after' => 'lastupdate'])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'after' => 'created_at'])
            ->update();

        // Backfill so existing rows have sensible timestamps. The sentinel
        // '0000-01-01 00:00:00' is the legacy "unknown" marker and is left as NULL.
        $this->execute(<<<'SQL'
            UPDATE users
               SET created_at = registrationtime
             WHERE created_at IS NULL
               AND registrationtime <> '0000-01-01 00:00:00'
            SQL);

        $this->execute(<<<'SQL'
            UPDATE users
               SET updated_at = lastvisit
             WHERE updated_at IS NULL
               AND lastvisit <> '0000-01-01 00:00:00'
            SQL);

        $this->execute(<<<'SQL'
            UPDATE member
               SET created_at = lastupdate,
                   updated_at = lastupdate
             WHERE created_at IS NULL
            SQL);
    }

    public function down(): void
    {
        $this->table('users')
            ->removeColumn('created_at')
            ->removeColumn('updated_at')
            ->update();

        $this->table('member')
            ->removeColumn('created_at')
            ->removeColumn('updated_at')
            ->update();
    }
}
